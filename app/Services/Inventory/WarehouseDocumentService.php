<?php

namespace App\Services\Inventory;

use App\Models\StockTransferRequest;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WarehouseDocumentService
{
    public function __construct(
        protected InventoryMovementService $movements,
        protected StockTransferService $transfers
    ) {
    }

    public function apply(string $idempotencyKey, string $type, array $payload): array
    {
        $existing = DB::table('warehouse_sync_events')->where('idempotency_key', $idempotencyKey)->first();
        if ($existing && $existing->status === 'applied' && $existing->result) {
            $result = json_decode($existing->result, true) ?: [];
            $result['replayed'] = true;

            return $result;
        }

        try {
            $result = DB::transaction(function () use ($idempotencyKey, $type, $payload) {
                $locked = DB::table('warehouse_sync_events')->where('idempotency_key', $idempotencyKey)->lockForUpdate()->first();
                if ($locked && $locked->status === 'applied' && $locked->result) {
                    $decoded = json_decode($locked->result, true) ?: [];
                    $decoded['replayed'] = true;

                    return $decoded;
                }

                $applied = $this->dispatch($type, $payload);
                $applied['doc_no'] = $payload['doc_no'] ?? null;
                $applied['replayed'] = false;
                $applied['idempotency_key'] = $idempotencyKey;
                $now = now();

                if ($locked) {
                    DB::table('warehouse_sync_events')->where('id', $locked->id)->update([
                        'doc_type' => $type,
                        'doc_no' => $payload['doc_no'] ?? null,
                        'payload' => json_encode($payload),
                        'status' => 'applied',
                        'result' => json_encode($applied),
                        'updated_at' => $now,
                    ]);
                } else {
                    DB::table('warehouse_sync_events')->insert([
                        'idempotency_key' => $idempotencyKey,
                        'doc_type' => $type,
                        'doc_no' => $payload['doc_no'] ?? null,
                        'payload' => json_encode($payload),
                        'status' => 'applied',
                        'result' => json_encode($applied),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                return $applied;
            });
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::withMessages(['document' => $e->getMessage()]);
        }

        return $result;
    }

    private function dispatch(string $type, array $payload): array
    {
        $lines = $payload['lines'] ?? [];
        if ($lines === []) {
            throw new \InvalidArgumentException('Dokumen tidak punya baris barang.');
        }

        return match ($type) {
            'receipt' => $this->move($payload['kd_toko'] ?? '', $lines, 1, 'purchase', 'warehouse_receipt', (string) ($payload['doc_no'] ?? '')),
            'supplier_return' => $this->move($payload['kd_toko'] ?? '', $lines, -1, 'purchase_return', 'warehouse_return', (string) ($payload['doc_no'] ?? '')),
            'adjustment' => $this->adjust($payload['kd_toko'] ?? '', $lines, (string) ($payload['doc_no'] ?? '')),
            'opname' => $this->opname($payload['kd_toko'] ?? '', $lines, (string) ($payload['doc_no'] ?? '')),
            'issue' => $this->issueOnline($payload['kd_toko'] ?? '', $lines, (string) ($payload['doc_no'] ?? '')),
            'transfer_ship' => $this->transferShip($payload),
            'transfer_receive' => $this->transferReceive($payload),
            default => throw new \InvalidArgumentException('Jenis dokumen tidak dikenal.'),
        };
    }

    private function move(string $kdToko, array $lines, int $sign, string $movementType, string $referenceType, string $docNo): array
    {
        $warehouse = $this->warehouse($kdToko);
        $out = [];
        foreach ($lines as $line) {
            $productId = $this->productId($line['kd_brg']);
            $base = $this->baseQty($line);
            if ($sign < 0) {
                $this->ensureEnough($warehouse->id, $productId, $base, $line['kd_brg'], $kdToko);
            }
            $before = $this->movements->qtyOnHand($warehouse->id, $productId);
            $balance = $this->movements->adjustBalance(
                $warehouse->id,
                $productId,
                $sign * $base,
                $movementType,
                $referenceType,
                $docNo !== '' ? $docNo : $kdToko,
                $line['kd_brg']
            );
            $out[] = $this->lineResult($line['kd_brg'], $warehouse->id, $before, (float) $balance->qty_on_hand);
        }

        return ['type' => $sign > 0 ? 'receipt' : 'supplier_return', 'lines' => $out];
    }

    private function adjust(string $kdToko, array $lines, string $docNo): array
    {
        $warehouse = $this->warehouse($kdToko);
        $out = [];
        foreach ($lines as $line) {
            $productId = $this->productId($line['kd_brg']);
            $delta = (float) ($line['qty_delta'] ?? 0);
            $base = abs($this->movements->toBaseQuantity($line['kd_brg'], abs($delta), (int) ($line['kd_sat'] ?? 0), $kdToko));
            $signed = $delta < 0 ? -$base : $base;
            if ($signed < 0) {
                $this->ensureEnough($warehouse->id, $productId, abs($signed), $line['kd_brg'], $kdToko);
            }
            $before = $this->movements->qtyOnHand($warehouse->id, $productId);
            $this->movements->adjustWarehouseQty($warehouse->id, $productId, $signed, $docNo !== '' ? $docNo : $kdToko, $line['kd_brg']);
            $out[] = $this->lineResult($line['kd_brg'], $warehouse->id, $before, $this->movements->qtyOnHand($warehouse->id, $productId));
        }

        return ['type' => 'adjustment', 'lines' => $out];
    }

    private function issueOnline(string $kdToko, array $lines, string $docNo): array
    {
        $online = (string) config('erp.online_kd_toko', 'ONLINE');
        if ($kdToko !== $online) {
            throw new \InvalidArgumentException("Packing marketplace hanya boleh memotong gudang {$online}.");
        }

        return $this->move($kdToko, $lines, -1, 'sale', 'marketplace_packing', $docNo);
    }

    private function opname(string $kdToko, array $lines, string $docNo): array
    {
        $warehouse = $this->warehouse($kdToko);
        $out = [];
        foreach ($lines as $line) {
            $productId = $this->productId($line['kd_brg']);
            $physical = $this->baseQty($line, true);
            $before = $this->movements->qtyOnHand($warehouse->id, $productId);
            $balance = $this->movements->setPhysicalCount($warehouse->id, $productId, $physical, $docNo !== '' ? $docNo : $kdToko);
            $out[] = $this->lineResult($line['kd_brg'], $warehouse->id, $before, (float) $balance->qty_on_hand);
        }

        return ['type' => 'opname', 'lines' => $out];
    }

    private function transferShip(array $payload): array
    {
        [$from, $to, $strLines] = $this->transferEnds($payload);
        $str = $this->transfers->create([
            'str_no' => $payload['doc_no'] ?? null,
            'from_warehouse_id' => $from->id,
            'to_warehouse_id' => $to->id,
            'status' => 'approved',
        ], array_map(fn ($line) => ['product_id' => $line['product_id'], 'qty' => $line['qty']], $strLines));
        $this->transfers->shipExisting($str);

        return [
            'type' => 'transfer_ship',
            'str_no' => $str->str_no,
            'str_status' => 'in_transit',
            'lines' => $this->transferQty($from, $to, $strLines),
        ];
    }

    private function transferReceive(array $payload): array
    {
        [$from, $to, $strLines] = $this->transferEnds($payload, false);
        $docNo = (string) ($payload['doc_no'] ?? '');
        $str = StockTransferRequest::where('str_no', $docNo)->first();
        if (! $str) {
            throw new \InvalidArgumentException('Pengiriman untuk dokumen ini belum ada.');
        }
        if ((int) $str->from_warehouse_id !== (int) $from->id || (int) $str->to_warehouse_id !== (int) $to->id) {
            throw new \InvalidArgumentException('Asal atau tujuan tidak sama dengan pengiriman.');
        }
        $this->transfers->receive($str);

        return [
            'type' => 'transfer_receive',
            'str_no' => $str->str_no,
            'str_status' => 'received',
            'lines' => $this->transferQty($from, $to, $strLines),
        ];
    }

    private function transferEnds(array $payload, bool $checkOrigin = true): array
    {
        $from = $this->warehouse($payload['from_kd_toko'] ?? '');
        $to = $this->warehouse($payload['to_kd_toko'] ?? '');
        if ($from->id === $to->id) {
            throw new \InvalidArgumentException('Gudang asal dan tujuan sama.');
        }

        $strLines = [];
        foreach ($payload['lines'] as $line) {
            $productId = $this->productId($line['kd_brg']);
            $base = $this->baseQty($line);
            if ($checkOrigin) {
                $this->ensureEnough($from->id, $productId, $base, $line['kd_brg'], $from->kd_toko);
            }
            $strLines[] = ['product_id' => $productId, 'qty' => $base, 'kd_brg' => $line['kd_brg']];
        }

        return [$from, $to, $strLines];
    }

    private function transferQty(Warehouse $from, Warehouse $to, array $strLines): array
    {
        $out = [];
        foreach ($strLines as $line) {
            $out[] = [
                'kd_brg' => $line['kd_brg'],
                'from_qty' => $this->movements->qtyOnHand($from->id, $line['product_id']),
                'to_qty' => $this->movements->qtyOnHand($to->id, $line['product_id']),
            ];
        }

        return $out;
    }

    private function warehouse(string $kdToko): Warehouse
    {
        $warehouse = Warehouse::where('kd_toko', $kdToko)->first();
        if (! $warehouse) {
            throw new \InvalidArgumentException("Lokasi {$kdToko} tidak ada di kamus gudang.");
        }

        return $warehouse;
    }

    private function productId(string $sku): int
    {
        $id = $this->movements->resolveProductId($sku);
        if (! $id) {
            throw new \InvalidArgumentException("SKU {$sku} belum ada di katalog FAFA.");
        }

        return $id;
    }

    private function baseQty(array $line, bool $allowZero = false): float
    {
        $qty = (float) ($line['qty'] ?? 0);
        if ($qty < 0 || (! $allowZero && $qty <= 0)) {
            throw new \InvalidArgumentException("Qty {$line['kd_brg']} harus lebih dari 0.");
        }

        return abs($this->movements->toBaseQuantity($line['kd_brg'], $qty, (int) ($line['kd_sat'] ?? 0), $line['kd_toko'] ?? null));
    }

    private function ensureEnough(int $warehouseId, int $productId, float $need, string $sku, string $kdToko): void
    {
        $onHand = $this->movements->qtyOnHand($warehouseId, $productId);
        if ($onHand + 0.0001 < $need) {
            throw new \InvalidArgumentException("Stok {$sku} di {$kdToko} tidak cukup. Sisa {$onHand}.");
        }
    }

    private function lineResult(string $sku, int $warehouseId, float $before, float $after): array
    {
        return [
            'kd_brg' => $sku,
            'warehouse_id' => $warehouseId,
            'qty_before' => $before,
            'qty_on_hand' => $after,
        ];
    }
}
