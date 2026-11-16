<?php

namespace App\Support\Reports;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportOutput
{
    public static function wants(Request $request): bool
    {
        return $request->boolean('cetak') || $request->boolean('excel');
    }

    public static function send(Request $request, ReportSheet $sheet)
    {
        if ($request->boolean('excel')) {
            return self::excel($sheet);
        }

        return view('admin.reports.print.sheet', ['sheet' => $sheet]);
    }

    public static function excel(ReportSheet $sheet): StreamedResponse
    {
        $filename = Str::slug($sheet->title) ?: 'laporan';
        $filename .= '-'.date('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($sheet) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [$sheet->title], ';');
            if ($sheet->subtitle !== '') {
                fputcsv($out, [$sheet->subtitle], ';');
            }
            fputcsv($out, [], ';');

            foreach ($sheet->blocks() as $block) {
                if (! empty($block['title'])) {
                    fputcsv($out, [$block['title']], ';');
                }
                $columns = $block['columns'] ?? [];
                fputcsv($out, array_column($columns, 'label'), ';');
                foreach ($block['rows'] ?? [] as $row) {
                    $line = [];
                    foreach ($columns as $column) {
                        $line[] = self::excelCell($row[$column['key']] ?? '', $column);
                    }
                    fputcsv($out, $line, ';');
                }
                fputcsv($out, [], ';');
            }

            foreach ($sheet->summary as $label => $value) {
                fputcsv($out, [$label, $value], ';');
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public static function display(mixed $value, array $column): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if (! empty($column['money'])) {
            return 'Rp '.number_format((float) $value, 0, ',', '.');
        }
        if (! empty($column['qty']) || ! empty($column['stock'])) {
            $number = (float) $value;
            $decimals = fmod($number, 1.0) === 0.0 ? 0 : 2;

            return number_format($number, $decimals, ',', '.');
        }
        if (! empty($column['date'])) {
            $time = strtotime((string) $value);

            return $time ? date('d/m/Y', $time) : (string) $value;
        }

        return (string) $value;
    }

    public static function excelCell(mixed $value, array $column): string
    {
        if ($value === null || $value === '') {
            return '';
        }
        if (! empty($column['money'])) {
            return number_format((float) $value, 0, ',', '');
        }
        if (! empty($column['qty']) || ! empty($column['stock'])) {
            $number = (float) $value;
            $decimals = fmod($number, 1.0) === 0.0 ? 0 : 2;

            return number_format($number, $decimals, ',', '');
        }
        if (! empty($column['date'])) {
            $time = strtotime((string) $value);

            return $time ? date('d/m/Y', $time) : (string) $value;
        }

        return (string) $value;
    }
}
