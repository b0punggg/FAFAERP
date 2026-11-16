<?php

namespace App\Services\Utility;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DataIntegrityService
{
    public function duplicateBarcodes(?string $kdToko): Collection
    {
        if (Schema::hasTable('products')) {
            return DB::table('products')
                ->whereNotNull('barcode')
                ->where('barcode', '!=', '')
                ->selectRaw('barcode, COUNT(*) as cnt, GROUP_CONCAT(sku) as skus')
                ->groupBy('barcode')
                ->havingRaw('COUNT(*) > 1')
                ->limit(50)
                ->get();
        }

        if (! Schema::hasTable('mas_brg')) {
            return collect();
        }

        return DB::table('mas_brg')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->whereNotNull('kd_bar')
            ->where('kd_bar', '!=', '')
            ->selectRaw('kd_bar as barcode, COUNT(*) as cnt, GROUP_CONCAT(kd_brg) as skus')
            ->groupBy('kd_bar')
            ->havingRaw('COUNT(*) > 1')
            ->limit(50)
            ->get();
    }

    public function negativeStock(?string $kdToko): Collection
    {
        if (! Schema::hasTable('beli_brg')) {
            return collect();
        }

        return DB::table('beli_brg')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->where('stok_jual', '<', 0)
            ->select('no_urut', 'kd_brg', 'no_fak', 'stok_jual')
            ->limit(50)
            ->get();
    }

    public function salesWithoutLines(?string $kdToko): Collection
    {
        if (! Schema::hasTable('jual') || ! Schema::hasTable('jual_brg')) {
            return collect();
        }

        return DB::table('jual as j')
            ->when($kdToko, fn ($q) => $q->where('j.kd_toko', $kdToko))
            ->leftJoin('jual_brg as jb', function ($j) {
                $j->on('j.no_fak', '=', 'jb.no_fak')->on('j.kd_toko', '=', 'jb.kd_toko');
            })
            ->whereNull('jb.no_urut')
            ->select('j.no_fak', 'j.tgl_jual', 'j.tot_bayar')
            ->limit(30)
            ->get();
    }
}
