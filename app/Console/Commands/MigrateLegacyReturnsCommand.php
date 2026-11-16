<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateLegacyReturnsCommand extends Command
{
    protected $signature = 'erp:migrate-legacy-returns {--fresh : Kosongkan sales_returns & lines sebelum import}';

    protected $description = 'Agregat retur_jual → sales_returns + sales_return_lines';

    public function handle(): int
    {
        if (! Schema::hasTable('retur_jual')) {
            $this->warn('Tabel retur_jual tidak ada — lewati.');

            return self::SUCCESS;
        }

        if (! Schema::hasTable('sales_returns') || ! Schema::hasTable('sales_return_lines')) {
            $this->error('Tabel sales_returns / sales_return_lines tidak ada. Jalankan php artisan migrate.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('sales_return_lines')->truncate();
            DB::table('sales_returns')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->warn('sales_returns & lines dikosongkan.');
        } elseif ((int) DB::table('sales_returns')->count() > 0) {
            $this->warn('sales_returns sudah berisi data — lewati (pakai --fresh).');

            return self::SUCCESS;
        }

        DB::statement("SET @legacy_sql_mode = @@SESSION.sql_mode");
        DB::statement("SET SESSION sql_mode = REPLACE(REPLACE(@legacy_sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', '')");

        $this->info('Header retur_jual → sales_returns…');

        DB::statement("
            INSERT INTO sales_returns (no_retur, no_fak, tgl_retur, kd_toko, total, ket, created_at, updated_at)
            SELECT
                g.no_returjual,
                g.no_fakjual,
                g.tgl_retur,
                g.kd_toko,
                g.total,
                'legacy import retur_jual',
                NOW(),
                NOW()
            FROM (
                SELECT
                    TRIM(r.no_returjual) AS no_returjual,
                    TRIM(r.no_fakjual) AS no_fakjual,
                    MIN(r.tgl_retur) AS tgl_retur,
                    CASE WHEN TRIM(r.kd_toko) = '' THEN 'IDTOKO-1' ELSE TRIM(r.kd_toko) END AS kd_toko,
                    SUM(
                        GREATEST(
                            0,
                            COALESCE(d.hrg_jual, 0) * r.qty_retur
                            - COALESCE(d.discrp, 0)
                        )
                    ) AS total
                FROM retur_jual r
                LEFT JOIN dum_jual d ON d.no_urut = r.no_urutjual
                WHERE TRIM(r.no_returjual) <> ''
                  AND r.tgl_retur IS NOT NULL
                  AND YEAR(r.tgl_retur) >= 1971
                GROUP BY TRIM(r.no_returjual), TRIM(r.no_fakjual), TRIM(r.kd_toko)
            ) g
        ");

        $this->info('Header retur: '.DB::table('sales_returns')->count());

        $this->info('Baris retur_jual → sales_return_lines…');

        DB::statement("
            INSERT INTO sales_return_lines (
                sales_return_id, kd_brg, nm_brg, kd_sat, qty, hrg_jual, subtotal
            )
            SELECT
                sr.id,
                COALESCE(NULLIF(TRIM(d.kd_brg), ''), 'UNKNOWN'),
                COALESCE(d.nm_brg, ''),
                COALESCE(d.kd_sat, 1),
                r.qty_retur,
                COALESCE(d.hrg_jual, 0),
                GREATEST(
                    0,
                    COALESCE(d.hrg_jual, 0) * r.qty_retur - COALESCE(d.discrp, 0)
                )
            FROM retur_jual r
            INNER JOIN sales_returns sr
                ON sr.no_retur = TRIM(r.no_returjual)
                AND sr.kd_toko = CASE WHEN TRIM(r.kd_toko) = '' THEN 'IDTOKO-1' ELSE TRIM(r.kd_toko) END
            LEFT JOIN dum_jual d ON d.no_urut = r.no_urutjual
            WHERE r.tgl_retur IS NOT NULL
              AND YEAR(r.tgl_retur) >= 1971
        ");

        DB::statement('SET SESSION sql_mode = @legacy_sql_mode');

        $this->info('Baris retur: '.DB::table('sales_return_lines')->count());

        return self::SUCCESS;
    }
}
