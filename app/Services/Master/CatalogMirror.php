<?php

namespace App\Services\Master;

use App\Models\CatalogProduct;
use App\Models\Product;

class CatalogMirror
{
    public function upsert(Product $row): void
    {
        $fromPusat = strtoupper(trim((string) ($row->kd_toko ?? ''))) === 'PUSAT';
        $existing = CatalogProduct::where('sku', $row->kd_brg)->first();
        $payload = [
            'barcode' => $row->kd_bar !== '' && $row->kd_bar !== null ? $row->kd_bar : null,
            'name' => $row->nm_brg,
            'kd_kem1' => (int) $row->kd_kem1 ?: 1,
            'nm_kem1' => $row->nm_kem1,
            'jum_kem1' => $row->jum_kem1 ?: 1,
            'kd_kem2' => $row->kd_kem2 !== '' && $row->kd_kem2 !== null ? (int) $row->kd_kem2 : null,
            'nm_kem2' => $row->nm_kem2 !== '' && $row->nm_kem2 !== null ? $row->nm_kem2 : null,
            'jum_kem2' => $row->jum_kem2,
            'kd_kem3' => $row->kd_kem3 !== '' && $row->kd_kem3 !== null ? (int) $row->kd_kem3 : null,
            'nm_kem3' => $row->nm_kem3 !== '' && $row->nm_kem3 !== null ? $row->nm_kem3 : null,
            'jum_kem3' => $row->jum_kem3,
            'is_active' => true,
        ];

        if ($existing && ! $fromPusat) {
            if ($existing->name) {
                unset($payload['name']);
            }
            if ($existing->nm_kem1) {
                unset($payload['kd_kem1'], $payload['nm_kem1'], $payload['jum_kem1']);
            }
            if ($existing->nm_kem2) {
                unset($payload['kd_kem2'], $payload['nm_kem2'], $payload['jum_kem2']);
            }
            if ($existing->nm_kem3) {
                unset($payload['kd_kem3'], $payload['nm_kem3'], $payload['jum_kem3']);
            }
            if ($existing->barcode) {
                unset($payload['barcode']);
            }
        }

        CatalogProduct::updateOrCreate(['sku' => $row->kd_brg], $payload);
    }

    public function forget(string $sku): void
    {
        CatalogProduct::where('sku', $sku)->delete();
    }
}
