# Phase 1 — Schema & ERD (ringkas)

## Entitas inti

```
companies 1──* branches 1──* warehouses
companies 1──* chart_of_accounts
companies 1──* journal_entries *── journal_lines
products 1──* branch_product_prices (branch_id)
products 1──* inventory_balances (warehouse_id)
users *──* roles (company_id nullable = holding)
```

## Tabel Phase 1.1

- `companies` — code, legal_name, type, npwp, currency
- `branches` — company_id, kd_toko (legacy), name, address, is_active
- `warehouses` — company_id, branch_id nullable, code, name
- `roles`, `permissions`, `role_user`, `permission_role`

## Tabel Phase 1.2

- `products` — sku (kd_brg), barcode, name, kemasan fields
- `branch_product_prices` — branch_id, product_id, unit tiers + prices
- `inventory_balances` — warehouse_id, product_id, qty_on_hand

## Tabel Phase 1.3

- `chart_of_accounts`, `coa_group_maps`
- `journal_entries`, `journal_lines`
- `stock_transfer_requests`, `stock_transfer_lines`
- `inter_company_invoices` (cross-PT, Phase 3 usage)

## Tabel Phase 1.5–1.6

- `purchase_orders`, `purchase_order_lines`, `goods_receipts`
- `audit_logs`
- `sale_void_requests` (approval stub)

## Migrasi data

Artisan: `php artisan erp:migrate-legacy-catalog` — isi products/prices/inventory dari `mas_brg` + `toko`.
