# Gelombang 3 — siklus harian + GL

Alur: **beli → stok → jual → kas/piutang → tutup toko → laporan + jurnal**.

## Edit pembelian

`purchases/{id}/edit` mengubah header/baris, menyesuaikan lot FIFO, lalu **mengganti jurnal** pembelian (jurnal lama di-void).

## Cetak nota

Dari detail penjualan:

- thermal (default)
- A4 (`?format=a4`)
- Member jika ada `kd_member`
- Crew jika ada `kd_crew`

## Void / hapus

Ajukan void di detail nota → approval → stok kembali, jurnal penjualan di-void, salinan di **History hapus nota**.

## Jurnal wajib

`PostingService` menulis jurnal posted untuk:

| Transaksi | Akun |
|-----------|------|
| Jual | Kas/QRIS/EDC + piutang / penjualan + PPN |
| Beli | Persediaan + PPN masukan / kas + hutang |
| Kas masuk/keluar | Kas lawan pendapatan lain / beban |
| Retur jual/beli | Kebalikan pendapatan atau hutang–persediaan |
| Bayar piutang | Kas / piutang |
| Bayar hutang | Hutang / kas |

Edit beli atau void jual tidak menumpuk jurnal: referensi yang sama di-void dulu.

## Trial balance

`/finance/trial-balance` → klik kode akun → baris jurnal + tautan nota.

## Tutup toko

Menu **Tutup toko / settlement**. Kas POS, QRIS, EDC dihitung dari nota hari itu. Kasir isi fisik laci. Kepala toko menyetujui.

POS offline (`mobile/pos`) ditunda jika gerai jarang putus jaringan.

## Cek

```bash
php artisan erp:gelombang3-audit
php artisan erp:gelombang3-audit --backfill
```
