# Gelombang 5 — Digital, TO, EIS

Kerjakan setelah Gelombang 0–3. Menu skeleton tidak dihitung selesai.

## Digital OMS

Alur order di `/digital/orders`:

1. Catat order (SKU katalog) status menunggu kirim.
2. **Kirim dari pusat** memotong `inventory_balances` gudang CENTRAL/PUSAT (bukan gerai 1–4) dan menulis faktur `IC-DIG-{id}` status **posted** plus jurnal kedua PT.
3. Selesai + komisi afiliasi opsional.

Impor BigSeller tetap rekap channel, tidak mengubah stok.

### Packing ONLINE ↔ omzet MP

Tabel `paket_ready` / `paket_ready_item` dan `mp_penjualan` (inventory, database yang sama) tampil di halaman order digital.

- SSO ke `/paket-ready.html` dan `/mp-penjualan.html`.
- **Tarik packing ke OMS** membuat order status selesai dari resi yang belum ada. Stok PUSAT **tidak** dipotong lagi (packing sudah lewat gudang ONLINE).

## Sales TO

`/fieldsales/outlets`:

- Limit kredit memakai sisa piutang (order approved+delivered dikurangi pelunasan).
- Kirim dari kanvas memotong stok KANVAS dan faktur `IC-TO-{id}` posted (retail penjual, TO pembeli).
- Umur piutang 0–30 / 31–60 / 61+ dari order terkirim, pelunasan FIFO tertua dulu.
- Komisi 2% **hanya** dari cash-in (`ar_settlements`). Pelunasan tidak boleh melebihi sisa.

## Inter-company

`/finance/inter-company`: draf harus **Posting jurnal** (GL penjual piutang, pembeli hutang). Kirim digital/TO tidak lewat draf.

## EIS holding

`php artisan erp:aggregate-eis-facts` (default 14 hari) atau tombol **Hitung ulang fakta** di `/executive`.

Isi `fact_sales_daily` (gerai + OMS/omzet MP + TO), `fact_ar_aging` (retail `jual.byr_hutang` + TO), `fact_stock_daily` (`inventory_balances`), `fact_ads_roas` (spend kampanye).

Budget 5W2H: draf → ajukan → periksa → setujui (`/executive/budgets`).

## Audit

```bash
php artisan erp:aggregate-eis-facts
php artisan erp:gelombang5-audit --aggregate
```

Jangan tandai selesai hanya karena menu `/executive` ada.
