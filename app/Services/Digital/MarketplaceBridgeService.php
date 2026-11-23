<?php

namespace App\Services\Digital;

use App\Models\Company;
use App\Models\DigitalOrder;
use App\Models\DigitalOrderLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MarketplaceBridgeService
{
    public function packingRows(int $limit = 20)
    {
        if (! Schema::hasTable('paket_ready')) {
            return collect();
        }

        $rows = DB::table('paket_ready')->orderByDesc('id')->limit($limit)->get();
        $ids = $rows->pluck('id');
        $items = Schema::hasTable('paket_ready_item') && $ids->isNotEmpty()
            ? DB::table('paket_ready_item')->whereIn('paket_id', $ids)->get()->groupBy('paket_id')
            : collect();
        $linked = DigitalOrder::query()
            ->whereIn('external_order_id', $rows->pluck('no_resi')->filter())
            ->pluck('id', 'external_order_id');

        return $rows->map(function ($row) use ($items, $linked) {
            $row->items = $items->get($row->id, collect());
            $row->oms_id = $linked[$row->no_resi] ?? null;

            return $row;
        });
    }

    public function omzetRows(int $limit = 24)
    {
        if (! Schema::hasTable('mp_penjualan')) {
            return collect();
        }

        return DB::table('mp_penjualan')
            ->selectRaw('periode, marketplace, nama_toko, SUM(omzet) as omzet, SUM(qty_jual) as qty_jual')
            ->groupBy('periode', 'marketplace', 'nama_toko')
            ->orderByDesc('periode')
            ->limit($limit)
            ->get();
    }

    public function omzetTotal(): float
    {
        if (! Schema::hasTable('mp_penjualan')) {
            return 0;
        }

        return (float) DB::table('mp_penjualan')->sum('omzet');
    }

    public function pullPackingToOms(int $limit = 40): int
    {
        $companyId = (int) Company::where('type', 'digital')->value('id');
        if (! $companyId || ! Schema::hasTable('paket_ready') || ! Schema::hasTable('paket_ready_item')) {
            return 0;
        }

        $created = 0;
        $rows = DB::table('paket_ready')->orderByDesc('id')->limit($limit)->get();
        foreach ($rows as $row) {
            $resi = trim((string) $row->no_resi);
            if ($resi === '') {
                continue;
            }
            if (DigitalOrder::where('external_order_id', $resi)->exists()) {
                continue;
            }
            $items = DB::table('paket_ready_item')->where('paket_id', $row->id)->get();
            if ($items->isEmpty()) {
                continue;
            }

            $order = DigitalOrder::create([
                'company_id' => $companyId,
                'channel' => 'ONLINE',
                'external_order_id' => $resi,
                'total' => 0,
                'status' => 'completed',
            ]);
            foreach ($items as $item) {
                DigitalOrderLine::create([
                    'digital_order_id' => $order->id,
                    'kd_brg' => $item->kd_brg,
                    'qty' => $item->qty,
                ]);
            }
            $created++;
        }

        return $created;
    }
}
