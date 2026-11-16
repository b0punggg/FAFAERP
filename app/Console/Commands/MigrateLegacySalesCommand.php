<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateLegacySalesCommand extends Command
{
    protected $signature = 'erp:migrate-legacy-sales
                            {--fresh : Kosongkan jual & jual_brg sebelum migrasi}
                            {--force : Lewati konfirmasi TRUNCATE (--fresh)}
                            {--only= : headers|lines (default: keduanya)}';

    protected $description = 'Salin mas_jual → jual dan dum_jual (bayar=SUDAH) → jual_brg';

    public function handle(): int
    {
        if (! Schema::hasTable('mas_jual') || ! Schema::hasTable('dum_jual')) {
            $this->error('Tabel mas_jual / dum_jual tidak ada.');

            return self::FAILURE;
        }

        if (! Schema::hasTable('jual') || ! Schema::hasTable('jual_brg')) {
            $this->error('Tabel jual / jual_brg tidak ada. Jalankan php artisan migrate.');

            return self::FAILURE;
        }

        $only = $this->option('only');
        $runHeaders = ! $only || $only === 'headers';
        $runLines = ! $only || $only === 'lines';

        if ($this->option('fresh')) {
            if (! $this->option('force') && ! $this->confirm('TRUNCATE jual dan jual_brg?', true)) {
                return self::SUCCESS;
            }
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('jual_brg')->truncate();
            DB::table('jual')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            $this->warn('jual & jual_brg dikosongkan.');
        }

        $hasPelanggan = Schema::hasTable('pelanggan');

        DB::statement("SET @legacy_sql_mode = @@SESSION.sql_mode");
        DB::statement("SET SESSION sql_mode = REPLACE(REPLACE(@legacy_sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', '')");

        if ($runHeaders) {
            $existing = (int) DB::table('jual')->count();
            if ($existing > 0 && ! $this->option('fresh')) {
                $this->warn("jual sudah berisi {$existing} baris — lewati header (pakai --fresh untuk ulang).");
            } else {
                $this->info('Migrasi header mas_jual → jual…');
                $pelJoin = $hasPelanggan
                    ? 'LEFT JOIN pelanggan p ON p.kd_pel = m.kd_pel'
                    : '';
                $nmPel = $hasPelanggan ? 'COALESCE(p.nm_pel, \'\')' : "''";

                $sql = "
                    INSERT INTO jual (
                        no_urut, no_fak, tgl_jual, kd_pel, nm_pel, kd_toko,
                        tot_jual, disc, ppn, tot_bayar, byr_tunai, byr_hutang, ket,
                        kd_member, nm_member, kd_crew, nm_crew, poin_earned, poin_redeem
                    )
                    SELECT
                        m.no_urut,
                        m.no_fakjual,
                        CASE
                            WHEN m.tgl_jual IS NULL OR YEAR(m.tgl_jual) < 1971
                            THEN IF(m.execut IS NULL OR YEAR(m.execut) < 1971, '2000-01-01', DATE(m.execut))
                            ELSE m.tgl_jual
                        END,
                        NULLIF(TRIM(m.kd_pel), ''),
                        {$nmPel},
                        CASE WHEN TRIM(m.kd_toko) = '' THEN 'IDTOKO-1' ELSE TRIM(m.kd_toko) END,
                        LEAST(GREATEST(0, m.tot_jual), 9999999999.99),
                        LEAST(GREATEST(0, m.tot_disc), 9999999999.99),
                        0,
                        GREATEST(0, m.tot_jual - m.tot_disc + IFNULL(m.ongkir, 0)),
                        IF(
                            m.bayar_uang < 0 OR m.bayar_uang > 9999999999.99 OR m.bayar_uang > (m.tot_jual - m.tot_disc + IFNULL(m.ongkir, 0)) * 100,
                            GREATEST(0, m.tot_jual - m.tot_disc + IFNULL(m.ongkir, 0) - m.saldo_hutang),
                            m.bayar_uang
                        ),
                        LEAST(GREATEST(0, m.saldo_hutang), 9999999999.99),
                        TRIM(CONCAT(m.ket_bayar, IF(TRIM(m.trf) <> '', CONCAT(' | ', m.trf), ''))),
                        NULL, NULL, NULL, NULL, 0, 0
                    FROM mas_jual m
                    {$pelJoin}
                    WHERE m.no_fakjual IS NOT NULL AND TRIM(m.no_fakjual) <> ''
                ";
                DB::statement($sql);
                $maxId = (int) DB::table('jual')->max('no_urut');
                if ($maxId > 0) {
                    DB::statement('ALTER TABLE jual AUTO_INCREMENT = '.($maxId + 1));
                }
                $this->info('Header selesai: '.DB::table('jual')->count().' faktur.');
            }
        }

        if ($runLines) {
            $lineCount = (int) DB::table('jual_brg')->count();
            if ($lineCount > 0 && ! $this->option('fresh')) {
                $this->warn("jual_brg sudah berisi {$lineCount} baris — lewati lines (pakai --fresh).");
            } else {
                $this->info('Migrasi baris dum_jual → jual_brg (bayar=SUDAH)…');
                DB::statement("
                    INSERT INTO jual_brg (
                        no_fak, tgl_jual, kd_brg, nm_brg, kd_sat, jml_brg,
                        hrg_jual, disc, ppn, tot_jual, kd_toko
                    )
                    SELECT
                        d.no_fakjual,
                        CASE
                            WHEN d.tgl_jual IS NULL OR YEAR(d.tgl_jual) < 1971
                            THEN IF(d.execut IS NULL OR YEAR(d.execut) < 1971, '2000-01-01', DATE(d.execut))
                            ELSE d.tgl_jual
                        END,
                        d.kd_brg,
                        d.nm_brg,
                        IFNULL(d.kd_sat, 1),
                        d.qty_brg,
                        d.hrg_jual,
                        GREATEST(0, IFNULL(d.discrp, 0) + (d.hrg_jual * d.qty_brg * IFNULL(d.discitem, 0))),
                        0,
                        GREATEST(0, (d.hrg_jual * d.qty_brg) - IFNULL(d.discrp, 0) - (d.hrg_jual * d.qty_brg * IFNULL(d.discitem, 0))),
                        CASE WHEN TRIM(d.kd_toko) = '' THEN 'IDTOKO-1' ELSE TRIM(d.kd_toko) END
                    FROM dum_jual d
                    WHERE d.bayar = 'SUDAH'
                      AND (d.panding = 0 OR d.panding IS NULL OR d.panding = false)
                ");
                $this->info('Baris selesai: '.DB::table('jual_brg')->count().' item.');
            }
        }

        DB::statement('SET SESSION sql_mode = @legacy_sql_mode');

        $this->info('Selesai. Cek dashboard / sales / reports.');

        return self::SUCCESS;
    }
}
