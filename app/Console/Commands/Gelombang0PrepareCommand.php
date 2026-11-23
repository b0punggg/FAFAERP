<?php

namespace App\Console\Commands;

use App\Models\InventoryBalance;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Gelombang0PrepareCommand extends Command
{
    protected $signature = 'erp:gelombang0-prepare
                            {--sync-qty : Isi inventory_balances gerai dari mas_brg.jml_brg}
                            {--zero-central : Nolkan qty gudang PUSAT (bukan gerai)}';

    protected $description = 'Gelombang 0: cek toko/gudang, sync stok gerai, nolkan PUSAT';

    public function handle(): int
    {
        $centralKd = (string) config('erp.central_kd_toko', 'PUSAT');

        $this->info('Freeze gerai boleh jam tutup berbeda — lihat docs/GELOMBANG_0_RUNBOOK.md');
        $this->newLine();

        $special = [
            $centralKd => 'PUSAT',
            (string) config('erp.kanvas_kd_toko', 'KANVAS') => 'kanvas',
            (string) config('erp.online_kd_toko', 'ONLINE') => 'online',
        ];

        if (Schema::hasTable('toko')) {
            $toko = DB::table('toko')->orderBy('kd_toko')->get(['kd_toko', 'nm_toko']);
            $this->table(
                ['kd_toko', 'nm_toko', 'jenis'],
                $toko->map(fn ($row) => [
                    $row->kd_toko,
                    $row->nm_toko,
                    $special[$row->kd_toko] ?? 'gerai',
                ])
            );
        } else {
            $this->warn('Tabel toko belum ada — import dump merge dulu.');
        }

        if (Schema::hasTable('warehouses')) {
            $rows = Warehouse::query()->orderBy('is_central', 'desc')->orderBy('kd_toko')->get();
            $this->table(
                ['id', 'kd_toko', 'code', 'pusat', 'branch_id', 'qty_sku'],
                $rows->map(function (Warehouse $w) {
                    return [
                        $w->id,
                        $w->kd_toko,
                        $w->code,
                        $w->is_central ? 'ya' : '',
                        $w->branch_id,
                        InventoryBalance::where('warehouse_id', $w->id)->count(),
                    ];
                })
            );
        }

        if ($this->option('sync-qty')) {
            $code = $this->call('inventory:sync-legacy-qty');
            if ($code !== self::SUCCESS) {
                return $code;
            }
        }

        if ($this->option('zero-central')) {
            $centralIds = Warehouse::query()->where('is_central', true)->pluck('id');
            $n = InventoryBalance::whereIn('warehouse_id', $centralIds)->update(['qty_on_hand' => 0]);
            $this->info("Qty PUSAT di-nolkan pada {$n} baris inventory_balances. Isi dengan opname, bukan stok gerai.");
        }

        return self::SUCCESS;
    }
}
