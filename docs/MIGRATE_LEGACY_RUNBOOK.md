# Runbook migrasi data legacy → Laravel

Cutover tiga dump gerai (jam tutup boleh berbeda): [GELOMBANG_0_RUNBOOK.md](GELOMBANG_0_RUNBOOK.md).

Database production (dump lama) dan schema ERP Laravel **hidup berdampingan**. Command di bawah **tidak** mengganti tabel master legacy (`mas_brg`, `supplier`, `pelanggan`, `member`, `crew`, …) — aplikasi Laravel tetap membaca banyak master dari situ.

## Prasyarat

1. Import dump MySQL ke DB yang sama dengan `.env` (`DB_DATABASE`).
2. `php artisan migrate` — tambah tabel/kolom ERP.
3. Pastikan ada data di **`mas_jual` / `dum_jual`** jika ingin riwayat penjualan di `/sales` (bukan hanya tabel `jual` kosong dari schema).

## Satu perintah (disarankan)

Import **foundation + katalog + transaksi + sync stok**:

```bash
php artisan erp:migrate-legacy-all --fresh --force
```

Rebuild penuh **plus** kas, biaya, retur historis:

```bash
php artisan erp:migrate-legacy-all --fresh --force --with-kas --with-expenses --with-returns
```

| Opsi | Arti |
|------|------|
| `--fresh` | Kosongkan tabel target sebelum isi ulang (jual, beli, modul opsional) |
| `--force` | Lewati konfirmasi interaktif |
| `--skip-seed` | Jangan `db:seed` (branch/gudang/CoA) |
| `--skip-catalog` | Lewati `products` / harga / `inventory_balances` |
| `--skip-transactions` | Lewati `mas_jual` → `jual` |
| `--skip-inventory` | Lewati `inventory:sync-legacy-qty` |
| `--with-kas` | `kas_harian` → `kas_entries` |
| `--with-expenses` | `biaya_ops` → `operating_expenses` |
| `--with-returns` | `retur_jual` → `sales_returns` |
| `--dedupe=barcode\|sku` | Dedupe katalog ERP |
| `--kd-toko=001` | Sync stok hanya satu gerai |

Backup sebelum `--fresh`:

```bash
php artisan erp:backup-database
```

## Perintah per modul

| Command | Sumber → Target |
|---------|-----------------|
| `erp:migrate-legacy-catalog` | `mas_brg` → `products`, `branch_product_prices`, `inventory_balances` |
| `erp:migrate-legacy-sales` | `mas_jual` → `jual`, `dum_jual` (SUDAH) → `jual_brg` |
| `erp:migrate-legacy-purchases` | agregat `beli_brg` → `beli` |
| `erp:migrate-legacy-transactions` | sales + purchases (opsi `--fresh`) |
| `inventory:sync-legacy-qty` | `mas_brg.jml_brg` → `inventory_balances` |
| `erp:migrate-legacy-kas` | `kas_harian` → `kas_entries` (saldo harian, `direction=in`) |
| `erp:migrate-legacy-expenses` | `biaya_ops` → `operating_expenses` |
| `erp:migrate-legacy-returns` | `retur_jual` → `sales_returns` + lines |

## Belum otomatis

- Jurnal historis GL, mutasi legacy, PO/pemesanan lama, piutang/hutang bayar historis ke tabel payment ERP
- Posting ulang ke `journal_entries` untuk transaksi lama

Lihat [FEATURE_MIGRATION_PLAN.md](../FEATURE_MIGRATION_PLAN.md) untuk gap fitur vs parity legacy.
