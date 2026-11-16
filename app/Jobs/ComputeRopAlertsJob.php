<?php

namespace App\Jobs;

use App\Models\Branch;
use App\Models\CatalogProduct;
use App\Models\InventoryBalance;
use App\Models\RopAlert;
use App\Models\SaleItem;
use App\Models\Warehouse;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ComputeRopAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $since = now()->subDays(30)->toDateString();

        Branch::where('is_active', true)->each(function (Branch $branch) use ($since) {
            if (! $branch->kd_toko) {
                return;
            }

            $velocity = SaleItem::query()
                ->select('kd_brg', DB::raw('SUM(jml_brg) as qty'))
                ->where('kd_toko', $branch->kd_toko)
                ->where('tgl_jual', '>=', $since)
                ->groupBy('kd_brg')
                ->get();

            $warehouseId = Warehouse::where('kd_toko', $branch->kd_toko)->value('id');

            foreach ($velocity as $row) {
                $product = CatalogProduct::where('sku', $row->kd_brg)->first();
                if (! $product || ! $warehouseId) {
                    continue;
                }

                $daily = (float) $row->qty / 30;
                $rop = $daily * 7 + 5;
                $onHand = (float) InventoryBalance::where('warehouse_id', $warehouseId)
                    ->where('product_id', $product->id)
                    ->value('qty_on_hand');

                if ($onHand <= $rop) {
                    RopAlert::updateOrCreate(
                        ['branch_id' => $branch->id, 'product_id' => $product->id],
                        [
                            'velocity_daily' => $daily,
                            'qty_on_hand' => $onHand,
                            'reorder_point' => $rop,
                            'alerted_at' => now(),
                        ]
                    );
                }
            }
        });
    }
}
