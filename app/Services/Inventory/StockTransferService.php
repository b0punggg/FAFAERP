<?php

namespace App\Services\Inventory;

use App\Models\InventoryMovement;
use App\Models\StockTransferLine;
use App\Models\StockTransferRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockTransferService
{
    public function __construct(protected InventoryMovementService $movements)
    {
    }

    public function create(array $data, array $lines): StockTransferRequest
    {
        return DB::transaction(function () use ($data, $lines) {
            $str = StockTransferRequest::create([
                'str_no' => $data['str_no'] ?? ('STR-'.Str::upper(Str::random(8))),
                'from_warehouse_id' => $data['from_warehouse_id'],
                'to_warehouse_id' => $data['to_warehouse_id'],
                'status' => $data['status'] ?? 'draft',
                'requested_by' => auth()->id(),
            ]);

            foreach ($lines as $line) {
                StockTransferLine::create([
                    'stock_transfer_request_id' => $str->id,
                    'product_id' => $line['product_id'],
                    'qty' => $line['qty'],
                ]);
            }

            return $str->load('lines');
        });
    }

    public function shipExisting(StockTransferRequest $str): StockTransferRequest
    {
        if (! in_array($str->status, ['draft', 'approved'], true)) {
            throw new \InvalidArgumentException('STR tidak bisa dikirim.');
        }

        return DB::transaction(function () use ($str) {
            $locked = StockTransferRequest::lockForUpdate()->findOrFail($str->id);
            if (! in_array($locked->status, ['draft', 'approved'], true)) {
                throw new \InvalidArgumentException('STR tidak bisa dikirim.');
            }
            $locked->load('lines');
            foreach ($locked->lines as $line) {
                $need = abs((float) $line->qty);
                $onHand = $this->movements->qtyOnHand($locked->from_warehouse_id, $line->product_id);
                if ($onHand + 0.0001 < $need) {
                    throw new \InvalidArgumentException("Stok asal tidak cukup untuk STR {$locked->str_no}. Sisa {$onHand}.");
                }
            }
            foreach ($locked->lines as $line) {
                $this->movements->adjustBalance(
                    $locked->from_warehouse_id,
                    $line->product_id,
                    -abs((float) $line->qty),
                    InventoryMovement::TYPE_TRANSFER_OUT,
                    'stock_transfer',
                    (string) $locked->id
                );
            }
            $locked->update([
                'status' => 'in_transit',
                'approved_by' => auth()->id() ?: $locked->approved_by,
            ]);

            return $locked->fresh('lines');
        });
    }

    public function receive(StockTransferRequest $str): void
    {
        if ($str->status !== 'in_transit') {
            throw new \InvalidArgumentException('Barang belum dalam perjalanan.');
        }

        DB::transaction(function () use ($str) {
            $locked = StockTransferRequest::lockForUpdate()->findOrFail($str->id);
            if ($locked->status !== 'in_transit') {
                throw new \InvalidArgumentException('Barang belum dalam perjalanan.');
            }
            $locked->load('lines');
            foreach ($locked->lines as $line) {
                $this->movements->adjustBalance(
                    $locked->to_warehouse_id,
                    $line->product_id,
                    abs((float) $line->qty),
                    InventoryMovement::TYPE_TRANSFER_IN,
                    'stock_transfer',
                    (string) $locked->id
                );
            }
            $locked->update(['status' => 'received']);
        });
    }
}
