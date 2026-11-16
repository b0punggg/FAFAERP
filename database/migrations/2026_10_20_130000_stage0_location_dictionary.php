<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('warehouses') && ! Schema::hasColumn('warehouses', 'kd_toko')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->string('kd_toko', 50)->nullable()->after('code');
            });
        }

        $this->uniqueIndex('warehouses', 'warehouses_kd_toko_unique', 'kd_toko');
        $this->uniqueIndex('toko', 'toko_kd_toko_unique', 'kd_toko');
        $this->uniqueIndex('mas_brg', 'mas_brg_kd_brg_unique', 'kd_brg');
        $this->uniqueIndex('supplier', 'supplier_kd_sup_unique', 'kd_sup');

        $phone = Schema::hasColumn('toko', 'no_telp') ? 't.no_telp' : 'NULL AS no_telp';
        DB::statement('DROP VIEW IF EXISTS v_lokasi');
        DB::statement(<<<SQL
            CREATE VIEW v_lokasi AS
            SELECT
                w.id AS warehouse_id,
                w.company_id,
                w.branch_id,
                w.code AS warehouse_code,
                w.kd_toko,
                w.name AS warehouse_name,
                w.is_central,
                t.nm_toko,
                t.al_toko,
                {$phone},
                b.name AS branch_name
            FROM warehouses w
            INNER JOIN toko t ON t.kd_toko = w.kd_toko
            LEFT JOIN branches b ON b.id = w.branch_id
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_lokasi');

        $this->dropIndex('supplier', 'supplier_kd_sup_unique');
        $this->dropIndex('mas_brg', 'mas_brg_kd_brg_unique');
        $this->dropIndex('toko', 'toko_kd_toko_unique');
        $this->dropIndex('warehouses', 'warehouses_kd_toko_unique');

        if (Schema::hasTable('warehouses') && Schema::hasColumn('warehouses', 'kd_toko')) {
            Schema::table('warehouses', function (Blueprint $table) {
                $table->dropColumn('kd_toko');
            });
        }
    }

    private function uniqueIndex(string $table, string $index, string $column): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column) || $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index, $column) {
            $blueprint->unique($column, $index);
        });
    }

    private function dropIndex(string $table, string $index): void
    {
        if (! Schema::hasTable($table) || ! $this->hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($index) {
            $blueprint->dropUnique($index);
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        $row = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [DB::getDatabaseName(), $table, $index]
        );

        return $row !== null;
    }
};
