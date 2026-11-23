# Gelombang 2 — katalog, harga, owner

Master barang resmi: **`products`** (SKU unik). `mas_brg` tetap cache stok/harga lama per toko, bukan sumber nama.

## Nama dan kemasan

Satu SKU = satu nama. Prioritas isi `products`: baris `mas_brg` **PUSAT**, lalu nama terpanjang.

```bash
php artisan erp:gelombang2-sync-catalog
php artisan erp:gelombang2-sync-catalog --dry-run
```

Edit barang gerai tidak menimpa nama/kemasan katalog jika sudah terisi (kecuali sumber PUSAT).

## Harga POS

Harga jual per gerai: **`branch_product_prices`**. Kasir memakai `BranchPricingService` (lalu jatuh ke `mas_brg` jika baris harga belum ada).

Perintah di atas mengisi harga dari `mas_brg` tiap `kd_toko` → `branches`.

## Role owner (inventory)

ENUM `users.role` = `owner`. Hak sama dengan `admin` (termasuk pivot uang dan ganti lokasi). Pilih di Manajemen User.

## Role pemilik (kasir FAFA, opsional)

`pemakai.otoritas` = `8` (Pemilik). Dipetakan seperti administrator (ganti gerai, master gerai, lihat HPP).
