<?php

namespace App\Services\Inventory;

use App\Models\CatalogProduct;
use App\Models\InventoryBalance;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use App\Support\BranchContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Single entry point for stock changes (ERP qty in base unit on inventory_balances).
 *
 * Costing (HPP) for GL remains legacy FIFO on beli_brg.stok_jual — issueForSale() decrements
 * those batches in document unit; inventory_balances always uses base-unit qty.
 */
class InventoryMovementService
{
    public function resolveProductId(string $kdBrg): ?int
    {
        return CatalogProduct::where('sku', $kdBrg)->value('id');
    }

    public function warehouseIdOrFail(): int
    {
        $warehouseId = BranchContext::warehouseId();
        if (! $warehouseId) {
            throw new \RuntimeException('Warehouse context belum diset. Login ulang atau pilih gerai.');
        }

        return $warehouseId;
    }

    /**
     * Convert line qty (purchase/sale unit) to base unit (kem1) for inventory_balances.
     */
    public function toBaseQuantity(string $kdBrg, float $qty, int $unit, ?string $kdToko = null): float
    {
        $catalog = CatalogProduct::where('sku', $kdBrg)->first();
        if ($catalog) {
            return $this->convertWithPackaging($qty, $unit, $catalog);
        }

        $legacy = Product::where('kd_brg', $kdBrg)
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->first();

        if ($legacy) {
            return $this->convertWithPackaging($qty, $unit, $legacy);
        }

        return $qty;
    }

    protected function convertWithPackaging(float $qty, int $unit, CatalogProduct|Product $row): float
    {
        $kem3 = (int) ($row->kd_kem3 ?? 0);
        $kem2 = (int) ($row->kd_kem2 ?? 0);

        if ($kem3 && $unit === $kem3 && (float) ($row->jum_kem3 ?? 0) > 0) {
            return $qty * (float) $row->jum_kem3;
        }
        if ($kem2 && $unit === $kem2 && (float) ($row->jum_kem2 ?? 0) > 0) {
            return $qty * (float) $row->jum_kem2;
        }

        return $qty;
    }

    public function qtyOnHand(?int $warehouseId, int $productId): float
    {
        if (! $warehouseId) {
            return 0;
        }

        return (float) InventoryBalance::where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->value('qty_on_hand');
    }

    public function qtyOnHandBySku(string $kdBrg, ?int $warehouseId = null): float
    {
        $productId = $this->resolveProductId($kdBrg);
        if (! $productId) {
            return 0;
        }

        return $this->qtyOnHand($warehouseId ?? BranchContext::warehouseId(), $productId);
    }

