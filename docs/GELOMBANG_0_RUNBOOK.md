# Gelombang 0 — freeze bertahap & overlay ERP

Kasir **tidak** harus tutup di jam dinding yang sama. Yang wajib: setelah **jam tutup gerai itu**, dump DB gerai itu, lalu gerai itu **berhenti menulis** ke sistem lama sampai go-live.

## Jam tutup berbeda

| Sumber DB | Kapan dump | Catatan |
|-----------|------------|---------|
| FAFA 1–2 (satu DB) | Setelah **yang terakhir** dari gerai 1 atau 2 tutup malam itu | Satu dump untuk dua gerai |
| FAFA 3 | Setelah gerai 3 tutup | Dump sendiri |
| FAFA 4 | Setelah gerai 4 tutup | Dump sendiri |

Contoh: gerai 3 tutup 21:00 → dump 21:10. Gerai 4 tutup 22:30 → dump 22:40. Gerai 1–2, yang tutup paling malam 21:30 → dump 1–2 jam 21:40. Merge dilakukan **setelah ketiga dump ada** (malam itu / pagi sebelum buka).

Setelah dump, toko itu **jangan** jual/beli di PHP lama. Kalau tetap buka, dump itu usang — ulangi dump atau ambil delta (sulit). Lebih aman: malam cutover semua gerai tidak buka lagi sampai sistem baru siap esok paginya.

Catat di kertas:

```text
Gerai / DB     Jam tutup   Jam dump   File SQL              Petugas
FAFA 1–2       ……          ……         tokofafa_12.sql
FAFA 3         ……          ……         tokofafa_3.sql
FAFA 4         ……          ……         tokofafa_4.sql
```

## Catat freeze (setelah dump SQL)

```bash
php artisan erp:gelombang0-audit
php artisan erp:gelombang0-audit --log-dump --gerai=FAFA-3 --closed-at="2026-11-23 21:00" --dumped-at="2026-11-23 21:10" --sql-file=tokofafa_3.sql --petugas=Nama
```

`erp:gelombang0-audit` mem-probe `fafa_src3` / `fafa_src4`, hitung bentrok `no_fak`, dan bandingkan kode supplier/member (kode sama nama beda → petakan manual, tidak di-overwrite). Log: `storage/app/gelombang0/freeze-log.csv`.

Backup DB uji (bukan dump gerai): `php artisan erp:backup-database`.

## Merge (lokal, bukan di produksi yang sudah berisi SJ uji)

1. Import dump 1–2 ke DB kosong `fafa_merge` (tulang punggung).
2. Import dump 3 ke `fafa_src3`, dump 4 ke `fafa_src4` (phpMyAdmin / mysql).
3. `.env` `DB_DATABASE=fafa_merge`.
4. Dry-run lalu tulis:

```bash
php artisan erp:gelombang0-merge-toko --from=fafa_src3 --from-kd-toko=IDTOKO-1 --kd-toko=IDTOKO-3 --offset=10000000 --fak-prefix=3-
php artisan erp:gelombang0-merge-toko --from=fafa_src3 --from-kd-toko=IDTOKO-1 --kd-toko=IDTOKO-3 --offset=10000000 --fak-prefix=3- --execute

php artisan erp:gelombang0-merge-toko --from=fafa_src4 --from-kd-toko=IDTOKO-1 --kd-toko=IDTOKO-4 --offset=20000000 --fak-prefix=4-
php artisan erp:gelombang0-merge-toko --from=fafa_src4 --from-kd-toko=IDTOKO-1 --kd-toko=IDTOKO-4 --offset=20000000 --fak-prefix=4- --execute
```

Dump gerai mandiri biasanya `kd_toko=IDTOKO-1` — `--from-kd-toko` filter sumber, `--kd-toko` kode di database gabungan. Command **tidak** menyalin `pemakai`. Prefiks nota hanya jika nomor faktur bentrok; jika sudah unik, hilangkan `--fak-prefix`.

5. Jangan `DROP` tabel ERP. `mas_brg` yang `(kd_toko, kd_brg)` sudah ada dilewati.

Perintah overlay (setelah merge):

```bash
php artisan migrate
php artisan db:seed --class=ErpFoundationSeeder
php artisan erp:sync-stage0
php artisan erp:migrate-legacy-all --force --with-kas --with-expenses --with-returns
php artisan erp:gelombang0-prepare --sync-qty --zero-central
```

`erp:migrate-legacy-all --fresh` hanya jika tabel jual/beli Laravel boleh dikosongkan lalu diisi ulang dari dump. Jangan `--fresh` di DB yang sudah berisi SJ/PUSAT uji yang ingin dipertahankan — untuk cutover client, target adalah DB hasil merge (kosong ERP), jadi `--fresh` aman.

Inventory: `npm run stage0` saja, **bukan** `npm run migrate`.

PUSAT tetap 0 sampai opname.

## Hosting

Inventory harus memakai **database FAFA yang sama**. Beda akun Hostinger: pindahkan Node ke akun FAFA, atau remote MySQL + izinkan IPv6 server inventory (`Access denied` di log).

## Perintah cek

```bash
php artisan erp:gelombang0-audit
php artisan erp:gelombang0-prepare
php artisan inventory:sync-legacy-qty
php artisan erp:gelombang0-prepare --sync-qty --zero-central
```

Sync stok **hanya gerai** (punya `branches`). Gudang PUSAT, KANVAS, ONLINE dilewati.
