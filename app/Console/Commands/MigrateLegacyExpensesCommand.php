<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateLegacyExpensesCommand extends Command
{
    protected $signature = 'erp:migrate-legacy-expenses {--fresh : Kosongkan operating_expenses sebelum import}';

    protected $description = 'Salin biaya_ops → operating_expenses';

    public function handle(): int
    {
        if (! Schema::hasTable('biaya_ops')) {
            $this->warn('Tabel biaya_ops tidak ada — lewati.');

            return self::SUCCESS;
        }

        if (! Schema::hasTable('operating_expenses')) {
            $this->error('Tabel operating_expenses tidak ada. Jalankan php artisan migrate.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            DB::table('operating_expenses')->truncate();
            $this->warn('operating_expenses dikosongkan.');
        } elseif ((int) DB::table('operating_expenses')->count() > 0) {
            $this->warn('operating_expenses sudah berisi data — lewati (pakai --fresh).');

            return self::SUCCESS;
        }

        $hasJenis = Schema::hasTable('jenis_biaya');

        $nmJenis = $hasJenis
            ? 'COALESCE(j.nm_jenis, \'\')'
            : "''";
        $join = $hasJenis
            ? 'LEFT JOIN jenis_biaya j ON j.id_jenis = b.id_jenis'
            : '';

        $this->info('Import biaya_ops → operating_expenses…');

        DB::statement("
            INSERT INTO operating_expenses (
                kd_toko, tgl_biaya, nominal, ket_biaya, id_jenis, nm_jenis, created_at, updated_at
            )
            SELECT
                TRIM(b.kd_toko),
                b.tgl_biaya,
                b.nominal,
                NULLIF(TRIM(b.ket_biaya), ''),
                LEAST(GREATEST(COALESCE(b.id_jenis, 11), 1), 65535),
                {$nmJenis},
                NOW(),
                NOW()
            FROM biaya_ops b
            {$join}
            WHERE b.tgl_biaya IS NOT NULL
              AND YEAR(b.tgl_biaya) >= 1971
        ");

        $this->info('Biaya operasional: '.DB::table('operating_expenses')->count().' baris.');

        return self::SUCCESS;
    }
}
