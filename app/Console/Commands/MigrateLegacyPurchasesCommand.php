<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateLegacyPurchasesCommand extends Command
{
    protected $signature = 'erp:migrate-legacy-purchases {--fresh : Kosongkan tabel beli sebelum migrasi}';

    protected $description = 'Buat header beli dari agregat beli_brg (detail tetap di beli_brg)';

    public function handle(): int
    {
        if (! Schema::hasTable('beli_brg')) {
            $this->error('Tabel beli_brg tidak ada.');

            return self::FAILURE;
        }

        if (! Schema::hasTable('beli')) {
            $this->error('Tabel beli tidak ada.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('beli')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->warn('Tabel beli dikosongkan.');
        }

        $existing = (int) DB::table('beli')->count();
        if ($existing > 0 && ! $this->option('fresh')) {
            $this->warn("beli sudah berisi {$existing} baris — lewati (pakai --fresh).");

            return self::SUCCESS;
        }

        $hasSupplier = Schema::hasTable('supplier');
        $supJoin = $hasSupplier ? 'LEFT JOIN supplier s ON s.kd_sup = g.kd_sup' : '';
        $nmSup = $hasSupplier ? 'COALESCE(s.nm_sup, \'\')' : "''";

        $this->info('Agregasi beli_brg → beli…');

        DB::statement("
            INSERT INTO beli (
                no_fak, tgl_fak, kd_sup, nm_sup, kd_toko,
                tot_beli, disc1, disc2, ppn, tot_bayar, ket
            )
            SELECT
                g.no_fak,
                g.tgl_fak,
                g.kd_sup,
                {$nmSup},
                g.kd_toko,
                g.tot_beli,
                0,
                0,
                0,
                g.tot_beli,
                CONCAT('legacy import ', g.line_count, ' lines')
            FROM (
                SELECT
                    b.no_fak,
                    MIN(b.tgl_fak) AS tgl_fak,
                    MAX(NULLIF(TRIM(b.kd_sup), '')) AS kd_sup,
                    TRIM(b.kd_toko) AS kd_toko,
                    SUM(b.jml_brg * b.hrg_beli) AS tot_beli,
                    COUNT(*) AS line_count
                FROM beli_brg b
                GROUP BY b.no_fak, TRIM(b.kd_toko)
            ) g
            {$supJoin}
        ");

        $this->info('Header beli: '.DB::table('beli')->count().' faktur.');

        if (Schema::hasColumn('beli_brg', 'expdate') && Schema::hasColumn('beli_brg', 'tgl_exp')) {
            DB::statement("
                UPDATE beli_brg
                SET tgl_exp = expdate
                WHERE YEAR(expdate) >= 1971
                  AND (tgl_exp IS NULL OR YEAR(tgl_exp) < 1971)
            ");
            $this->info('tgl_exp diselaraskan dari expdate (batch beli).');
        }

        return self::SUCCESS;
    }
}
