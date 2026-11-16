# Mutasi legacy vs STR

| Kebutuhan | Laravel |
|-----------|---------|
| Transfer stok **antar gudang/gerai** (1 PT retail) | `/inventory/str` — `StockTransferService` |
| Koreksi stok **lokal** (barang rusak/hilang/temuan) | `/retail/stock-adjustments` → `mutasi_gudang` + `InventoryMovementService` |
| Opname fisik | `/retail/opname` — draft → **approve** posting selisih |

Legacy `f_mutgudang.php` mencakup keduanya; di ERP dipisah agar audit dan STR approval jelas.
