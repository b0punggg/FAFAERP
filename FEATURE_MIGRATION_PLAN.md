# Rencana Migrasi Fitur Admin → Laravel (ERP-aligned)

Dokumen ini memetakan fitur folder legacy `admin/` ke aplikasi Laravel **sesuai arsitektur ERP yang sudah ada** — bukan copy-paste PHP lama.

**Referensi arsitektur:**

- [docs/ERP_ROADMAP.md](docs/ERP_ROADMAP.md) — fase holding, 1 PT retail (gerai = `branches`), digital & TO terpisah
- [docs/PHASE1_SCHEMA.md](docs/PHASE1_SCHEMA.md) — schema inti (`products`, `inventory_balances`, jurnal, STR, PO)

---

## Legenda status

| Simbol | Arti |
|--------|------|
| ✅ | Selesai (parity operasional minimal) |
| 🟡 | Partial — ada route/UI tapi belum parity legacy |
| ⬜ | Belum dimigrasi |
| 🔵 | Modul ERP baru (tidak ada di menu `admin`) |

**Modul ERP:** `Core` · `Retail` · `Inventory` · `Finance` · `Procurement` · `Digital` · `FieldSales` · `Executive`

---

## Prinsip implementasi (wajib untuk fitur baru)

1. **Scope:** semua layar retail memakai `BranchContext` (`company_id`, `branch_id`, `kd_toko`, `warehouse_id`) — lihat `App\Support\BranchContext`.
2. **Harga jual:** `BranchPricingService` + `branch_product_prices`; hindari duplikasi `mas_brg` per toko.
3. **Stok:** target tunggal `inventory_balances` per `warehouse_id`. Saat ini **dual write**: penjualan masih FIFO `beli_brg.stok_jual`; opname/STR pakai `inventory_balances` — lihat gelombang **A0**.
4. **Jurnal:** transaksi keuangan memanggil `PostingService` (perluas method per event: retur, kas, biaya, bayar hutang/piutang).
5. **Risiko:** void/hapus nota & diskon besar → `sale_void_requests`, `audit_logs` (bukan hapus fisik seperti maintenance legacy).
6. **UI:** layout `resources/views/layouts/app.blade.php` + `fafa-modern.css`; controller di namespace `Master`, `Retail`, `Report`, dll.
7. **Legacy `admin/*.php`:** referensi **aturan bisnis & cetak**, bukan arsitektur target.

### Keputusan arsitektur (legacy vs ERP)

| Legacy | Implementasi di sistem sekarang |
|--------|----------------------------------|
| `f_mutgudang.php` (mutasi antar gerai, 1 PT) | `/inventory/str` + `StockTransferService` |
| Adjustment stok lokal / selisih opname | Movement type `adjustment` di **InventoryMovementService** (A0) |
| `f_pesan.php` (pemesanan) | Fase 1: kebutuhan operasional; Fase 2: selaraskan dengan `/procurement/po` + goods receipt → pembelian |
| Master toko `m_toko` | Tampil/update `branches` (+ `kd_toko` legacy) |
| Tabel `kas` legacy | `kas_entries` di `/retail/kas` (+ posting kas opsional) |

---

## Inventaris Laravel saat ini (baseline)

| Area | Route / controller | Catatan |
|------|-------------------|---------|
| Login, dashboard | `/`, `/dashboard` | Chart, top 10, alert stok |
| Barang | `/products` | CRUD + search; pricing ERP + fallback `mas_brg` |
| Penjualan | `/sales` | Barcode/pelanggan AJAX, cetak, void request; diskon stub; stok via `beli_brg` |
| Pembelian | `/purchases` | Create/list/show dasar |
| Master | `/master/*` | Kemas, supplier, pelanggan, stores (read), bagian, paket (header), user (create) — kebanyakan **tanpa edit/hapus** |
| Laporan | `/reports/sales`, `purchases`, `stock` | Per periode dasar |
| Retail | `/retail/*` | Inventory ERP + legacy-stock, kas, piutang/hutang bayar, retur jual MVP, opname MVP, settlement harian |
| Finance | `/finance/trial-balance`, inter-company | GL Phase 1 |
| Inventory | `/inventory/str` | STR antar gudang |
| Procurement | `/procurement/po`, `/rop` | PO pusat, ROP alert |
| Digital / TO / EIS | `/digital`, `/fieldsales`, `/executive` | 🔵 di luar menu legacy |

Menu resmi legacy: `admin/starting.php` (sidebar Master, Transaksi, Report, Maintenance).

---

## Mapping fitur legacy → Laravel

### 1. Dashboard (`dasbor.php`) — Retail

