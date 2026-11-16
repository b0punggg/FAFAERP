<?php

namespace App\Services\Digital;

use App\Models\CatalogProduct;
use App\Models\DigitalImport;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BigSellerImporter
{
    public function __construct(protected BigSellerSheet $sheet)
    {
    }

    public function import(string $path, string $extension, int $companyId, string $channel, ?string $from, ?string $to, string $filename): DigitalImport
    {
        $parsed = $this->sheet->read($path, $extension);
        $matched = $this->matchSkus(array_column($parsed, 'sku'));

        return DB::transaction(function () use ($parsed, $matched, $companyId, $channel, $from, $to, $filename) {
            $import = DigitalImport::create([
                'company_id' => $companyId,
                'channel' => $channel !== '' ? $channel : 'BigSeller',
                'period_start' => $from,
                'period_end' => $to,
                'filename' => mb_substr($filename, 0, 180),
                'rows_count' => 0,
                'matched_count' => 0,
            ]);

            $now = now();
            $hit = 0;
            foreach (array_chunk($parsed, 200) as $chunk) {
                $payload = [];
                foreach ($chunk as $row) {
                    $sku = mb_substr(trim((string) $row['sku']), 0, 80);
                    $kd = $matched[$sku] ?? $matched[$this->plainNumber($sku)] ?? null;
                    if ($kd !== null) {
                        $hit++;
                    }
                    $payload[] = [
                        'digital_import_id' => $import->id,
                        'sku' => $sku,
                        'spu' => $this->text($row['spu'] ?? null, 80),
                        'product_name' => $this->text($row['product_name'] ?? null, 255),
                        'kd_brg' => $kd,
                        'orders_qty' => $this->sheet->number($row['orders_qty'] ?? 0),
                        'orders_net' => $this->sheet->number($row['orders_net'] ?? 0),
                        'sku_qty' => $this->sheet->number($row['sku_qty'] ?? 0),
                        'sku_qty_wh' => $this->sheet->number($row['sku_qty_wh'] ?? 0),
                        'sku_qty_wh_net' => $this->sheet->number($row['sku_qty_wh_net'] ?? 0),
                        'dana_produk' => $this->sheet->number($row['dana_produk'] ?? 0),
                        'dana_valid' => $this->sheet->number($row['dana_valid'] ?? 0),
                        'dana_bersih' => $this->sheet->number($row['dana_bersih'] ?? 0),
                        'dana_gudang' => $this->sheet->number($row['dana_gudang'] ?? 0),
                        'cancel_orders' => $this->sheet->number($row['cancel_orders'] ?? 0),
                        'cancel_amount' => $this->sheet->number($row['cancel_amount'] ?? 0),
                        'cancel_rate' => $this->sheet->number($row['cancel_rate'] ?? 0),
                        'refund_orders' => $this->sheet->number($row['refund_orders'] ?? 0),
                        'refund_amount' => $this->sheet->number($row['refund_amount'] ?? 0),
                        'refund_qty' => $this->sheet->number($row['refund_qty'] ?? 0),
                        'refund_rate' => $this->sheet->number($row['refund_rate'] ?? 0),
                        'subsidi' => $this->sheet->number($row['subsidi'] ?? 0),
                        'diskon' => $this->sheet->number($row['diskon'] ?? 0),
                        'voucher' => $this->sheet->number($row['voucher'] ?? 0),
                        'koin' => $this->sheet->number($row['koin'] ?? 0),
                        'subsidi_penjual' => $this->sheet->number($row['subsidi_penjual'] ?? 0),
                        'diskon_penjual' => $this->sheet->number($row['diskon_penjual'] ?? 0),
                        'voucher_penjual' => $this->sheet->number($row['voucher_penjual'] ?? 0),
                        'diskon_marketplace' => $this->sheet->number($row['diskon_marketplace'] ?? 0),
                        'voucher_marketplace' => $this->sheet->number($row['voucher_marketplace'] ?? 0),
                        'stock_total' => $this->sheet->number($row['stock_total'] ?? 0),
                        'stock_available' => $this->sheet->number($row['stock_available'] ?? 0),
                        'stock_transit' => $this->sheet->number($row['stock_transit'] ?? 0),
                        'stock_allocated' => $this->sheet->number($row['stock_allocated'] ?? 0),
                        'stock_promo' => $this->sheet->number($row['stock_promo'] ?? 0),
                        'report' => json_encode($this->report($row)),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                DB::table('digital_import_rows')->insert($payload);
            }

            $import->update([
                'rows_count' => count($parsed),
                'matched_count' => $hit,
            ]);

            return $import;
        });
    }

    protected function matchSkus(array $skus): array
    {
        $wanted = [];
        foreach ($skus as $sku) {
            $text = trim((string) $sku);
            if ($text === '') {
                continue;
            }
            $wanted[$text] = true;
            $plain = $this->plainNumber($text);
            if ($plain !== $text) {
                $wanted[$plain] = true;
            }
        }
        $keys = array_keys($wanted);
        if ($keys === []) {
            return [];
        }

        $map = [];
        CatalogProduct::query()
            ->where(function ($query) use ($keys) {
                $query->whereIn('sku', $keys)->orWhereIn('barcode', $keys);
            })
            ->get(['sku', 'barcode'])
            ->each(function ($product) use (&$map) {
                $map[(string) $product->sku] = $product->sku;
                if ($product->barcode) {
                    $map[(string) $product->barcode] = $product->sku;
                }
            });

        $missing = array_values(array_diff($keys, array_keys($map)));
        if ($missing !== []) {
            DB::table('mas_brg')
                ->where(function ($query) use ($missing) {
                    $query->whereIn('kd_brg', $missing)->orWhereIn('kd_bar', $missing);
                })
                ->get(['kd_brg', 'kd_bar'])
                ->each(function ($row) use (&$map) {
                    $map[(string) $row->kd_brg] = $row->kd_brg;
                    if ($row->kd_bar) {
                        $map[(string) $row->kd_bar] = $row->kd_brg;
                    }
                });
        }

        return $map;
    }

    protected function report(array $row): array
    {
        $keys = [
            'harga_rata', 'pendapatan_kotor', 'modal_sku', 'modal_gudang', 'laba_kotor', 'persen_laba',
            'pendapatan_rata', 'modal_rata', 'laba_gudang', 'pendapatan_harian', 'modal_harian', 'laba_harian',
        ];
        $report = [];
        foreach ($keys as $key) {
            $report[$key] = $this->sheet->number($row[$key] ?? 0);
        }

        return $report;
    }

    protected function text(mixed $value, int $limit): ?string
    {
        $text = trim((string) $value);

        return $text === '' ? null : mb_substr($text, 0, $limit);
    }

    protected function plainNumber(string $sku): string
    {
        if (preg_match('/^\d+\.0+$/', $sku)) {
            return (string) (int) $sku;
        }

        return $sku;
    }
}
