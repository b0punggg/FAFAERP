<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\CatalogProduct;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Console\Command;

class SyncLegacyInventoryQtyCommand extends Command
{
    protected $signature = 'inventory:sync-legacy-qty {--kd-toko= : Sync one branch only}';

    protected $description = 'Set inventory_balances qty from mas_brg.jml_brg per gerai (reconcile ERP vs legacy display)';

    public function handle(): int
    {
        $kdTokoFilter = $this->option('kd-toko');

        $query = Product::query()->select('kd_toko', 'kd_brg', 'jml_brg');
        if ($kdTokoFilter) {
            $query->where('kd_toko', $kdTokoFilter);
        }

        $updated = 0;
        foreach ($query->cursor() as $row) {
            $branch = Branch::where('kd_toko', $row->kd_toko)->first();
            if (! $branch) {
                continue;
            }
            $warehouse = Warehouse::where('branch_id', $branch->id)->first();
            if (! $warehouse) {
                continue;
            }
            $productId = CatalogProduct::where('sku', $row->kd_brg)->value('id');
            if (! $productId) {
                continue;
            }

            InventoryBalance::updateOrCreate(
                ['warehouse_id' => $warehouse->id, 'product_id' => $productId],
                ['qty_on_hand' => (float) $row->jml_brg]
            );
            $updated++;
        }

        $this->info("Synced {$updated} SKU/gerai rows from mas_brg into inventory_balances.");

        return self::SUCCESS;
    }
}
