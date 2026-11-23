<?php

namespace App\Services\Catalog;

use App\Models\BranchProductPrice;
use App\Models\CatalogProduct;
use App\Models\InventoryBalance;
use App\Models\Product;
use App\Support\BranchContext;

class BranchPricingService
{
    public function resolveSellPrice(string $kdBrg, int $unit = 1, ?int $branchId = null): float
    {
        $branchId = $branchId ?? BranchContext::branchId();
        $product = CatalogProduct::where('sku', $kdBrg)->first();

        if ($product && $branchId) {
            $price = BranchProductPrice::where('branch_id', $branchId)
                ->where('product_id', $product->id)
                ->first();
            if ($price) {
                return $price->priceForUnit($unit);
            }
        }

        $legacy = Product::where('kd_brg', $kdBrg)
            ->when(BranchContext::kdToko(), fn ($q) => $q->where('kd_toko', BranchContext::kdToko()))
            ->first();

        if (! $legacy) {
            return 0;
        }

        return (float) match ($unit) {
            2 => $legacy->hrg_jum2,
            3 => $legacy->hrg_jum3,
            default => $legacy->hrg_jum1,
        };
    }

    public function searchForBranch(?string $term, ?int $branchId = null, int $limit = 20, bool $onlyInStock = false): array
    {
        $branchId = $branchId ?? BranchContext::branchId();
        $kdToko = BranchContext::kdToko();
        $warehouseId = BranchContext::warehouseId();

        if ($onlyInStock && ! $warehouseId) {
            return [];
        }

        if ($branchId) {
            $query = CatalogProduct::query()
                ->where('is_active', true)
                ->whereHas('branchPrices', fn ($q) => $q->where('branch_id', $branchId));

            if ($onlyInStock) {
                $query->whereHas('inventoryBalances', function ($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId)->where('qty_on_hand', '>', 0);
                });
            }

            if ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('sku', 'like', "%{$term}%")
                        ->orWhere('barcode', 'like', "%{$term}%")
                        ->orWhere('name', 'like', "%{$term}%");
                });
            }

            $priced = $query->limit($limit)->get()->map(function (CatalogProduct $p) use ($branchId) {
                $price = BranchProductPrice::where('branch_id', $branchId)
                    ->where('product_id', $p->id)
                    ->first();

                return [
                    'kd_brg' => $p->sku,
                    'kd_bar' => $p->barcode,
                    'nm_brg' => $p->name,
                    'hrg_beli' => $price?->hrg_beli ?? 0,
                    'hrg_jum1' => $price?->hrg_jum1 ?? 0,
                    'hrg_jum2' => $price?->hrg_jum2 ?? 0,
                    'hrg_jum3' => $price?->hrg_jum3 ?? 0,
                    'kd_kem1' => $p->kd_kem1 ?: 1,
                    'nm_kem1' => $p->nm_kem1 ?: 'pcs',
                    'jum_kem1' => $p->jum_kem1 ?? 1,
                    'kd_kem2' => $p->kd_kem2,
                    'nm_kem2' => $p->nm_kem2,
                    'jum_kem2' => $p->jum_kem2,
                    'kd_kem3' => $p->kd_kem3,
                    'nm_kem3' => $p->nm_kem3,
                    'jum_kem3' => $p->jum_kem3,
                    'jml_brg' => $this->qtyForProduct($p->id),
                ];
            })->all();
            if ($priced !== []) {
                return $priced;
            }
        }

        $legacyQuery = Product::query()->where('kd_toko', $kdToko);
        if ($onlyInStock) {
            $legacyQuery->whereIn('kd_brg', function ($sub) use ($warehouseId) {
                $sub->select('p.sku')
                    ->from('products as p')
                    ->join('inventory_balances as ib', 'ib.product_id', '=', 'p.id')
                    ->where('ib.warehouse_id', $warehouseId)
                    ->where('ib.qty_on_hand', '>', 0);
            });
        }
        if ($term) {
            $legacyQuery->where(function ($q) use ($term) {
                $q->where('kd_brg', 'like', "%{$term}%")
                    ->orWhere('kd_bar', 'like', "%{$term}%")
                    ->orWhere('nm_brg', 'like', "%{$term}%");
            });
        }

        return $legacyQuery->limit($limit)->get()->map(function (Product $p) {
            $row = $p->toArray();
            $productId = CatalogProduct::where('sku', $p->kd_brg)->value('id');
            $row['jml_brg'] = $productId ? $this->qtyForProduct((int) $productId) : 0;

            return $row;
        })->all();
    }

    public function saveSellPrices(string $kdBrg, float $hrgBeli, ?float $hrg1, ?float $hrg2, ?float $hrg3): void
    {
        $branchId = BranchContext::branchId();
        $product = CatalogProduct::where('sku', $kdBrg)->first();
        if ($product && $branchId && ($hrg1 > 0 || $hrg2 > 0 || $hrg3 > 0 || $hrgBeli > 0)) {
            $row = BranchProductPrice::firstOrNew([
                'branch_id' => $branchId,
                'product_id' => $product->id,
            ]);
            if ($hrgBeli > 0) {
                $row->hrg_beli = $hrgBeli;
            }
            if ($hrg1 > 0) {
                $row->hrg_jum1 = $hrg1;
            }
            if ($hrg2 > 0) {
                $row->hrg_jum2 = $hrg2;
            }
            if ($hrg3 > 0) {
                $row->hrg_jum3 = $hrg3;
            }
            $row->hrg_jum1 = $row->hrg_jum1 ?? 0;
            $row->hrg_jum2 = $row->hrg_jum2 ?? 0;
            $row->hrg_jum3 = $row->hrg_jum3 ?? 0;
            $row->hrg_beli = $row->hrg_beli ?? 0;
            $row->save();
        }

        $legacy = Product::where('kd_brg', $kdBrg)
            ->when(BranchContext::kdToko(), fn ($q) => $q->where('kd_toko', BranchContext::kdToko()))
            ->first();
        if (! $legacy) {
            return;
        }
        $dirty = [];
        if ($hrg1 > 0) {
            $dirty['hrg_jum1'] = $hrg1;
        }
        if ($hrg2 > 0) {
            $dirty['hrg_jum2'] = $hrg2;
        }
        if ($hrg3 > 0) {
            $dirty['hrg_jum3'] = $hrg3;
        }
        if ($dirty !== []) {
            $legacy->update($dirty);
        }
    }

    protected function qtyForProduct(int $productId): float
    {
        $warehouseId = BranchContext::warehouseId();
        if (! $warehouseId) {
            return 0;
        }

        return (float) InventoryBalance::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->value('qty_on_hand') ?? 0;
    }
}
