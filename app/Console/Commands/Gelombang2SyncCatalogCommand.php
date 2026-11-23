<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Gelombang2SyncCatalogCommand extends Command
{
    protected $signature = 'erp:gelombang2-sync-catalog
                            {--dry-run : Hitung tanpa menulis}
                            {--skip-prices : Lewati isi branch_product_prices}';

    protected $description = 'Gelombang 2: seragamkan products dari PUSAT, isi harga per gerai';

    public function handle(): int
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('mas_brg')) {
            $this->error('Tabel products / mas_brg belum ada.');

            return self::FAILURE;
        }

        $this->ensureOwnerEnum();

        $dups = DB::table('products')
            ->select('sku', DB::raw('COUNT(*) as c'))
            ->groupBy('sku')
            ->having('c', '>', 1)
            ->limit(10)
            ->get();
        if ($dups->isNotEmpty()) {
            $this->error('products.sku duplikat: '.$dups->pluck('sku')->implode(', '));

            return self::FAILURE;
        }

        $dry = (bool) $this->option('dry-run');
        $canonical = $this->pickCanonicalMasBrg();
        $this->info('SKU unik di mas_brg: '.count($canonical));

        $existing = (int) DB::table('products')->count();
        $created = max(0, count($canonical) - $existing);
        $updated = min($existing, count($canonical));

        if (! $dry) {
            $this->applyCanonical($canonical);
        }

        $this->info("Katalog: perkiraan baru {$created}, diseragamkan {$updated}".($dry ? ' (dry-run)' : ''));

        $priceUpsert = 0;
        if (! $this->option('skip-prices') && Schema::hasTable('branch_product_prices') && Schema::hasTable('branches')) {
            $priceUpsert = $this->syncPrices($dry);
            $this->info("Harga gerai upsert: {$priceUpsert}".($dry ? ' (dry-run)' : ''));
        }

        $skuCount = (int) DB::table('products')->count();
        $priceCount = Schema::hasTable('branch_product_prices')
            ? (int) DB::table('branch_product_prices')->count()
            : 0;
        $this->info("Exit 2 lokal: products={$skuCount} sku unik, branch_product_prices={$priceCount}");

        return self::SUCCESS;
    }

    protected function ensureOwnerEnum(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }
        $col = DB::selectOne("SHOW COLUMNS FROM users LIKE 'role'");
        $type = strtolower((string) ($col->Type ?? ''));
        if ($type === '' || str_contains($type, "'owner'")) {
            return;
        }
        DB::statement(
            "ALTER TABLE users MODIFY COLUMN role ENUM('admin','owner','gudang','kepala_gudang','kasir','packer','admin_store','finance') NOT NULL DEFAULT 'gudang'"
        );
        $this->info('ENUM users.role: owner ditambah.');
    }

    /**
     * @return array<string, object>
     */
    protected function pickCanonicalMasBrg(): array
    {
        $best = [];
        $q = DB::table('mas_brg')->select([
            'kd_brg', 'kd_toko', 'kd_bar', 'nm_brg',
            'kd_kem1', 'nm_kem1', 'jum_kem1',
            'kd_kem2', 'nm_kem2', 'jum_kem2',
            'kd_kem3', 'nm_kem3', 'jum_kem3',
        ]);

        foreach ($q->cursor() as $row) {
            $sku = trim((string) $row->kd_brg);
            if ($sku === '') {
                continue;
            }
            if (! isset($best[$sku])) {
                $best[$sku] = $row;

                continue;
            }
            if ($this->rankMas($row) < $this->rankMas($best[$sku])) {
                $best[$sku] = $row;
            }
        }

        return $best;
    }

    protected function rankMas(object $row): int
    {
        $pusat = strtoupper(trim((string) $row->kd_toko)) === 'PUSAT' ? 0 : 1;
        $len = 9999 - min(9999, strlen((string) $row->nm_brg));

        return ($pusat * 10000) + $len;
    }

    /**
     * @param  array<string, object>  $canonical
     */
    protected function applyCanonical(array $canonical): void
    {
        DB::statement('DROP TEMPORARY TABLE IF EXISTS g2_canon');
        DB::statement("
            CREATE TEMPORARY TABLE g2_canon (
                sku VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL PRIMARY KEY,
                barcode VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                kd_kem1 INT NOT NULL DEFAULT 1,
                nm_kem1 VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                jum_kem1 DECIMAL(15,2) NOT NULL DEFAULT 1,
                kd_kem2 INT NULL,
                nm_kem2 VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                jum_kem2 DECIMAL(15,2) NULL,
                kd_kem3 INT NULL,
                nm_kem3 VARCHAR(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
                jum_kem3 DECIMAL(15,2) NULL
            ) ENGINE=InnoDB
        ");

        foreach (array_chunk($canonical, 400, true) as $chunk) {
            $rows = [];
            foreach ($chunk as $sku => $row) {
                $p = $this->payloadFromMas($row);
                $rows[] = [
                    'sku' => $sku,
                    'barcode' => $p['barcode'] !== null ? mb_substr((string) $p['barcode'], 0, 50) : null,
                    'name' => mb_substr((string) $p['name'], 0, 255),
                    'kd_kem1' => $p['kd_kem1'],
                    'nm_kem1' => $p['nm_kem1'] !== null ? mb_substr((string) $p['nm_kem1'], 0, 50) : null,
                    'jum_kem1' => $p['jum_kem1'],
                    'kd_kem2' => $p['kd_kem2'],
                    'nm_kem2' => $p['nm_kem2'] !== null ? mb_substr((string) $p['nm_kem2'], 0, 50) : null,
                    'jum_kem2' => $p['jum_kem2'],
                    'kd_kem3' => $p['kd_kem3'],
                    'nm_kem3' => $p['nm_kem3'] !== null ? mb_substr((string) $p['nm_kem3'], 0, 50) : null,
                    'jum_kem3' => $p['jum_kem3'],
                ];
            }
            DB::table('g2_canon')->insert($rows);
        }

        DB::statement("
            UPDATE products p
            INNER JOIN g2_canon c ON c.sku = p.sku
            SET p.barcode = c.barcode,
                p.name = c.name,
                p.kd_kem1 = c.kd_kem1,
                p.nm_kem1 = c.nm_kem1,
                p.jum_kem1 = c.jum_kem1,
                p.kd_kem2 = c.kd_kem2,
                p.nm_kem2 = c.nm_kem2,
                p.jum_kem2 = c.jum_kem2,
                p.kd_kem3 = c.kd_kem3,
                p.nm_kem3 = c.nm_kem3,
                p.jum_kem3 = c.jum_kem3,
                p.is_active = 1,
                p.updated_at = NOW()
        ");

        DB::statement("
            INSERT INTO products (
                sku, barcode, name, kd_kem1, nm_kem1, jum_kem1,
                kd_kem2, nm_kem2, jum_kem2, kd_kem3, nm_kem3, jum_kem3,
                is_active, created_at, updated_at
            )
            SELECT c.sku, c.barcode, c.name, c.kd_kem1, c.nm_kem1, c.jum_kem1,
                c.kd_kem2, c.nm_kem2, c.jum_kem2, c.kd_kem3, c.nm_kem3, c.jum_kem3,
                1, NOW(), NOW()
            FROM g2_canon c
            LEFT JOIN products p ON p.sku = c.sku
            WHERE p.id IS NULL
        ");
    }

    /**
     * @return array<string, mixed>
     */
    protected function payloadFromMas(object $row): array
    {
        return [
            'barcode' => $row->kd_bar !== '' && $row->kd_bar !== null ? $row->kd_bar : null,
            'name' => $row->nm_brg ?: $row->kd_brg,
            'kd_kem1' => (int) $row->kd_kem1 ?: 1,
            'nm_kem1' => $row->nm_kem1,
            'jum_kem1' => $row->jum_kem1 ?: 1,
            'kd_kem2' => $row->kd_kem2 !== '' && $row->kd_kem2 !== null ? (int) $row->kd_kem2 : null,
            'nm_kem2' => $row->nm_kem2 !== '' && $row->nm_kem2 !== null ? $row->nm_kem2 : null,
            'jum_kem2' => $row->jum_kem2,
            'kd_kem3' => $row->kd_kem3 !== '' && $row->kd_kem3 !== null ? (int) $row->kd_kem3 : null,
            'nm_kem3' => $row->nm_kem3 !== '' && $row->nm_kem3 !== null ? $row->nm_kem3 : null,
            'jum_kem3' => $row->jum_kem3,
            'is_active' => true,
        ];
    }

    protected function syncPrices(bool $dry): int
    {
        $join = '
            FROM mas_brg m
            INNER JOIN branches b ON CONVERT(b.kd_toko USING utf8mb4) COLLATE utf8mb4_unicode_ci
                = CONVERT(m.kd_toko USING utf8mb4) COLLATE utf8mb4_unicode_ci
            INNER JOIN products p ON p.sku = CONVERT(m.kd_brg USING utf8mb4) COLLATE utf8mb4_unicode_ci
        ';
        $n = (int) DB::selectOne("SELECT COUNT(*) AS c {$join}")->c;
        if ($dry || $n === 0) {
            return $n;
        }

        $hrgBeli = Schema::hasColumn('mas_brg', 'hrg_beli')
            ? 'IFNULL(m.hrg_beli, 0)'
            : '0';

        DB::statement("
            INSERT INTO branch_product_prices
                (branch_id, product_id, hrg_jum1, hrg_jum2, hrg_jum3, hrg_beli, created_at, updated_at)
            SELECT b.id, p.id,
                IFNULL(m.hrg_jum1, 0), IFNULL(m.hrg_jum2, 0), IFNULL(m.hrg_jum3, 0), {$hrgBeli},
                NOW(), NOW()
            {$join}
            ON DUPLICATE KEY UPDATE
                hrg_jum1 = VALUES(hrg_jum1),
                hrg_jum2 = VALUES(hrg_jum2),
                hrg_jum3 = VALUES(hrg_jum3),
                hrg_beli = VALUES(hrg_beli),
                updated_at = VALUES(updated_at)
        ");

        return $n;
    }
}
