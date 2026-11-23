# Gelombang 4 — akses dua aplikasi + CSS

Kasir Laravel dan gudang Node tetap dua login, tapi **username yang sama** bisa pindah tanpa ketik password lagi (tiket 2 menit di tabel `sso_tickets`, satu database).

## Tautan / SSO

| Dari | Tombol | Syarat |
|------|--------|--------|
| FAFA header **Gudang** / menu pantauan | `/sso/gudang` | Ada baris `users.username` = `pemakai.nm_user` |
| Gudang footer **Kasir FAFA** | `/sso/kasir?ticket=` | Ada pemakai dengan `nm_user` itu |

Kalau akun gudang belum ada: buat user inventory dengan username identik.

## URL

FAFA `.env`:

```
INVENTORY_APP_URL=https://gudang.contoh.com
ERP_CENTRAL_KD_TOKO=PUSAT
APP_URL=https://kasir.contoh.com
```

Inventory `.env`:

```
FAFA_APP_URL=https://kasir.contoh.com
FAFA_API_URL=https://kasir.contoh.com
DB_NAME=<database FAFA>
```

Lokal: `INVENTORY_APP_URL=http://localhost:3000`, `FAFA_APP_URL=http://127.0.0.1:8000`.

## RBAC (selaras, bukan satu akun)

| FAFA `otoritas` | Inventory `role` |
|-----------------|------------------|
| 8 Pemilik | `owner` (= admin + uang + ganti lokasi) |
| 2 Administrator | `admin` |
| 6 Finance | `finance` (pivot uang) |
| 1 Kasir | tetap FAFA; inventory `kasir` hanya baca stok |
| 3 Kepala toko | tetap FAFA; **bukan** `kepala_gudang` |
| — | `kepala_gudang` / `gudang` / `packer` hanya di inventory |

## CSS Hostinger

Document root sering folder proyek, bukan `public/`. `.htaccess` root memaksa `/assets/` ke `public/assets/`.

```bash
php artisan erp:gelombang4-assets
php artisan erp:gelombang4-assets --copy
```

`--copy` menimpa `assets/css/fafa-modern.css` di root bila rewrite Apache tidak aktif.
