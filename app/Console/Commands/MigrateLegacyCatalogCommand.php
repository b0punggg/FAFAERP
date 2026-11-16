<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\CatalogProduct;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateLegacyCatalogCommand extends Command
{
    protected $signature = 'erp:migrate-legacy-catalog {--dedupe=barcode : barcode|sku}';

    protected $description = 'Migrate mas_brg into products, branch prices, and inventory balances';

    public function handle(): int
    {
        $dedupe = $this->option('dedupe');
        $rows = Product::orderBy('kd_brg')->orderBy('kd_toko')->get();
        $skuMap = [];

        DB::transaction(function () use ($rows, $dedupe, &$skuMap) {
            foreach ($rows as $row) {
                $key = $dedupe === 'barcode' && $row->kd_bar
                    ? 'bar:'.$row->kd_bar
                    : 'sku:'.$row->kd_brg;

                if (! isset($skuMap[$key])) {
                    $catalog = CatalogProduct::firstOrCreate(
                        ['sku' => $row->kd_brg],
                        [
                            'barcode' => $row->kd_bar ?: null,
                            'name' => $row->nm_brg,
                            'kd_kem1' => $row->kd_kem1 ?: 1,
                            'nm_kem1' => $row->nm_kem1,
                            'jum_kem1' => $row->jum_kem1,
                            'kd_kem2' => $row->kd_kem2 ?: null,
                            'nm_kem2' => $row->nm_kem2 ?: null,
                            'jum_kem2' => $row->jum_kem2 ?: null,
                            'kd_kem3' => $row->kd_kem3 ?: null,
                            'nm_kem3' => $row->nm_kem3 ?: null,
                            'jum_kem3' => $row->jum_kem3 ?: null,
                        ]
                    );
                    $skuMap[$key] = $catalog->id;
                }

                $productId = $skuMap[$key];
                $branch = Branch::where('kd_toko', $row->kd_toko)->first();
                if (! $branch) {
                    continue;
                }

                BranchProductPrice::updateOrCreate(
                    ['branch_id' => $branch->id, 'product_id' => $productId],
                    [
                        'hrg_jum1' => $row->hrg_jum1,
                        'hrg_jum2' => $row->hrg_jum2,
                        'hrg_jum3' => $row->hrg_jum3,
                    ]
                );

                $warehouse = Warehouse::where('branch_id', $branch->id)->first();
                if ($warehouse) {
                    InventoryBalance::updateOrCreate(
                        ['warehouse_id' => $warehouse->id, 'product_id' => $productId],
                        ['qty_on_hand' => $row->jml_brg]
                    );
                }
            }
        });

        $this->info('Legacy catalog migration completed. Products: '.CatalogProduct::count());

        return self::SUCCESS;
    }
}
