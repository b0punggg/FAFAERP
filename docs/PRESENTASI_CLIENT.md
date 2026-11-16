# Presentasi Sistem ERP FAFA — Materi Client

Dokumen ini merangkum **alur, modul, dan roadmap** platform FAFA untuk presentasi ke stakeholder bisnis dan IT. Diagram menggunakan [Mermaid](https://mermaid.js.org); render di [mermaid.live](https://mermaid.live) lalu export PNG/SVG untuk PowerPoint atau Google Slides.

**Referensi teknis:** [ERP_ROADMAP.md](./ERP_ROADMAP.md) · [PHASE1_SCHEMA.md](./PHASE1_SCHEMA.md) · [FEATURE_MIGRATION_PLAN.md](../FEATURE_MIGRATION_PLAN.md)

---

## Executive summary (1 slide)

| Aspek | Penjelasan |
|--------|------------|
| **Apa ini?** | ERP retail multi-gerai dengan fondasi holding, migrasi bertahap dari admin PHP legacy ke Laravel |
| **Siapa pakai?** | Kasir, admin toko, manajer gerai, finance, direksi |
| **Data** | Satu database MySQL; historis tetap; modul baru memakai struktur ERP (`companies`, `branches`, jurnal, stok per gudang) |
| **Keunggulan** | SKU master + harga per gerai, stok per gudang, hutang/piutang, approval void, PO pusat, laporan terpusat |
| **Roadmap** | Fase 1 operasional retail + GL pilot → Fase 2 POS offline → Fase 3 Digital & TO → Fase 4 EIS holding |

---

## 1. Struktur grup usaha & peran sistem

Satu holding; **semua gerai** under **1 PT retail**; Digital Marketing dan Sales Taking Order sebagai PT terpisah (inter-company).

```mermaid
flowchart TB
    subgraph Holding["Holding / Direksi"]
        EIS["Dashboard EIS & Budget"]
        TB["Trial Balance & Laporan Keuangan"]
    end

    subgraph Retail["PT Retail — Semua Gerai"]
        WEB["Aplikasi Web Laravel<br/>Admin & POS Gerai"]
        G1["Gerai A<br/>branch + gudang"]
        G2["Gerai B"]
        G3["Gerai …"]
    end

    subgraph Digital["PT Digital Marketing"]
        OMS["OMS Digital / Ads / CRM"]
    end

    subgraph TO["PT Sales Taking Order"]
        SFA["SFA / Outlet / Kredit"]
    end

    Holding --> Retail
    Holding --> Digital
    Holding --> TO
    Retail <-->|"Faktur antar-PT"| Digital
    Retail <-->|"Faktur antar-PT"| TO
    WEB --> G1
    WEB --> G2
    WEB --> G3
```

**Talking point:** Platform siap multi-PT; operasional harian fokus gerai retail; direksi melihat agregat holding (Fase 4).

---

## 2. Arsitektur aplikasi (high level)

```mermaid
flowchart LR
    User["Kasir / Admin / Manajer"]
    Browser["Browser"]
    App["Laravel FAFA ERP"]
    DB[("MySQL")]

    User --> Browser --> App --> DB

    subgraph Layers["Isi database"]
        Legacy["Layer legacy<br/>mas_brg, pelanggan, beli_brg,<br/>mas_jual → jual, …"]
        ERP["Layer ERP<br/>companies, branches, products,<br/>inventory_balances, jurnal"]
    end

    DB --- Layers
```

**Talking point:** Migrasi **tanpa membuang** data lama; bridging data lewat command `erp:migrate-legacy-all` (lihat [MIGRATE_LEGACY_RUNBOOK.md](./MIGRATE_LEGACY_RUNBOOK.md)).

---

## 3. Login & konteks gerai

Setiap transaksi retail di-scope oleh **gerai aktif** (kode toko, gudang, tanggal kerja).

```mermaid
sequenceDiagram
    actor U as Pengguna
    participant L as Login
    participant C as BranchContext
    participant M as Modul Retail

    U->>L: Username & password
    L->>C: Set company / branch / kd_toko / warehouse
    U->>M: Penjualan, stok, laporan, …
    M->>C: Ambil gerai & tanggal kerja
    C-->>M: Filter data per gerai
    M-->>U: Tampilan & transaksi gerai tersebut
```

**Talking point:** Multi-gerai aman; hak akses (RBAC) membatasi void, diskon, ganti gerai.

---

## 4. Peta modul (menu aplikasi)

```mermaid
mindmap
  root((FAFA ERP))
    Beranda
      KPI penjualan
      Alert stok
    Master Data
      Barang Supplier Pelanggan
      Member Crew Kemasan
      Diskon User Gerai
    Transaksi
      Penjualan Pembelian
      Kas Biaya Piutang Hutang
      Retur Settlement
      STR Opname Penyesuaian
    Laporan
      Penjualan Stok Laba
      Rekap Trial Balance
      Dashboard Holding
    ERP Lanjutan
      PO Pusat ROP
      Digital OMS
      Sales TO Budget
    Pemeliharaan
      Tanggal kerja Backup
```

---

## 5. Siklus operasional harian gerai

```mermaid
flowchart TD
    A["Master: barang, harga per gerai,<br/>supplier, pelanggan"] --> B["Pembelian"]
    B --> C["Stok masuk"]
    C --> D["Penjualan / POS"]
    D --> E["Stok keluar"]
    D --> F{"Pembayaran"}
    F -->|Tunai| G["Kas"]
    F -->|Tempo| H["Piutang"]
    B --> I["Hutang supplier"]
    D --> J["Retur jual / beli"]
    G --> K["Settlement harian"]
    H --> L["Pelunasan piutang"]
    I --> M["Pelunasan hutang"]
    K --> N["Laporan & rekap"]
    L --> N
    M --> N
    J --> C
    O["Opname / penyesuaian / STR antar gerai"] --> C
    N --> P["Keuangan: jurnal & trial balance"]
```

**Talking point:** Mutasi antar gerai (satu PT) lewat **Transfer Stok (STR)**, selaras kebijakan ERP (bukan duplikasi modul mutasi lama).

---

## 6. Alur transaksi penjualan

```mermaid
flowchart LR
    subgraph Input
        S1["Scan / pilih barang"]
        S2["Harga per gerai"]
        S3["Pelanggan / member / crew"]
        S4["Diskon & bayar"]
    end

    subgraph Proses
        T1["Simpan nota & baris jual"]
        T2["Kurangi stok FIFO"]
        T3["Update stok ERP"]
        T4["Poin member"]
        T5["Posting jurnal opsional"]
    end

    subgraph Hasil
        O1["Cetak nota"]
        O2["Piutang jika tempo"]
        O3["Dashboard & laporan"]
    end

    S1 --> S2 --> S3 --> S4 --> T1
    T1 --> T2 --> T3
    T1 --> T4
    T1 --> T5
    T1 --> O1
    T1 --> O2
    T1 --> O3
```

**Talking point:** Void/hapus nota sensitif → **permintaan + approval** + audit log, bukan hapus langsung seperti sistem lama.

---

## 7. Stok — legacy & ERP (transisi)

```mermaid
flowchart TB
    subgraph Ops["Operasional"]
        Beli["Pembelian"]
        Jual["Penjualan"]
        Opname["Opname / adjustment"]
        STR["STR antar gudang"]
    end

    subgraph Leg["Stok operasional legacy"]
        BB["beli_brg FIFO"]
        MB["mas_brg per toko"]
    end

    subgraph Erp["Stok ERP"]
        IB["inventory_balances"]
    end

    Beli --> BB
    Jual --> BB
    Beli --> MB
    Jual --> MB
    Opname --> IB
    STR --> IB
    MB -.->|"reconcile"| IB
```

**Talking point:** Fase transisi; target jangka panjang **satu kebenaran stok per gudang** di ERP.

---

## 8. Roadmap implementasi

```mermaid
timeline
    title Roadmap ERP FAFA
    section Fase 1 Core & Retail
        Multi-PT multi-gerai RBAC
        Master beli jual hutang piutang
        Retur kas laporan jurnal pilot
    section Fase 2 POS & Gerai
        Settlement harian
        API POS offline
        STR antar gerai
    section Fase 3 Digital & TO
        OMS digital CRM affiliate
        SFA TO kredit komisi
        Tagihan antar perusahaan
    section Fase 4 EIS Holding
        Agregasi fakta harian
        Dashboard direksi
        Budget workflow
```

Detail fase: [ERP_ROADMAP.md](./ERP_ROADMAP.md).

---

## 9. Urutan slide disarankan

| # | Slide | Audiens |
|---|--------|---------|
| 1 | Executive summary (tabel di atas) | Semua |
| 2 | Struktur grup usaha (§1) | Direksi / owner |
| 3 | Peta modul (§4) | Operasional |
| 4 | Siklus harian gerai (§5) | Manajer toko |
| 5 | Alur penjualan (§6) | Kasir / supervisor |
| 6 | Roadmap (§8) | Semua |
| 7 | Arsitektur & stok (§2, §7) | IT / vendor |

---

## 10. FAQ singkat untuk Q&A

**Apakah data lama hilang?**  
Tidak. Dump production tetap; Laravel menambah tabel ERP dan command migrasi mengisi `jual`, katalog ERP, dll.

**Berapa PT dalam satu login?**  
User terikat company/branch; holding bisa punya hak lihat lintas PT (Fase 4).

**POS offline?**  
Fase 2 — API sync; pilot gerai setelah Fase 1 stabil.

**Laporan keuangan?**  
Trial balance per company; posting dari transaksi terpilih (jual/beli/kas/biaya); historis GL lama tidak otomatis di-backfill.

---

## Lampiran — export diagram

1. Buka https://mermaid.live  
2. Paste blok ` ```mermaid ` dari dokumen ini  
3. **Actions → Export PNG/SVG**  
4. Sisipkan ke deck; gunakan font & warna brand client di slide master

---

*Terakhir diselaraskan dengan codebase Laravel FAFA (sidebar, routes, ERP_ROADMAP).*
