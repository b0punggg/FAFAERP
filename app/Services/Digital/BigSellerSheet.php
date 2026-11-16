<?php

namespace App\Services\Digital;

use RuntimeException;
use ZipArchive;

class BigSellerSheet
{
    /** @var array<string, string> */
    private array $fields = [
        'nama produk' => 'product_name',
        'nomor sku' => 'sku',
        'sku' => 'sku',
        'nomor spu' => 'spu',
        'jumlah pesanan' => 'orders_qty',
        'jumlah pesanan bersih' => 'orders_net',
        'jumlah penjualan sku' => 'sku_qty',
        'penjualan sku gudang' => 'sku_qty_wh',
        'penjualan sku gudang bersih' => 'sku_qty_wh_net',
        'dana produk' => 'dana_produk',
        'dana produk valid' => 'dana_valid',
        'dana produk bersih' => 'dana_bersih',
        'dana penjualan produk gudang' => 'dana_gudang',
        'jumlah pesanan dibatalkan' => 'cancel_orders',
        'dana penjualan produk dibatalkan' => 'cancel_amount',
        'persentase pembatalan' => 'cancel_rate',
        'jumlah pesanan pengembalian dana' => 'refund_orders',
        'dana penjualan produk pengembalian dana' => 'refund_amount',
        'jumlah penjualan produk pengembalian dana' => 'refund_qty',
        'persentase pengembalian dana' => 'refund_rate',
        'total subsidi' => 'subsidi',
        'total diskon' => 'diskon',
        'total voucher' => 'voucher',
        'koin' => 'koin',
        'total subsidi penjual' => 'subsidi_penjual',
        'diskon penjual' => 'diskon_penjual',
        'voucher penjual' => 'voucher_penjual',
        'diskon marketplace' => 'diskon_marketplace',
        'voucher marketplace' => 'voucher_marketplace',
        'total stok' => 'stock_total',
        'stok tersedia seluruh gudang' => 'stock_available',
        'stok dalam perjalanan' => 'stock_transit',
        'stok dialokasikan pesanan' => 'stock_allocated',
        'stok reservasi promo' => 'stock_promo',
        'harga rata-rata sku' => 'harga_rata',
        'pendapatan kotor' => 'pendapatan_kotor',
        'pendapatan di kotor' => 'pendapatan_kotor',
        'modal sku' => 'modal_sku',
        'modal sku gudang' => 'modal_gudang',
        'laba kotor' => 'laba_kotor',
        'persentase laba kotor' => 'persen_laba',
        'pendapatan rata-rata sku' => 'pendapatan_rata',
        'modal rata-rata sku' => 'modal_rata',
        'laba kotor sku gudang' => 'laba_gudang',
        'pendapatan kotor harian' => 'pendapatan_harian',
        'modal sku gudang harian' => 'modal_harian',
        'laba kotor harian' => 'laba_harian',
    ];

    public function read(string $path, string $extension): array
    {
        $extension = strtolower($extension);
        $matrix = in_array($extension, ['csv', 'txt'], true) ? $this->csv($path) : $this->xlsx($path);
        if ($matrix === []) {
            throw new RuntimeException('File kosong.');
        }

        $headerAt = $this->headerRow($matrix);
        if ($headerAt === null) {
            throw new RuntimeException('Kolom Nomor SKU atau Nama Produk tidak ditemukan.');
        }

        $columns = [];
        foreach ($matrix[$headerAt] as $index => $label) {
            $field = $this->field((string) $label);
            if ($field !== null) {
                $columns[$index] = $field;
            }
        }

        $rows = [];
        foreach (array_slice($matrix, $headerAt + 1) as $line) {
            $item = [];
            foreach ($columns as $index => $field) {
                $item[$field] = $line[$index] ?? '';
            }
            $sku = trim((string) ($item['sku'] ?? ''));
            $name = trim((string) ($item['product_name'] ?? ''));
            if ($sku === '' && $name === '') {
                continue;
            }
            $item['sku'] = $sku !== '' ? $sku : $name;
            $rows[] = $item;
        }

        if ($rows === []) {
            throw new RuntimeException('Tidak ada baris produk di file.');
        }

        return $rows;
    }

