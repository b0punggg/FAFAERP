<?php

namespace App\Services\Utility;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeadStockService
{
    public function topDeadStock(?string $kdToko, int $month, int $year, int $limit = 10): Collection
    {
        if (! $kdToko) {
            return collect();
        }

        $soldSub = DB::table('jual_brg')
            ->select('kd_brg')
            ->where('kd_toko', $kdToko)
            ->whereMonth('tgl_jual', $month)
            ->whereYear('tgl_jual', $year)
            ->distinct();

        $rows = DB::table('beli_brg as bb')
            ->leftJoin('mas_brg as mb', function ($j) use ($kdToko) {
                $j->on('bb.kd_brg', '=', 'mb.kd_brg')->where('mb.kd_toko', '=', $kdToko);
            })
            ->where('bb.kd_toko', $kdToko)
            ->whereNotIn('bb.kd_brg', $soldSub)
            ->whereRaw("INSTR(IFNULL(mb.nm_brg,''), 'JASA') = 0")
            ->selectRaw('bb.kd_brg, MAX(mb.nm_brg) as nm_brg, SUM(bb.stok_jual) as stok')
            ->groupBy('bb.kd_brg')
            ->havingRaw('stok > 0')
            ->orderByDesc('stok')
            ->limit($limit)
            ->get();

        return $rows->map(function ($row) use ($kdToko) {
            $lastSale = DB::table('jual_brg')
                ->where('kd_toko', $kdToko)
                ->where('kd_brg', $row->kd_brg)
                ->max('tgl_jual');

            $row->last_sale = $lastSale;

            return $row;
        });
    }
}
