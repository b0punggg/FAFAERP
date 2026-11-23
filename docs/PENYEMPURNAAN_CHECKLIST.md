# Checklist penyempurnaan FAFA + Inventory

Penyempurnaan menuju brief ERP (`ERP_ROADMAP.md`, `PRESENTASI_CLIENT.md`) tanpa mengorbankan operasional gerai.

**Status:** `[ ]` belum · `[x]` selesai · `[~]` parsial

## Prinsip

- Satu MySQL untuk kasir Laravel dan gudang Node.
- Stok resmi = `inventory_balances` per gudang.
- Stok gerai FAFA 1–4 tidak di-nolkan / tidak di-wipe.
- Gudang PUSAT diisi opname, bukan salinan `jml_brg` gerai.
- Overlay: `php artisan migrate` + `erp:sync-stage0` + inventory `ensureStage0` (`npm run stage0`).
- Jangan `npm run migrate` inventory, `migrate:fresh`, atau `db:wipe`.

Gelombang dikerjakan sekarang: **5** (digital / TO / EIS). Exit 0–4 lokal sudah; hosting produksi tetap terbuka.

Urutan: `0 → 1` wajib berurutan. `2` boleh paralel dengan `1`. `3` setelah `1`. `4` kapan saja setelah `0`. `5` hanya setelah `0–3`.

---

Runbook freeze bertahap (jam tutup boleh beda): [GELOMBANG_0_RUNBOOK.md](GELOMBANG_0_RUNBOOK.md)

## Gelombang 0 — Go-live produksi

### Data

- [x] Jalur DB sekarang: tidak menunggu dump freeze 3/4 (freeze tetap opsional jika histori kasir lama mau ditempel)
- [~] Catat jam tutup / jam dump / nama file SQL per sumber — dilewati selama memakai DB sekarang
- [x] Backup DB uji: `storage/app/backups/backup-20261123-124628.sql`
- [~] Merge 3 DB — ditunda (alat `erp:gelombang0-merge-toko` siap)
- [~] Prefiks nota — ditunda sampai dump freeze
- [x] `mas_brg` per `(kd_toko, kd_brg)` gerai 3/4 tidak menimpa katalog 1–2 (merge skip jika sudah ada)
- [x] Tabel `toko` memuat `IDTOKO-1` … `IDTOKO-4`
- [~] Supplier/member: kode yang sudah ada dilewati; nama beda dilapor audit (tanpa overwrite)
- [x] User uji dibuat baru: `G0KASIR1`–`4` / `FafaExit0` (bukan copy `pemakai` lama)

### Overlay ERP

- [x] `php artisan migrate` (bukan `migrate:fresh`)
- [x] Seed company retail + branch gerai 1–4
- [x] `php artisan erp:sync-stage0` (toko `PUSAT` + bind warehouse)
- [x] `php artisan erp:gelombang0-prepare --sync-qty --zero-central` (22.533 baris gerai; PUSAT 0)
- [x] Qty PUSAT = 0 sampai opname
- [x] Inventory: `npm run stage0` di database `fafa`
- [ ] Jadwal opname gudang pusat (orang + waktu) — operasional, bukan kode

### Hosting

- [ ] FAFA `.env`: `APP_URL`, `DB_*` produksi, `INVENTORY_APP_URL`, `ERP_CENTRAL_KD_TOKO=PUSAT`
- [ ] Inventory satu akun Hostinger dengan FAFA **atau** remote MySQL + host/IP diizinkan
- [ ] Inventory env: `DB_NAME` = database FAFA, `FAFA_API_URL`, `CENTRAL_KD_TOKO=PUSAT`, `SESSION_SECRET` ≥ 32 karakter
- [ ] Situs inventory bukan 503; log runtime tanpa `Access denied`
- [ ] CSS FAFA `fafa-modern.css` HTTP 200 (aset di `public/assets` terlayani)
- [ ] Kasir PHP lama di semua hosting gerai berhenti menulis ke DB

### Uji go-live

- [x] Login kasir FAFA 1–4 (`G0KASIR1`–`4`, hash `FafaExit0`)
- [x] 1 nota jual per gerai (`G0EXIT-IDTOKO-*-20261123`)
- [x] Stok gerai 1 ≠ gerai 2 (katalog tidak overlap; total qty beda)
- [x] Gudang: user `gudang1` @ PUSAT; kode SJ memaksa asal PUSAT
- [x] Packing `packer1` @ ONLINE; kode tidak memotong FAFA 1–4

**Exit 0 lokal:** empat gerai + PUSAT, satu database `fafa`, kasir dan inventory hidup. Hosting produksi (blok di atas) belum.

Ulangi cek: `php artisan erp:gelombang0-exit --skip-sync --skip-sales`

---

## Gelombang 1 — Stok satu sumber

