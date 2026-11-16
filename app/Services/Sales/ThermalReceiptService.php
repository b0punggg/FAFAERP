<?php

namespace App\Services\Sales;

use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class ThermalReceiptService
{
    public function printSale(Sale $sale): bool
    {
        $sale->loadMissing('items');
        $payload = $this->bytes($this->receipt($sale));

        if (function_exists('printer_open') && $this->writeExtension($payload)) {
            return true;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            return $this->writeWindows($payload);
        }

        return false;
    }

    public function receipt(Sale $sale): string
    {
        $width = max(32, (int) config('erp.thermal_width', 42));
        $store = DB::table('toko')->where('kd_toko', $sale->kd_toko)->first();
        $name = $this->plain($store->nm_toko ?? 'FAFA');
        $lines = [];
        $lines[] = $this->center($name, $width);
        $lines[] = $this->center('Nota Penjualan', $width);
        $lines[] = str_repeat('-', $width);
        $lines[] = $this->pair('No', $this->plain($sale->no_fak), $width);
        $lines[] = $this->pair('Tgl', (string) $sale->tgl_jual, $width);
        $lines[] = $this->pair('Bayar', $this->plain($sale->kd_bayar ?: 'TUNAI'), $width);
        if ($sale->nm_pel) {
            $lines[] = $this->pair('Pel', $this->plain($sale->nm_pel), $width);
        }
        if ($sale->nm_member) {
            $lines[] = $this->pair('Member', $this->plain($sale->nm_member), $width);
        }
        $lines[] = str_repeat('-', $width);

        $itemDisc = 0.0;
        foreach ($sale->items as $item) {
            $unit = $this->plain($item->nm_sat ?: 'pcs');
            $lines[] = $this->clip($this->plain($item->nm_brg), $width);
            $left = rtrim(rtrim(number_format((float) $item->jml_brg, 2, '.', ''), '0'), '.').' '.$unit;
            $left .= ' x '.$this->money($item->hrg_jual);
            $lines[] = $this->pair($left, $this->money($item->tot_jual), $width);
            if ((float) $item->disc > 0) {
                $itemDisc += (float) $item->disc;
                $lines[] = $this->pair('  Diskon item', '-'.$this->money($item->disc), $width);
            }
        }

        $lines[] = str_repeat('-', $width);
        $lines[] = $this->pair('Jumlah', $this->money($sale->tot_jual), $width);
        if ($itemDisc > 0) {
            $lines[] = $this->pair('Diskon item', '-'.$this->money($itemDisc), $width);
        }
        if ((float) $sale->disc_nota > 0) {
            $lines[] = $this->pair('Diskon nota', '-'.$this->money($sale->disc_nota), $width);
        }
        if ((float) $sale->voucher > 0) {
            $lines[] = $this->pair('Voucher', '-'.$this->money($sale->voucher), $width);
        }
        if ((float) $sale->voucher_member > 0) {
            $lines[] = $this->pair('Voucher member', '-'.$this->money($sale->voucher_member), $width);
        }
        $lines[] = $this->pair('TOTAL', $this->money($sale->tot_bayar), $width);
        $lines[] = $this->pair('Diterima', $this->money($sale->diterima), $width);
        $cara = $sale->kd_bayar ?: 'TUNAI';
        if ($cara === 'TEMPO') {
            $lines[] = $this->pair('Sisa tempo', $this->money($sale->byr_hutang), $width);
        } else {
            $kembali = max(0, (float) $sale->diterima - (float) $sale->tot_bayar);
            $lines[] = $this->pair('Kembali', $this->money($kembali), $width);
        }
        $lines[] = str_repeat('-', $width);
        $lines[] = $this->center('Terima kasih', $width);
        $lines[] = '';
        $lines[] = '';
        $lines[] = '';

        return implode("\n", $lines);
    }

    private function bytes(string $text): string
    {
        $body = function_exists('iconv')
            ? (iconv('UTF-8', 'CP437//TRANSLIT//IGNORE', $text) ?: $text)
            : $text;

        return "\x1B\x40".$body."\n\n\n\x1D\x56\x00";
    }

    private function writeExtension(string $payload): bool
    {
        if (! defined('PRINTER_MODE')) {
            define('PRINTER_MODE', 2);
        }

        foreach ($this->printerNames() as $name) {
            $handle = @call_user_func('printer_open', $name);
            if (! $handle) {
                continue;
            }
            try {
                call_user_func('printer_set_option', $handle, PRINTER_MODE, 'RAW');
                call_user_func('printer_write', $handle, $payload);
                call_user_func('printer_close', $handle);

                return true;
            } catch (\Throwable $e) {
                @call_user_func('printer_close', $handle);
            }
        }

        return false;
    }

    private function writeWindows(string $payload): bool
    {
        $installed = $this->installedPrinters();
        $names = $this->printerNames();
        if ($installed) {
            $wanted = array_map('strtolower', $names);
            $names = array_values(array_filter($installed, fn ($name) => in_array(strtolower($name), $wanted, true)));
        }
        if ($names === []) {
            return false;
        }

        $dir = storage_path('app/thermal');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $bin = $dir.DIRECTORY_SEPARATOR.'nota-'.uniqid().'.bin';
        $script = $dir.DIRECTORY_SEPARATOR.'raw-print.ps1';
        file_put_contents($bin, $payload);
        file_put_contents($script, $this->powershell());

        try {
            foreach ($names as $name) {
                $command = 'powershell -NoProfile -ExecutionPolicy Bypass -File '
                    .escapeshellarg($script).' -PrinterName '.escapeshellarg($name).' -FilePath '.escapeshellarg($bin);
                $output = [];
                $code = 1;
                exec($command, $output, $code);
                if ($code === 0 && in_array('OK', $output, true)) {
                    return true;
                }
            }
        } finally {
            @unlink($bin);
        }

        return false;
    }

    private function installedPrinters(): array
    {
        $output = [];
        $code = 1;
        exec('powershell -NoProfile -Command "Get-Printer | Select-Object -ExpandProperty Name"', $output, $code);
        if ($code !== 0) {
            return [];
        }

        return array_values(array_filter(array_map('trim', $output)));
    }

    private function printerNames(): array
    {
        $names = config('erp.thermal_printers', []);

        return array_values(array_filter(array_map('strval', is_array($names) ? $names : [])));
    }

    private function powershell(): string
    {
        return <<<'PS1'
param([string]$PrinterName, [string]$FilePath)
$ErrorActionPreference = 'Stop'
Add-Type -TypeDefinition @"
using System;
using System.Runtime.InteropServices;
public class FafaRawPrinter {
  [StructLayout(LayoutKind.Sequential, CharSet=CharSet.Ansi)]
  public class DOCINFOA {
    [MarshalAs(UnmanagedType.LPStr)] public string pDocName;
    [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile;
    [MarshalAs(UnmanagedType.LPStr)] public string pDataType;
  }
  [DllImport("winspool.Drv", EntryPoint="OpenPrinterA", SetLastError=true, CharSet=CharSet.Ansi)]
  public static extern bool OpenPrinter(string szPrinter, out IntPtr hPrinter, IntPtr pd);
  [DllImport("winspool.Drv", SetLastError=true)]
  public static extern bool ClosePrinter(IntPtr hPrinter);
  [DllImport("winspool.Drv", EntryPoint="StartDocPrinterA", SetLastError=true, CharSet=CharSet.Ansi)]
  public static extern bool StartDocPrinter(IntPtr hPrinter, int level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA di);
  [DllImport("winspool.Drv", SetLastError=true)]
  public static extern bool EndDocPrinter(IntPtr hPrinter);
  [DllImport("winspool.Drv", SetLastError=true)]
  public static extern bool StartPagePrinter(IntPtr hPrinter);
  [DllImport("winspool.Drv", SetLastError=true)]
  public static extern bool EndPagePrinter(IntPtr hPrinter);
  [DllImport("winspool.Drv", SetLastError=true)]
  public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, int dwCount, out int dwWritten);
  public static bool Send(string printerName, byte[] bytes) {
    IntPtr hPrinter;
    if (!OpenPrinter(printerName, out hPrinter, IntPtr.Zero)) return false;
    DOCINFOA di = new DOCINFOA();
    di.pDocName = "Nota";
    di.pDataType = "RAW";
    if (!StartDocPrinter(hPrinter, 1, di)) { ClosePrinter(hPrinter); return false; }
    if (!StartPagePrinter(hPrinter)) { EndDocPrinter(hPrinter); ClosePrinter(hPrinter); return false; }
    IntPtr p = Marshal.AllocCoTaskMem(bytes.Length);
    Marshal.Copy(bytes, 0, p, bytes.Length);
    int written;
    bool ok = WritePrinter(hPrinter, p, bytes.Length, out written);
    Marshal.FreeCoTaskMem(p);
    EndPagePrinter(hPrinter);
    EndDocPrinter(hPrinter);
    ClosePrinter(hPrinter);
    return ok;
  }
}
"@
$bytes = [System.IO.File]::ReadAllBytes($FilePath)
if ([FafaRawPrinter]::Send($PrinterName, $bytes)) { Write-Output 'OK'; exit 0 }
exit 1
PS1;
    }

    private function plain(?string $value): string
    {
        $text = trim((string) $value);
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;

        return preg_replace('/[^\x20-\x7E]/', '', $text) ?: '';
    }

    private function money($amount): string
    {
        return number_format((float) $amount, 0, ',', '.');
    }

    private function clip(string $text, int $width): string
    {
        return strlen($text) <= $width ? $text : substr($text, 0, $width);
    }

    private function center(string $text, int $width): string
    {
        $text = $this->clip($text, $width);
        $pad = max(0, intdiv($width - strlen($text), 2));

        return str_repeat(' ', $pad).$text;
    }

    private function pair(string $left, string $right, int $width): string
    {
        $right = $this->clip($right, $width);
        $room = $width - strlen($right) - 1;
        if ($room < 1) {
            return $this->clip($right, $width);
        }
        $left = $this->clip($left, $room);

        return $left.str_repeat(' ', $width - strlen($left) - strlen($right)).$right;
    }
}
