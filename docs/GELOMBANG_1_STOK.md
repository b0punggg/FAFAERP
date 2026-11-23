# Gelombang 1 — stok satu sumber

Angka resmi per SKU per lokasi: **`inventory_balances.qty_on_hand`**.

## Pintu tulis

Semua jual, beli, retur, opname, SJ, STR, packing lewat `InventoryMovementService` (kasir) atau API `POST /api/v1/warehouse/documents` (inventory).

`mas_brg.jml_brg` hanya **cache tampilan**. Disalin dari ERP setiap mutasi, atau:

```bash
php artisan erp:gelombang1-reconcile --sync-cache
```

HPP tetap FIFO `beli_brg.stok_jual` (bukan qty rak).

## Opname

| Lokasi | Di mana | Efek |
|--------|---------|------|
| Gerai FAFA 1–4 | Kasir Laravel Stok Opname | Hanya gudang gerai itu |
| PUSAT | Inventory → Opname Pusat | Hanya gudang PUSAT |
| ONLINE | Packing / mutasi ONLINE | Bukan gerai |

Jangan menyalin `jml_brg` gerai ke PUSAT.

## Cek selisih

```bash
php artisan erp:gelombang1-reconcile
```

Kolom `erp` = resmi. `cache_mas_brg` harus ikut. `fifo_sisa` boleh beda (lot HPP).

## POS

Pencarian barang di kasir memakai qty gudang gerai (`inventory_balances`), bukan angka master yang usang.
