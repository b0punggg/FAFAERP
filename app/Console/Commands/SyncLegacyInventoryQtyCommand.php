<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\CatalogProduct;
use App\Models\InventoryBalance;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncLegacyInventoryQtyCommand extends Command
{
    protected $signature = 'inventory:sync-legacy-qty {--kd-toko= : Sync one gerai only}';

    protected $description = 'Set inventory_balances qty from mas_brg.jml_brg per gerai; tidak mengisi gudang pusat';

    public function handle(): int
    {
        if (! Schema::hasTable('mas_brg')) {
            $this->error('Tabel mas_brg belum ada.');

            return self::FAILURE;
        }

        $kdTokoFilter = $this->option('kd-toko');
        $skipCodes = [
            (string) config('erp.central_kd_toko', 'PUSAT'),
            (string) config('erp.kanvas_kd_toko', 'KANVAS'),
            (string) config('erp.online_kd_toko', 'ONLINE'),
        ];

        $query = DB::table('mas_brg')->select('kd_toko', 'kd_brg', 'jml_brg');
        if ($kdTokoFilter) {
            $query->where('kd_toko', $kdTokoFilter);
        } else {
            $query->whereNotIn('kd_toko', $skipCodes);
        }

        $updated = 0;
        $skippedNoBranch = 0;
        $skippedNoSku = 0;

        foreach ($query->cursor() as $row) {
            if (in_array((string) $row->kd_toko, $skipCodes, true)) {
                continue;
            }

            $branch = Branch::where('kd_toko', $row->kd_toko)->first();
            if (! $branch) {
                $skippedNoBranch++;
                continue;
            }
            $warehouse = Warehouse::where('branch_id', $branch->id)->first();
            if (! $warehouse || $warehouse->is_central) {
                $skippedNoBranch++;
                continue;
            }
            $productId = CatalogProduct::where('sku', $row->kd_brg)->value('id');
            if (! $productId) {
                $skippedNoSku++;
                continue;
            }

            InventoryBalance::updateOrCreate(
                ['warehouse_id' => $warehouse->id, 'product_id' => $productId],
                ['qty_on_hand' => (float) $row->jml_brg]
            );
            $updated++;
        }

        $this->info("Synced {$updated} baris SKU/gerai dari mas_brg ke inventory_balances.");
        if ($skippedNoBranch) {
            $this->warn("Lewati {$skippedNoBranch} baris tanpa branch/gudang gerai (termasuk PUSAT).");
        }
        if ($skippedNoSku) {
            $this->warn("Lewati {$skippedNoSku} baris tanpa products.sku — jalankan erp:sync-stage0 dulu.");
        }

        return self::SUCCESS;
    }
}