- [x] Jual, beli, retur, opname, SJ, STR lewat `InventoryMovementService` / API gudang
- [x] `inventory_balances` = angka resmi per gudang
- [x] `mas_brg.jml_brg` cache dari ERP (`adjustBalance` + `erp:gelombang1-reconcile --sync-cache`)
- [x] HPP tetap FIFO lot `beli_brg`
- [x] Dashboard/ROP/`v_stok_lokasi`/POS search baca gudang yang sama (`inventory_balances`)
- [x] Rekonsiliasi: `php artisan erp:gelombang1-reconcile`
- [x] Opname gerai vs PUSAT: [GELOMBANG_1_STOK.md](GELOMBANG_1_STOK.md); kasir PUSAT diblok

**Exit 1:** satu angka stok per SKU per lokasi di kasir dan inventory. Hosting produksi terpisah (Gelombang 0).

---

## Gelombang 2 — Katalog, harga, role

- [x] `products.sku` unik; nama barang tidak andalkan `mas_brg` sebagai master
- [x] Harga jual POS dari `branch_product_prices` / `BranchPricingService`
- [x] Nama/kemasan SKU diseragamkan antar gerai setelah merge (`erp:gelombang2-sync-catalog`)
- [x] Role inventory `owner`: ENUM `users.role`, `middleware/auth.js`, `public/layout.js`, `public/users.html`
- [x] `owner` mendapat hak setara `admin` (termasuk uang + ganti lokasi)
- [x] Role owner di FAFA jika pemilik login kasir (`pemakai.otoritas` = 8 Pemilik)

**Exit 2:** satu SKU = satu barang; harga boleh beda per gerai; owner bisa dipilih di form user. Lihat [GELOMBANG_2_KATALOG.md](GELOMBANG_2_KATALOG.md).

---

## Gelombang 3 — Tutup siklus harian + GL

- [x] Edit pembelian (`purchases/{id}/edit` + jurnal diganti)
- [x] Cetak nota member/crew/A4/thermal
- [x] UI history void / hapus nota (snapshot isi nota)
- [x] GL wajib: jual, beli, kas, retur, bayar hutang, bayar piutang
- [x] Trial balance gerai / PT retail bisa ditelusur ke nota (`finance.trial-balance.ledger`)
- [x] Settlement harian dipakai setiap tutup toko (angka POS otomatis)
- [x] POS offline (`mobile/pos`) — ditunda (gerai online)

**Exit 3:** beli → stok → jual → kas/piutang → settlement → laporan + jurnal. Lihat [GELOMBANG_3_SIKLUS.md](GELOMBANG_3_SIKLUS.md). `php artisan erp:gelombang3-audit`

---

## Gelombang 4 — Akses dan UX (opsional setelah 0)

- [x] Tautan atau SSO kasir ↔ gudang (`/sso/gudang`, tiket `sso_tickets`, tombol Kasir FAFA)
- [x] URL produksi `INVENTORY_APP_URL` / menu pantauan lewat SSO
- [x] RBAC owner / finance / kepala gudang / kasir selaras (username sama; kepala toko ≠ kepala gudang)
- [x] Folder `assets` document root = `public/assets` (rewrite `.htaccess` + `erp:gelombang4-assets --copy`)

**Exit 4:** user tidak bingung dua login; aset CSS stabil di Hostinger. Lihat [GELOMBANG_4_AKSES.md](GELOMBANG_4_AKSES.md).

---

## Gelombang 5 — Brief Fase 3–4 (setelah 0–3)

Kerjakan hanya jika ada pemilik proses bisnis. Menu skeleton Laravel tidak dihitung selesai.

### Digital

- [x] OMS order marketplace end-to-end (catat → kirim PUSAT → selesai; impor BigSeller tidak memotong stok)
- [x] Packing ONLINE terhubung omzet marketplace (`paket_ready` + `mp_penjualan` di halaman digital; tarik resi ke OMS tanpa potong PUSAT)
- [x] Faktur inter-company retail ↔ digital (posted + jurnal saat kirim; draf UI bisa diposting)

### Sales TO

- [x] Outlet SFA, kredit, AR aging (sisa = billed − pelunasan; bucket 0–30/31–60/61+)
- [x] Komisi dari pelunasan (cash-in 2%, tidak dari SO)
- [x] Faktur inter-company retail ↔ TO (posted saat kirim kanvas)

### EIS

- [x] Fact harian (penjualan gerai/OMS/MP/TO, stok `inventory_balances`, AR retail+TO) — `erp:aggregate-eis-facts`
- [x] Dashboard holding baca fact_* + tombol hitung ulang (bukan menu kosong)
- [x] Budget 5W2H workflow (draf → ajukan → periksa → setujui)

**Exit 5 lokal:** `php artisan erp:gelombang5-audit --aggregate`. Modul siap dipakai tim terkait; hosting produksi (Gelombang 0) masih terbuka.

Lihat [GELOMBANG_5_DIGITAL_TO_EIS.md](GELOMBANG_5_DIGITAL_TO_EIS.md).

---

## Dilarang

Jangan tandai item berikut sebagai capaian:

- Import dump Hostinger mentah ke database ERP yang sudah berisi gudang/SJ
- `migrate:fresh` / `db:wipe` / `npm run migrate` inventory
- Menyalin `jml_brg` gerai ke PUSAT
- Klaim holding/EIS selesai hanya karena ada menu `/executive`
