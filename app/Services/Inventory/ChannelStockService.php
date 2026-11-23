<?php

namespace App\Services\Inventory;

use App\Models\Company;
use App\Models\DigitalOrder;
use App\Models\InventoryMovement;
use App\Models\SfaOrder;
use App\Models\Warehouse;
use App\Services\Finance\PostingService;
use Illuminate\Support\Facades\DB;

class ChannelStockService
{
    public function __construct(
        protected InventoryMovementService $movements,
        protected PostingService $posting
    ) {
    }

    public function shipDigital(DigitalOrder $order): bool
    {
        return DB::transaction(function () use ($order) {
            $locked = DigitalOrder::lockForUpdate()->findOrFail($order->id);
            if (in_array($locked->status, ['shipped', 'completed', 'cancelled'], true)) {
                return false;
            }

            $warehouse = $this->warehouseByCode('CENTRAL');
            $locked->load('lines');
            $this->issueLines($warehouse, $locked->lines, 'digital_order', (string) $locked->id);
            if ((int) $locked->company_id !== (int) $warehouse->company_id) {
                $this->posting->postDigitalIntercompany(
                    (int) $locked->company_id,
                    (int) $warehouse->company_id,
                    (float) $locked->total,
                    (string) $locked->id,
                    now()->toDateString()
                );
            }
            $locked->update(['status' => 'shipped']);

            return true;
        });
    }

    public function deliverFieldOrder(SfaOrder $order): bool
    {
        return DB::transaction(function () use ($order) {
            $locked = SfaOrder::lockForUpdate()->findOrFail($order->id);
            if ($locked->status === 'delivered') {
                return false;
            }
            if ($locked->status === 'credit_hold') {
                throw new \InvalidArgumentException('Order TO masih credit hold. Stok kanvas tidak berkurang.');
            }
            if ($locked->status !== 'approved') {
                throw new \InvalidArgumentException('Order TO belum disetujui.');
            }

            $warehouse = $this->warehouseByCode('KANVAS');
            $locked->load('lines');
            $this->issueLines($warehouse, $locked->lines, 'sfa_order', (string) $locked->id);
            $retailId = (int) Company::where('type', 'retail')->value('id');
            $toId = (int) Company::where('type', 'field_sales')->value('id');
            if ($retailId && $toId && $retailId !== $toId) {
                $this->posting->postFieldSalesIntercompany(
                    $toId,
                    $retailId,
                    (float) $locked->total,
                    (string) $locked->id,
                    now()->toDateString()
                );
            }
            $locked->update(['status' => 'delivered']);

            return true;
        });
    }

    private function warehouseByCode(string $code): Warehouse
    {
        $centralKd = (string) config('erp.central_kd_toko', 'PUSAT');
        $kanvasKd = (string) config('erp.kanvas_kd_toko', 'KANVAS');

        $query = Warehouse::query();
        if ($code === 'CENTRAL') {
            $warehouse = $query->where(function ($inner) use ($centralKd) {
                $inner->where('code', 'CENTRAL')
                    ->orWhere('kd_toko', $centralKd)
                    ->orWhere('is_central', true);
            })->whereNull('branch_id')->first();
        } elseif ($code === 'KANVAS') {
            $warehouse = $query->where(function ($inner) use ($kanvasKd) {
                $inner->where('code', 'KANVAS')->orWhere('kd_toko', $kanvasKd);
            })->first();
        } else {
            $warehouse = $query->where(function ($inner) use ($code) {
                $inner->where('code', $code)->orWhere('kd_toko', $code);
            })->first();
        }

        if (! $warehouse) {
            throw new \InvalidArgumentException("Gudang {$code} belum ada.");
        }

        return $warehouse;
    }

    private function issueLines(Warehouse $warehouse, $lines, string $referenceType, string $referenceId): void
    {
        if ($lines->isEmpty()) {
            throw new \InvalidArgumentException('Order tidak punya barang.');
        }

        foreach ($lines as $line) {
            $productId = $this->movements->resolveProductId($line->kd_brg);
            if (! $productId) {
                throw new \InvalidArgumentException("SKU {$line->kd_brg} belum ada di katalog FAFA.");
            }
            $base = abs($this->movements->toBaseQuantity($line->kd_brg, (float) $line->qty, 0, $warehouse->kd_toko));
            if ($base <= 0) {
                throw new \InvalidArgumentException("Qty {$line->kd_brg} harus lebih dari 0.");
            }
            $onHand = $this->movements->qtyOnHand($warehouse->id, $productId);
            if ($onHand + 0.0001 < $base) {
                throw new \InvalidArgumentException("Stok {$line->kd_brg} di {$warehouse->kd_toko} tidak cukup. Sisa {$onHand}.");
            }
            $this->movements->adjustBalance(
                $warehouse->id,
                $productId,
                -$base,
                InventoryMovement::TYPE_SALE,
                $referenceType,
                $referenceId,
                $line->kd_brg
            );
        }
    }
}
