<?php

namespace App\Services\Sales;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\InventoryMovement;
use App\Services\Finance\PostingService;
use App\Services\Inventory\InventoryMovementService;
use App\Support\AuditLogger;
use App\Support\BranchContext;
use Illuminate\Support\Facades\DB;

class SaleVoidService
{
    public function __construct(
        protected InventoryMovementService $inventory,
        protected PostingService $posting
    ) {
    }

    public function voidApproved(Sale $sale, string $reason, ?int $approvedBy = null): void
    {
        $kdToko = $sale->kd_toko ?: BranchContext::kdToko();
        $items = SaleItem::where('no_fak', $sale->no_fak)
            ->where('tgl_jual', $sale->tgl_jual)
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->get();

        DB::transaction(function () use ($sale, $items, $kdToko, $reason, $approvedBy) {
            $this->posting->voidReference(Sale::class, (string) $sale->no_urut);

            DB::table('sale_deletion_history')->insert([
                'kd_toko' => $kdToko,
                'no_fak' => $sale->no_fak,
                'tgl_jual' => $sale->tgl_jual,
                'tot_bayar' => $sale->tot_bayar,
                'snapshot' => json_encode([
                    'header' => $sale->toArray(),
                    'items' => $items->toArray(),
                ]),
                'reason' => $reason,
                'voided_by' => auth()->id(),
                'approved_by' => $approvedBy,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($items as $item) {
                $this->inventory->restoreForSaleLine(
                    $item->kd_brg,
                    (float) $item->jml_brg,
                    (int) $item->kd_sat,
                    $kdToko,
                    'void:'.$sale->no_fak,
                    InventoryMovement::TYPE_SALE_VOID,
                    'jual_void'
                );
            }

            SaleItem::where('no_fak', $sale->no_fak)
                ->where('tgl_jual', $sale->tgl_jual)
                ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
                ->delete();

            AuditLogger::log($sale, 'voided', $sale->toArray(), ['reason' => $reason]);
            $sale->delete();
        });
    }
}
