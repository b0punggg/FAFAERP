<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $bag = Schema::hasColumn('mas_brg', 'id_bag')
            ? 'COALESCE(b.id_bag, 0)'
            : '0';
        $bagJoin = Schema::hasColumn('mas_brg', 'id_bag')
            ? 'LEFT JOIN bag_brg bg ON bg.no_urut = b.id_bag'
            : '';
        $bagName = Schema::hasColumn('mas_brg', 'id_bag') ? 'bg.nm_bag' : 'NULL';

        DB::statement('DROP VIEW IF EXISTS v_stok_lokasi');
        DB::statement(<<<SQL
            CREATE VIEW v_stok_lokasi AS
            SELECT
                ib.id AS no_urut,
                p.sku COLLATE utf8mb4_unicode_ci AS kd_brg,
                COALESCE(b.nm_brg, p.name) COLLATE utf8mb4_unicode_ci AS nm_brg,
                COALESCE(NULLIF(b.kd_bar, ''), p.barcode, '') COLLATE utf8mb4_unicode_ci AS kd_bar,
                w.kd_toko COLLATE utf8mb4_unicode_ci AS kd_toko,
                COALESCE(t.nm_toko, w.name) COLLATE utf8mb4_unicode_ci AS nm_toko,
                ib.qty_on_hand AS qty,
                COALESCE(NULLIF(b.kd_kem1, ''), CAST(p.kd_kem1 AS CHAR)) COLLATE utf8mb4_unicode_ci AS kd_sat,
                COALESCE(NULLIF(b.nm_kem1, ''), p.nm_kem1, '') COLLATE utf8mb4_unicode_ci AS nm_sat,
                COALESCE(NULLIF(b.nm_kem1, ''), p.nm_kem1, '') COLLATE utf8mb4_unicode_ci AS nm_sat1,
                {$bag} AS id_bag,
                {$bagName} AS nm_bag,
                w.id AS warehouse_id,
                w.code COLLATE utf8mb4_unicode_ci AS warehouse_code,
                w.is_central
            FROM inventory_balances ib
            INNER JOIN products p ON p.id = ib.product_id
            INNER JOIN warehouses w ON w.id = ib.warehouse_id
            LEFT JOIN toko t ON t.kd_toko = w.kd_toko
            LEFT JOIN mas_brg b ON b.kd_brg = p.sku
            {$bagJoin}
            WHERE w.kd_toko IS NOT NULL
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_stok_lokasi');
    }
};