| Fitur legacy | Status | Route | Gelombang |
|--------------|--------|-------|-----------|
| KPI inventory, jual hari ini, stok habis/menipis | ✅ | `/dashboard` | — |
| Chart penjualan, top 10 | ✅ | `/dashboard` | — |
| List stok 0 / stok ≤5 (AJAX) | 🟡 | dashboard sebagian | G |
| Dead stock (`dasbor_deadstock.php`) | ✅ | dashboard + `/utility/dead-stock` | G |
| Laba piutang kompleks (modal report) | ⬜ | — | F |

**Service:** query stok dari `inventory_balances` setelah A0.

---

### 2. Master data — Retail / Core

| Legacy | Status | Route target | Gap / gelombang |
|--------|--------|--------------|-------------------|
| Set tanggal kerja | ✅ | `BranchContext::businessDate()` | G |
| `m_kemas.php` | 🟡 | `master.kemas` | CRUD edit/hapus/cari — **A** |
| `m_sup.php` | 🟡 | `master.suppliers` | **A** |
| `m_pel.php` | 🟡 | `master.customers` | **A** |
| `m_toko.php` | 🟡 | `master.stores` | Edit branch, bukan CRUD `toko` terpisah — **A** |
| `m_bagian.php` | 🟡 | `master.bagian` | **A** |
| `f_masbrg.php` | 🟡 | `products.*` | Export Excel, barcode, history beli, filter — **A** |
| `m_paket.php` + `paket_brg` | 🟡 | `master.paket` | Detail baris paket, cari barang — **A** |
| `f_pasuser2.php` | 🟡 | `master.users` | Edit user, assign branch — **A** |
| `m_member.php` | ⬜ | `master.members` (baru) | **C** |
| `m_crew.php` | ⬜ | `master.crew` (baru) | **C** |
| `m_brand_report.php` | ⬜ | `reports/brand` | **F** |
| `m_persediaan_bulan.php` | ⬜ | `reports/inventory-monthly` | **F** |
| `f_discount_promo.php` | ⬜ | `master/promo` atau `retail/promo` | **B** |
| `f_setdiscount.php` | ⬜ | `master/discount-fixed` | **A5 / B** |

---

### 3. Transaksi — Retail / Finance

| Legacy | Status | Route target | Gap / gelombang |
|--------|--------|--------------|-------------------|
| `f_jual.php` | 🟡 | `sales.*` | Pending, edit, paket di cart, diskon engine, member/crew, multi cetak — **B**, **C** |
| Hapus penjualan + history | 🟡 | void request | Perluas reversal stok/jurnal + `history` audit — **B** |
| `f_beli.php` | 🟡 | `purchases.*` | Cari supplier/barang, cetak, edit — **B** |
| `f_pesan.php` | ✅ MVP | `retail/orders` + `/procurement/po` | **E** |
| `f_kas.php` | ✅ | `retail.kas` + cetak | GL opsional — **E** |
| `f_hutangbayar.php` | ✅ | `retail.payables` | Cari, partial, cetak bukti — **E** |
| `f_piutangbayar.php` | ✅ | `retail.receivables` | Cari, partial, cetak bukti — **E** |
| `f_returjual.php` | 🟡 | `retail.returns.sales` | Line detail, stok, cetak, posting — **D** |
| `f_returbeli.php` | ⬜ | `retail/returns/purchases` | **D** |
| `f_stokbrg.php` | 🟡 | `retail.inventory`, `reports.stock` | UI dedicated filter — **D** |
| `f_mutgudang.php` | 🟡 | `inventory.str` + adjustment | Dokumentasi UX; jangan duplikasi — **D** |
| `f_stokopname.php` | 🟡 | `retail.opname` | Progress, approve, cetak — **D** |
| `f_biaya.php` | ✅ | `retail/expenses` | **E** |
| `f_expdate.php` | ⬜ | `retail/expiry` | **G** |
| `f_tukarpoin.php` | ⬜ | `retail/points` | **C** |

**Settlement harian:** ✅ `retail.settlements` (🔵 Phase 2, tidak di menu legacy lama).

---

### 4. Laporan & cetak (`f_cetak_*`, menu Report) — Retail / Finance

Legacy: banyak modal `f_cetak_pilih_*` di `starting.php`. Target: **satu laporan per iterasi** via `ReportController` / `ReportService`.

| Jenis laporan legacy | Status | Gelombang |
|----------------------|--------|-----------|
| Penjualan / pembelian per periode | ✅ | `reports.sales`, `reports.purchases` + F1 agregasi |
| Stok barang | ✅ | `reports.stock` |
| Laba penjualan, laba piutang | ✅ | `reports.sales-profit`, `reports.receivable-collections` |
| Bagian penjualan, voucher, bonus | 🟡 | `reports.sales-by-section` (butuh `id_bag`), `reports.promo-summary` |
| Hutang/piutang cetak, bayar hutang/piutang report | ✅ | `reports.ar-ap` + cetak |
| Mutasi, mutasi online, opname, retur cetak | ✅ | `reports.activity` |
| Rekapitulasi (`f_rekap.php`) | 🟡 MVP | `reports.recap` per gerai |
| Transaksi member / crew | ✅ | **C** + `reports.member-sales` / `crew-sales` |

