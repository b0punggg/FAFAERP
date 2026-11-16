<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateLegacyKasCommand extends Command
{
    protected $signature = 'erp:migrate-legacy-kas {--fresh : Kosongkan kas_entries sebelum import}';

    protected $description = 'Salin kas_harian → kas_entries (direction=in, saldo harian legacy)';

    public function handle(): int
    {
        if (! Schema::hasTable('kas_harian')) {
            $this->warn('Tabel kas_harian tidak ada — lewati.');

            return self::SUCCESS;
        }

        if (! Schema::hasTable('kas_entries')) {
            $this->error('Tabel kas_entries tidak ada. Jalankan php artisan migrate.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            DB::table('kas_entries')->truncate();
            $this->warn('kas_entries dikosongkan.');
        } elseif ((int) DB::table('kas_entries')->count() > 0) {
            $this->warn('kas_entries sudah berisi data — lewati (pakai --fresh).');

            return self::SUCCESS;
        }

        $this->info('Import kas_harian → kas_entries…');

        DB::statement("
            INSERT INTO kas_entries (kd_toko, tgl, direction, amount, description, created_at, updated_at)
            SELECT
                CASE WHEN TRIM(k.kd_toko) = '' THEN 'IDTOKO-1' ELSE TRIM(k.kd_toko) END,
                k.tgl_kas,
                'in',
                GREATEST(0, k.uang_kas),
                'Import saldo kas harian legacy',
                NOW(),
                NOW()
            FROM kas_harian k
            WHERE k.tgl_kas IS NOT NULL
              AND YEAR(k.tgl_kas) >= 1971
        ");

        $this->info('Kas entries: '.DB::table('kas_entries')->count().' baris.');

        return self::SUCCESS;
    }
}
