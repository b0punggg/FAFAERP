# POS Offline-First (Pilot Wonogiri)

Client mobile (Flutter/React Native) untuk 3 gerai pilot. Sinkron ke API Laravel:

- `POST /api/v1/pos/sync` — batch events dengan `idempotency_key` (UUID)
- Auth: Laravel Sanctum token per device/kasir

## Offline rules

1. Transaksi disimpan lokal (SQLite) dengan UUID idempotency.
2. Saat online, kirim batch; server skip duplicate keys.
3. Konflik stok: server quantity authoritative setelah sync.

## Env contoh

```
API_BASE_URL=https://fafa.test/api/v1
BRANCH_ID=1
```

Dual-run 4–8 minggu dengan web POS sebelum cutover penuh.