    /**
     * @param  float  $delta  Base-unit quantity (positive = in, negative = out)
     */
    public function adjustBalance(
        int $warehouseId,
        int $productId,
        float $delta,
        string $movementType,
        ?string $referenceType = null,
        ?string $referenceId = null,
        ?string $sku = null
    ): InventoryBalance {
        return DB::transaction(function () use (
            $warehouseId,
            $productId,
            $delta,
            $movementType,
            $referenceType,
            $referenceId,
            $sku
        ) {
            $balance = InventoryBalance::lockForUpdate()->firstOrCreate(
                ['warehouse_id' => $warehouseId, 'product_id' => $productId],
                ['qty_on_hand' => 0]
            );

            $balance->qty_on_hand = max(0, (float) $balance->qty_on_hand + $delta);
            $balance->save();
            $this->syncMasBrgCache($warehouseId, $productId, $sku, (float) $balance->qty_on_hand);

            if (Schema::hasTable('inventory_movements')) {
                InventoryMovement::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'movement_type' => $movementType,
                    'qty_delta' => $delta,
                    'qty_on_hand_after' => $balance->qty_on_hand,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'sku' => $sku,
                ]);
            }

            return $balance;
        });
    }

    public function setPhysicalCount(
        int $warehouseId,
        int $productId,
        float $qtyPhysical,
        ?string $referenceId = null
    ): InventoryBalance {
        $current = $this->qtyOnHand($warehouseId, $productId);
        $delta = $qtyPhysical - $current;

        if (abs($delta) < 0.0001) {
            $balance = InventoryBalance::firstOrCreate(
                ['warehouse_id' => $warehouseId, 'product_id' => $productId],
                ['qty_on_hand' => $qtyPhysical]
            );
            $this->syncMasBrgCache($warehouseId, $productId, null, (float) $balance->qty_on_hand);

            return $balance;
        }

        return $this->adjustBalance(
            $warehouseId,
            $productId,
            $delta,
            InventoryMovement::TYPE_OPNAME,
            'stok_opname',
            $referenceId
        );
    }

    /**
     * Pembelian: legacy mas_brg + ERP inventory (base unit).
     */
    public function receivePurchaseLine(
        string $kdBrg,
        float $qty,
        int $kdSat,
        string $kdToko,
        ?string $referenceId = null
    ): void {
        $product = Product::where('kd_brg', $kdBrg)->where('kd_toko', $kdToko)->first();
        if ($product) {
            $product->increment('brg_msk', $qty);
        }

        $productId = $this->resolveProductId($kdBrg);
        $warehouseId = $this->warehouseIdForToko($kdToko);
        if (! $productId || ! $warehouseId) {
            return;
        }

        $baseQty = $this->toBaseQuantity($kdBrg, $qty, $kdSat, $kdToko);
        $this->adjustBalance(
            $warehouseId,
            $productId,
            $baseQty,
            InventoryMovement::TYPE_PURCHASE,
            'beli',
            $referenceId,
            $kdBrg
        );
    }

    public function applyPurchaseDelta(
        string $kdBrg,
        float $docDelta,
        float $baseDelta,
        string $kdToko,
        ?string $referenceId = null
    ): void {
        if (abs($docDelta) < 0.00001 && abs($baseDelta) < 0.00001) {
            return;
        }

        $product = Product::where('kd_brg', $kdBrg)->where('kd_toko', $kdToko)->first();
        if ($product && abs($docDelta) >= 0.00001) {
            if ($docDelta > 0) {
                $product->increment('brg_msk', $docDelta);
            } else {
                $cutIn = min(abs($docDelta), (float) $product->brg_msk);
                if ($cutIn > 0) {
                    $product->decrement('brg_msk', $cutIn);
                }
            }
        }

        $productId = $this->resolveProductId($kdBrg);
        $warehouseId = $this->warehouseIdForToko($kdToko);
        if (! $productId || ! $warehouseId || abs($baseDelta) < 0.00001) {
            return;
        }

        $this->adjustBalance(
            $warehouseId,
            $productId,
            $baseDelta,
            InventoryMovement::TYPE_PURCHASE,
            'beli',
            $referenceId,
            $kdBrg
        );
    }

    /**
     * Penjualan: FIFO beli_brg (legacy HPP) + ERP inventory out (base unit).
     */
    public function issuePurchaseReturnLine(
        string $kdBrg,
        float $qty,
        int $kdSat,
        string $kdToko,
        ?string $referenceId = null
    ): void {
        $this->deductLegacyFifo($kdBrg, $qty, $kdToko);

        $productId = $this->resolveProductId($kdBrg);
        $warehouseId = $this->warehouseIdForToko($kdToko);
        if (! $productId || ! $warehouseId) {
            return;
        }

        $baseQty = $this->toBaseQuantity($kdBrg, $qty, $kdSat, $kdToko);
        $this->adjustBalance(
            $warehouseId,
            $productId,
            -abs($baseQty),
            InventoryMovement::TYPE_PURCHASE_RETURN,
            'retur_beli',
            $referenceId,
            $kdBrg
        );
    }

    public function restoreForSaleLine(
        string $kdBrg,
        float $qty,
        int $kdSat,
        string $kdToko,
        ?string $referenceId = null,
        string $movementType = InventoryMovement::TYPE_SALE_RETURN,
        string $referenceType = 'retur_jual'
    ): void {
        $this->restoreLegacyFifo($kdBrg, $qty, $kdToko);

        $productId = $this->resolveProductId($kdBrg);
        $warehouseId = $this->warehouseIdForToko($kdToko);
        if (! $productId || ! $warehouseId) {
            return;
        }

        $baseQty = $this->toBaseQuantity($kdBrg, $qty, $kdSat, $kdToko);
        $this->adjustBalance(
            $warehouseId,
            $productId,
            abs($baseQty),
            $movementType,
            $referenceType,
            $referenceId,
            $kdBrg
        );
    }

    public function adjustWarehouseQty(
        int $warehouseId,
        int $productId,
        float $baseQtyDelta,
        string $referenceId,
        ?string $sku = null
    ): void {
        if (abs($baseQtyDelta) < 0.0001) {
            return;
        }

        $this->adjustBalance(
            $warehouseId,
            $productId,
            $baseQtyDelta,
            InventoryMovement::TYPE_ADJUSTMENT,
            'stock_adjustment',
            $referenceId,
            $sku
        );
    }

    public function issueForSaleLine(
        string $kdBrg,
        float $qty,
        int $kdSat,
        string $kdToko,
        ?string $referenceId = null
    ): void {
        $this->deductLegacyFifo($kdBrg, $qty, $kdToko);

        $productId = $this->resolveProductId($kdBrg);
        $warehouseId = $this->warehouseIdForToko($kdToko);
        if (! $productId || ! $warehouseId) {
            return;
        }

        $baseQty = $this->toBaseQuantity($kdBrg, $qty, $kdSat, $kdToko);
        $this->adjustBalance(
            $warehouseId,
            $productId,
            -abs($baseQty),
            InventoryMovement::TYPE_SALE,
            'jual',
            $referenceId,
            $kdBrg
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function assertSaleQuantities(array $items, ?string $kdToko): void
    {
        $warehouseId = $this->warehouseIdForToko($kdToko);
        if (! $warehouseId) {
            throw new \InvalidArgumentException('Gudang gerai belum dipilih.');
        }

        $need = [];
        $names = [];
        foreach ($items as $item) {
            $sku = (string) ($item['kd_brg'] ?? '');
            if ($sku === '') {
                continue;
            }
            $base = $this->toBaseQuantity(
                $sku,
                (float) ($item['jml_brg'] ?? 0),
                (int) ($item['kd_sat'] ?? 1),
                $kdToko
            );
            $need[$sku] = ($need[$sku] ?? 0) + $base;
            if (($names[$sku] ?? '') === '' && ! empty($item['nm_brg'])) {
                $names[$sku] = (string) $item['nm_brg'];
            }
        }

        foreach ($need as $sku => $qty) {
            $productId = $this->resolveProductId($sku);
            $onHand = 0.0;
            if ($productId) {
                $balance = InventoryBalance::lockForUpdate()
                    ->where('warehouse_id', $warehouseId)
                    ->where('product_id', $productId)
                    ->first();
                $onHand = (float) ($balance->qty_on_hand ?? 0);
            }
            if ($qty - $onHand > 0.0001) {
                $left = rtrim(rtrim(number_format($onHand, 2, ',', '.'), '0'), ',');
                $label = ($names[$sku] ?? '') !== '' ? $names[$sku] : $sku;
                throw new \InvalidArgumentException('Stok '.$label.' hanya tersisa '.$left.'.');
            }
        }
    }

    /**
     * mas_brg.jml_brg = cache tampilan, disalin dari inventory_balances.
     */
    protected function syncMasBrgCache(int $warehouseId, int $productId, ?string $sku, float $qtyOnHand): void
    {
        if (! Schema::hasTable('mas_brg')) {
            return;
        }
        $kdToko = Warehouse::where('id', $warehouseId)->value('kd_toko');
        $code = $sku ?: CatalogProduct::where('id', $productId)->value('sku');
        if (! $kdToko || ! $code) {
            return;
        }
        DB::table('mas_brg')
            ->where('kd_toko', $kdToko)
            ->where('kd_brg', $code)
            ->update(['jml_brg' => $qtyOnHand]);
    }

    protected function warehouseIdForToko(?string $kdToko): ?int
    {
        if (! $kdToko) {
            return null;
        }

        $sessionId = BranchContext::warehouseId();
        if ($sessionId && Warehouse::where('id', $sessionId)->where('kd_toko', $kdToko)->exists()) {
            return $sessionId;
        }

        $id = Warehouse::where('kd_toko', $kdToko)->value('id');

        return $id ? (int) $id : null;
    }

    protected function deductLegacyFifo(string $kdBrg, float $qty, string $kdToko): void
    {
        $purchaseItems = DB::table('beli_brg')
            ->where('kd_brg', $kdBrg)
            ->where('kd_toko', $kdToko)
            ->where('stok_jual', '>', 0)
            ->orderBy('tgl_fak', 'asc')
            ->get();

        $remaining = abs($qty);

        foreach ($purchaseItems as $item) {
            if ($remaining <= 0) {
                break;
            }

            $deduct = min($remaining, (float) $item->stok_jual);
            DB::table('beli_brg')
                ->where('no_urut', $item->no_urut)
                ->decrement('stok_jual', $deduct);

            $remaining -= $deduct;
        }
    }

    protected function restoreLegacyFifo(string $kdBrg, float $qty, string $kdToko): void
    {
        $item = DB::table('beli_brg')
            ->where('kd_brg', $kdBrg)
            ->where('kd_toko', $kdToko)
            ->orderByDesc('tgl_fak')
            ->first();

        if ($item) {
            DB::table('beli_brg')
                ->where('no_urut', $item->no_urut)
                ->increment('stok_jual', abs($qty));
        }
    }

    public function transferLine(
        int $fromWarehouseId,
        int $toWarehouseId,
        int $productId,
        float $qtyBase,
        string $referenceId
    ): void {
        $this->adjustBalance(
            $fromWarehouseId,
            $productId,
            -abs($qtyBase),
            InventoryMovement::TYPE_TRANSFER_OUT,
            'stock_transfer',
            $referenceId
        );
        $this->adjustBalance(
            $toWarehouseId,
            $productId,
            abs($qtyBase),
            InventoryMovement::TYPE_TRANSFER_IN,
            'stock_transfer',
            $referenceId
        );
    }

    /** Dashboard / report aggregates when warehouse context is set. */
    public function stockSummaryForWarehouse(int $warehouseId): array
    {
        $rows = InventoryBalance::query()
            ->where('warehouse_id', $warehouseId)
            ->where('qty_on_hand', '>', 0)
            ->get(['product_id', 'qty_on_hand']);

        $withStock = $rows->count();
        $zeroCount = CatalogProduct::where('is_active', true)
            ->whereDoesntHave('inventoryBalances', fn ($q) => $q->where('warehouse_id', $warehouseId)->where('qty_on_hand', '>', 0))
            ->count();

        $lowCount = $rows->where('qty_on_hand', '>', 0)->where('qty_on_hand', '<=', 5)->count();

        return [
            'sku_with_stock' => $withStock,
            'zero_stock_skus' => $zeroCount,
            'low_stock_skus' => $lowCount,
        ];
    }

    public function zeroStockList(int $warehouseId, int $limit = 10): Collection
    {
        return InventoryBalance::with('product')
            ->where('warehouse_id', $warehouseId)
            ->where('qty_on_hand', '<=', 0)
            ->limit($limit)
            ->get()
            ->map(fn ($b) => (object) [
                'kd_brg' => $b->product?->sku,
                'nm_brg' => $b->product?->name,
                'stok' => (float) $b->qty_on_hand,
            ]);
    }

    public function lowStockList(int $warehouseId, int $limit = 10): Collection
    {
        return InventoryBalance::with('product')
            ->where('warehouse_id', $warehouseId)
            ->where('qty_on_hand', '>', 0)
            ->where('qty_on_hand', '<=', 5)
            ->orderBy('qty_on_hand')
            ->limit($limit)
            ->get()
            ->map(fn ($b) => (object) [
                'kd_brg' => $b->product?->sku,
                'nm_brg' => $b->product?->name,
                'stok' => (float) $b->qty_on_hand,
            ]);
    }

    public function stockReportQuery(int $warehouseId, string $filter = 'all')
    {
        $query = InventoryBalance::query()
            ->with('product')
            ->where('warehouse_id', $warehouseId);

        if ($filter === 'empty') {
            $query->where('qty_on_hand', '<=', 0);
        } elseif ($filter === 'low') {
            $query->where('qty_on_hand', '>', 0)->where('qty_on_hand', '<=', 5);
        } elseif ($filter === 'available') {
            $query->where('qty_on_hand', '>', 0);
        }

        return $query
            ->with('product')
            ->join('products', 'products.id', '=', 'inventory_balances.product_id')
            ->select('inventory_balances.*')
            ->orderBy('products.name');
    }
}