---

### 5. Maintenance — Retail / ops

| Legacy | Status | Gelombang |
|--------|--------|-----------|
| `f_backup.php` | ✅ | `erp:backup-database` + UI | **G** |
| `f_stokbrg2.php` (compare harga) | 🟡 | `/utility/price-compare` | **G** |
| `f_stokaset.php` | ⬜ | **D/G** (opsional) |
| `f_cekdatafile.php`, `f_setbag.php` | ✅ | `/utility/data-check`, `set-bagian` | **G** |
| Setting printer / proses data | ✅ | `branch_utility_settings` | **G** |
| `f_cetbarcode.php` (+ TSPL) | 🟡 | browser print MVP; TSPL nama printer | **G** |
| `m_history_notahapus.php` | ⬜ | audit / void history — **B** |

Opname juga ada di menu Maintenance legacy — sudah 🟡 di `/retail/opname`.

---

### 6. Modul ERP (bukan migrasi `admin`)

| Modul | Route | Fase roadmap |
|-------|-------|--------------|
| Trial balance, inter-company | `/finance/*` | Phase 1–3 |
| STR | `/inventory/str` | Phase 1–2 |
| PO pusat, ROP | `/procurement/*` | Phase 1 |
| Settlement, API POS sync | `/retail/settlements`, `routes/api.php` | Phase 2 |
| Digital OMS | `/digital/orders` | Phase 3 |
| Field sales SFA | `/fieldsales/*` | Phase 3 |
| EIS dashboard, budget | `/executive/*` | Phase 4 |

---

## Gelombang migrasi (bertahap)

### A0 — Stok tunggal (prasyarat teknis)

- [x] `InventoryMovementService` (jual, beli, opname, STR via `StockTransferService`) — lihat [docs/INVENTORY_MOVEMENT.md](docs/INVENTORY_MOVEMENT.md)
- [x] `SaleController` / `PurchaseController` / opname memakai service yang sama
- [x] Retur penjualan/pembelian (line + stok) — gelombang D (`sales_returns`, `purchase_returns`)
- [x] Kebijakan HPP: FIFO `beli_brg` (dokumentasi di service)
- [x] Dashboard & laporan stok baca `inventory_balances` jika `warehouse_id` ada (+ fallback `beli_brg`)
- [x] `inventory:sync-legacy-qty` reconcile dari `mas_brg.jml_brg`

### A — Master lengkap

- [x] CRUD edit/hapus/cari: kemas, supplier, pelanggan, bagian
- [x] Branch/toko via `branches` (edit nama/alamat/aktif)
- [x] Paket + `paket_brg` (detail `/master/paket/{id}`)
- [x] User edit + assign branch + hapus
- [x] Product: export CSV, filter cari (barcode/cetak → gelombang G)
- [x] Diskon tetap (`disctetap`) + hook `sales.calculate-discount`

### B — Penjualan & pembelian parity

- [x] Discount promo + integrasi `calculate-discount` (`DiscountCalculatorService`, `master.discount-promo`)
- [x] Paket di keranjang penjualan (`/sales/paket/{id}/lines`)
- [x] Pending penjualan (`sale_drafts`, `/sales/drafts`)
- [x] Void/hapus + history (`SaleVoidService`, `sale_deletion_history`, approval `/sales/void-requests`)
- [x] Pembelian: cari supplier/barang, cetak, form create/show
- [ ] Edit pembelian; multi format cetak nota (member/crew) — sisa / gelombang C

### C — Member, crew, poin

- [x] Master member & crew (scoped `kd_toko`) — `/master/members`, `/master/crew`
- [x] Penjualan + member/crew + poin (earn floor/50k, redeem 1:1 Rp) — `MemberPointService`
- [x] Tukar poin manual — `/retail/points/exchange` + riwayat
- [x] Laporan transaksi member/crew — `/reports/member-sales`, `/reports/crew-sales`
- [x] Cetak nota format member/crew — `?format=member|crew`

### D — Stok, retur, mutasi

- [x] Retur pembelian penuh (+ posting) — `/retail/returns/purchases`
- [x] Retur penjualan penuh (+ stok + cetak) — `/retail/returns/sales`
- [x] Opname workflow (draft → approve) — `/retail/opname`
- [x] Keputusan UX mutasi legacy → STR + adjustment — [docs/MUTASI_VS_STR.md](docs/MUTASI_VS_STR.md), `/retail/stock-adjustments`
- [ ] Stok barang UI; stok asset; compare harga (opsional)

