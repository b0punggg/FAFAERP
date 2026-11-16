<?php

namespace App\Services\Master;

use App\Models\CatalogProduct;
use App\Models\Product;

class CatalogMirror
{
    public function upsert(Product $row): void
    {
        CatalogProduct::updateOrCreate(
            ['sku' => $row->kd_brg],
            [
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
            ]
        );
    }

    public function forget(string $sku): void
    {
        CatalogProduct::where('sku', $sku)->delete();
    }
}