    public function number(mixed $value): float
    {
        $text = trim((string) $value);
        if ($text === '' || $text === '-' || $text === '—') {
            return 0.0;
        }
        $text = str_replace(['%', ' '], '', $text);
        if (str_contains($text, ',')) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $text)) {
            $text = str_replace('.', '', $text);
        }

        return is_numeric($text) ? (float) $text : 0.0;
    }

    private function field(string $label): ?string
    {
        $key = $this->norm($label);
        if (isset($this->fields[$key])) {
            return $this->fields[$key];
        }
        if (str_contains($key, 'dialokas') && str_contains($key, 'pesan')) {
            return 'stock_allocated';
        }
        if (str_contains($key, 'nomor sku')) {
            return 'sku';
        }
        if (str_contains($key, 'nama produk')) {
            return 'product_name';
        }

        return null;
    }

    private function norm(string $label): string
    {
        $label = mb_strtolower(trim($label));
        $label = str_replace(["\n", "\r", '.'], ' ', $label);
        $label = preg_replace('/\s+/', ' ', $label) ?? $label;

        return trim($label);
    }

    private function headerRow(array $matrix): ?int
    {
        $limit = min(20, count($matrix));
        $best = null;
        $score = 0;
        for ($i = 0; $i < $limit; $i++) {
            $hits = 0;
            foreach ($matrix[$i] as $cell) {
                if ($this->field((string) $cell) !== null) {
                    $hits++;
                }
            }
            if ($hits > $score) {
                $score = $hits;
                $best = $i;
            }
        }

        return $score >= 2 ? $best : null;
    }

    private function csv(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('File tidak bisa dibaca.');
        }
        $first = fgets($handle);
        if ($first === false) {
            fclose($handle);

            return [];
        }
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first) ?? $first;
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $rows = [str_getcsv($first, $delimiter)];
        while (($line = fgetcsv($handle, 0, $delimiter)) !== false) {
            $rows[] = $line;
        }
        fclose($handle);

        return $rows;
    }

    private function xlsx(string $path): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new RuntimeException('File Excel belum bisa dibaca di server ini. Simpan sebagai CSV lalu impor lagi.');
        }
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('File Excel tidak bisa dibuka.');
        }
        $shared = $this->sharedStrings($zip);
        $sheet = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheet === false) {
            $sheet = $zip->getFromName('xl/worksheets/sheet.xml');
        }
        $zip->close();
        if ($sheet === false) {
            throw new RuntimeException('Lembar pertama Excel tidak ditemukan.');
        }

        $xml = $this->unwrap($sheet);
        if ($xml === null) {
            throw new RuntimeException('Isi Excel tidak bisa dibaca.');
        }
        $rows = [];
        foreach ($xml->sheetData->row as $row) {
            $line = [];
            foreach ($row->c as $cell) {
                $ref = (string) $cell['r'];
                $index = $this->columnIndex($ref);
                $type = (string) $cell['t'];
                if ($type === 's') {
                    $line[$index] = $shared[(int) $cell->v] ?? '';
                } elseif ($type === 'inlineStr') {
                    $line[$index] = (string) $cell->is->t;
                } else {
                    $line[$index] = (string) $cell->v;
                }
            }
            if ($line !== []) {
                ksort($line);
                $rows[] = $line;
            }
        }

        return $rows;
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }
        $sx = $this->unwrap($xml);
        if ($sx === null) {
            return [];
        }
        $strings = [];
        foreach ($sx->si as $si) {
            if (isset($si->t)) {
                $strings[] = (string) $si->t;
                continue;
            }
            $text = '';
            foreach ($si->r as $run) {
                $text .= (string) $run->t;
            }
            $strings[] = $text;
        }

        return $strings;
    }

    private function unwrap(string $content): ?\SimpleXMLElement
    {
        $content = preg_replace('/\sxmlns(:\w+)?="[^"]*"/', '', $content) ?? $content;
        $content = preg_replace('/<(\/?)(\w+):/', '<$1', $content) ?? $content;
        $sx = simplexml_load_string($content);

        return $sx === false ? null : $sx;
    }

    private function columnIndex(string $ref): int
    {
        $letters = preg_replace('/\d/', '', strtoupper($ref)) ?? 'A';
        $number = 0;
        $length = strlen($letters);
        for ($i = 0; $i < $length; $i++) {
            $number = $number * 26 + (ord($letters[$i]) - 64);
        }

        return $number - 1;
    }
}