### E — Pemesanan, kas, subledger lanjutan

- [x] Pemesanan MVP gerai (`store_orders`) + cetak; hubung PO pusat opsional — `/retail/orders`
- [x] Kas cetak bulanan + posting GL opsional — `/retail/kas`, `/retail/kas/print`
- [x] Hutang/piutang: cari faktur, partial, cetak bukti bayar — `/retail/receivables`, `/retail/payables`
- [x] Biaya operasional + posting GL opsional — `/retail/expenses` (`operating_expenses`)

### F — Laporan (satu per PR)

- [x] F1: per barang / pelanggan / supplier — `ReportService`, `/reports/*`
- [x] F2: laba & laba piutang (estimasi HPP / kolom `laba` jika ada)
- [x] F3: bagian (jika schema), ringkasan promo/diskon
- [x] F4: AR/AP cetak — `reports.ar-ap`
- [x] F5: mutasi, opname, retur — `reports.activity`
- [x] F6: rekapitulasi MVP — `reports.recap`
- [x] Persediaan masuk bulan — `reports.inventory-monthly` (brand report → **G**)

### G — Maintenance & utility

- [x] Expired date (`beli_brg.tgl_exp`), cetak barcode, backup — `/utility`, `erp:backup-database`
- [x] Dead stock widget (dashboard) + laporan, cek data, set bagian, printer — `/utility/*`
- [x] Set tanggal kerja — `BranchContext::businessDate()` + `/utility` (legacy `tgl_set`)

**Urutan eksekusi disarankan:** `A0 → A → B → D (retur) → B (void) → C → E1 → F1–F2 → G`.

---

## Tabel / data legacy vs ERP

| Kebutuhan | Sudah ada di Laravel/ERP | Perlu ditambah / lengkapi |
|-----------|--------------------------|---------------------------|
| Kemasan, bagian, paket header | Tabel legacy + route partial | `paket_brg` UI; CRUD penuh |
| Promo / disctetap | ⬜ | Migrasi schema jika belum; model + admin |
| Retur jual | `sales_returns` + legacy `retur_jual` | ✅ ERP table + GL |
| Retur beli | `purchase_returns` | ✅ controller + posting |
| Opname | `stok_opname`, lines | ✅ draft → approve |
| Kas | `kas_entries` | Cetak, GL |
| Biaya | `operating_expenses` | ✅ + posting opsional |
| Member, crew | ⬜ | Tabel + relasi ke `jual` |
| Mutasi legacy | ⬜ | Prefer STR + adjustment, bukan duplikasi `mutasi` |
| PO internal | `purchase_orders` + `store_orders.purchase_order_id` | MVP link manual |

**Runbook lengkap:** [docs/MIGRATE_LEGACY_RUNBOOK.md](docs/MIGRATE_LEGACY_RUNBOOK.md)

**Satu perintah (disarankan setelah `migrate` + import dump):**

```bash
php artisan erp:migrate-legacy-all --fresh --force --with-kas --with-expenses --with-returns
```

| Command | Isi |
|---------|-----|
| `erp:migrate-legacy-all` | Orchestrator: seed, katalog, transaksi, sync stok (+ flag opsional) |
| `erp:migrate-legacy-catalog` | `mas_brg` → products / harga / inventory ERP |
| `erp:migrate-legacy-sales` | `mas_jual` → `jual`, `dum_jual` (SUDAH) → `jual_brg` |
| `erp:migrate-legacy-purchases` | agregat `beli_brg` → header `beli` |
| `erp:migrate-legacy-transactions` | penjualan + pembelian (`--fresh`, `--force`) |
| `erp:migrate-legacy-kas` | `kas_harian` → `kas_entries` |
| `erp:migrate-legacy-expenses` | `biaya_ops` → `operating_expenses` |
| `erp:migrate-legacy-returns` | `retur_jual` → `sales_returns` |
| `inventory:sync-legacy-qty` | reconcile `mas_brg.jml_brg` → `inventory_balances` |

---

## Template checklist per fitur (saat mengerjakan PR)

```text
Legacy file(s):
Modul ERP:
Route:
Controller / Service:
BranchContext: ya
BranchPricingService: ya/tidak
InventoryMovementService: ya/tidak
PostingService: method baru?
Audit / void:
Status: ⬜ → 🟡 → ✅
Gelombang:
```

---

## Riwayat dokumen

- **Rewrite (2025):** selaras ERP (`BranchContext`, dual stok, STR vs mutasi, gelombang A0–G), status aktual route Laravel, modul legacy yang sebelumnya tidak tercantum (member, crew, pesan, rekap, cetak massal).
