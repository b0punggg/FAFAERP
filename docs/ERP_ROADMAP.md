# ERP Roadmap — Multi-Corporate (Brief Alignment)

## Struktur legal

| Entitas | Badan hukum | `companies.type` |
|---------|-------------|------------------|
| Direksi / governance | Holding | `holding` |
| Semua gerai | **1 PT retail** | `retail` — gerai = `branches` (`kd_toko` legacy) |
| Digital Marketing | PT terpisah | `digital` |
| Sales Taking Order | PT terpisah | `field_sales` |

Harga jual: **SKU master** + `branch_product_prices` per gerai (bukan duplikasi `mas_brg` per toko).

## Fase eksekusi (brief)

### Phase 1 — Core & Finance + retail operasional

- Foundation: `companies`, `branches`, `warehouses`, RBAC, `BranchContext`
- Katalog: `products`, `branch_product_prices`, `inventory_balances` (+ compat `mas_brg`)
- GL: CoA, jurnal, posting jual/beli, trial balance
- Lanjutan fitur legacy → modul **Retail** (lihat mapping di bawah)
- Procurement pusat, ROP MVP, audit log, approval void/discount

**Exit:** 1 gerai pilot full cycle; trial balance PT retail; skeleton PT digital & TO.

### Phase 2 — Pilot gerai (POS)

- Settlement harian, API Sanctum + sync idempotency
- STR antar gerai (satu PT)
- POS offline (`mobile/pos` README + API)

### Phase 3 — Digital & TO

- OMS, ads, affiliate, CRM (PT digital)
- SFA API, credit, AR aging, komisi cash-in (PT TO)
- `inter_company_invoices` retail ↔ digital/TO

### Phase 4 — EIS

- Fact tables nightly, dashboard holding, budget 5W2H workflow

## Mapping FEATURE_MIGRATION_PLAN → modul

| Area FEATURE_MIGRATION_PLAN | Modul ERP | Phase |
|-----------------------------|-----------|-------|
| Dashboard, Penjualan, Pembelian | Retail + Core | 1 |
| Master kemasan, bagian, paket, supplier, pelanggan, toko, user | Retail / Core | 1 |
| Stok, opname, mutasi | Inventory | 1 |
| Hutang/piutang, retur | Finance subledger | 1 |
| Kas, laporan, utility | Retail / Finance | 1–2 |
| POS offline, settlement | Retail API | 2 |

Detail teknis Phase 1: [PHASE1_SCHEMA.md](./PHASE1_SCHEMA.md).

Materi presentasi client (diagram alur): [PRESENTASI_CLIENT.md](./PRESENTASI_CLIENT.md).
