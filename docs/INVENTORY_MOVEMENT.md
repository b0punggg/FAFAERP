# Inventory movement (Gelombang A0)

## Service

`App\Services\Inventory\InventoryMovementService` — satu pintu perubahan qty ERP:

| Method | Legacy | ERP |
|--------|--------|-----|
| `receivePurchaseLine` | `mas_brg.jml_brg`, `beli_brg.stok_jual` (via form) | `inventory_balances` + log |
| `issueForSaleLine` | FIFO `beli_brg.stok_jual` | `inventory_balances` (base unit) |
| `setPhysicalCount` | — | opname selisih |
| `transferLine` | — | STR receive |

**HPP / GL:** costing tetap FIFO batch pembelian (`beli_brg`); jurnal lewat `PostingService` pada header jual/beli.

**Satuan:** qty di `inventory_balances` = **base unit (kem1)**; konversi dari `kd_sat` lewat data kemasan produk.

## Audit

Tabel `inventory_movements` (type, qty_delta, reference).

## Reconcile

Jika qty ERP drift dari `mas_brg` setelah migrasi data:

```bash
php artisan inventory:sync-legacy-qty
php artisan inventory:sync-legacy-qty --kd-toko=001
```

## Konteks wajib

Login harus mengisi `BranchContext::warehouseId()` (middleware login / `SetCompanyContext`). Tanpa warehouse, pembelian/penjualan tetap update legacy; ERP qty tidak berubah.
