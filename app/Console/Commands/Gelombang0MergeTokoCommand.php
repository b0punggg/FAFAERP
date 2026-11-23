<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Gelombang0MergeTokoCommand extends Command
{
    protected $signature = 'erp:gelombang0-merge-toko
                            {--from= : Nama database sumber (dump gerai 3 atau 4 yang sudah diimport)}
                            {--kd-toko= : Kode gerai di target, contoh IDTOKO-3}
                            {--from-kd-toko= : Kode di dump sumber (sering IDTOKO-1). Kosong = sama dengan --kd-toko}
                            {--offset=10000000 : Ditambah ke kolom no_urut / id autoincrement}
                            {--fak-prefix= : Prefiks nomor nota (no_fak, no_jual) jika bentrok}
                            {--execute : Tulis ke DB target; tanpa flag hanya hitung (dry-run)}';

    protected $description = 'Salin baris satu gerai dari DB dump lain ke database .env (offset no_urut). Tidak menyalin pemakai.';

    /** @var list<string> */
    private array $skipTables = [
        'pemakai',
        'users',
        'sessions',
        'migrations',
        'branches',
        'warehouses',
        'companies',
        'products',
        'inventory_balances',
        'stock_limits',
        'kas_entries',
        'operating_expenses',
        'sales_returns',
        'purchase_returns',
        'hutang_payments',
        'piutang_payments',
        'sale_deletion_history',
        'sale_drafts',
        'store_orders',
        'branch_utility_settings',
        'stok_gudang',
        'file_log',
        'v_lokasi',
        'v_stok_lokasi',
    ];

    public function handle(): int
    {
        $from = (string) $this->option('from');
        $kdToko = (string) $this->option('kd-toko');
        $fromKd = (string) ($this->option('from-kd-toko') ?: $kdToko);
        $offset = (int) $this->option('offset');
        $prefix = (string) $this->option('fak-prefix');
        $execute = (bool) $this->option('execute');

        if ($from === '' || $kdToko === '') {
            $this->error('Wajib: --from=nama_db_sumber --kd-toko=IDTOKO-3');

            return self::FAILURE;
        }

        $targetDb = (string) DB::getDatabaseName();
        if (strcasecmp($from, $targetDb) === 0) {
            $this->error('--from tidak boleh sama dengan DB .env ('.$targetDb.').');

            return self::FAILURE;
        }

        config(['database.connections.g0src' => array_merge(config('database.connections.mysql'), [
            'database' => $from,
        ])]);
        DB::purge('g0src');

        try {
            DB::connection('g0src')->getPdo();
        } catch (\Throwable $e) {
            $this->error('Tidak bisa buka DB sumber '.$from.': '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info($execute ? 'EXECUTE' : 'DRY-RUN (tambah --execute untuk menulis)');
        $this->line("Sumber {$from} {$fromKd} → target {$targetDb} {$kdToko} offset={$offset}".($prefix !== '' ? " prefix={$prefix}" : ''));

        $tables = $this->tablesWithKdToko($from);
        $summary = [];

        foreach ($tables as $table) {
            if (in_array($table, $this->skipTables, true) || str_starts_with($table, 'v_')) {
                continue;
            }
            if (! Schema::hasTable($table)) {
                $this->warn("Lewati {$table}: tidak ada di target.");
                continue;
            }

            $count = (int) DB::connection('g0src')->table($table)->where('kd_toko', $fromKd)->count();
            $summary[] = [$table, $count];
            if ($count === 0) {
                continue;
            }
            if (! $execute) {
                continue;
            }

            $copied = $this->copyTable($table, $fromKd, $kdToko, $offset, $prefix);
            $this->line("  {$table}: {$copied}/{$count} baris");
        }

        $this->table(['tabel', 'baris sumber'], $summary);
        $this->reportCollisions($fromKd, $prefix, $offset);

        if ($execute) {
            $this->copyMissingToko($fromKd, $kdToko);
            $this->copyMissingMasters($fromKd, $kdToko);
            $this->info('Selesai. Lanjut: php artisan erp:sync-stage0 && php artisan erp:gelombang0-prepare --sync-qty --zero-central');
        } else {
            $this->comment('Dry-run. Import dump ke DB terpisah dulu, lalu ulang dengan --execute.');
        }

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function tablesWithKdToko(string $schema): array
    {
        $rows = DB::connection('g0src')->select(
            'SELECT TABLE_NAME AS t FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ? AND COLUMN_NAME = ? AND TABLE_NAME NOT LIKE ? ORDER BY TABLE_NAME',
            [$schema, 'kd_toko', 'v_%']
        );

        return array_map(fn ($r) => $r->t, $rows);
    }

    private function copyTable(string $table, string $fromKd, string $kdToko, int $offset, string $prefix): int
    {
        $srcCols = $this->columns('g0src', $table);
        $dstCols = $this->columns('mysql', $table);
        $cols = array_values(array_intersect($srcCols, $dstCols));
        if ($cols === []) {
            return 0;
        }

        $copied = 0;
        $orderCol = in_array('no_urut', $srcCols, true) ? 'no_urut' : (in_array('id', $srcCols, true) ? 'id' : $cols[0]);
        DB::connection('g0src')->table($table)->where('kd_toko', $fromKd)->orderBy($orderCol)->chunk(300, function ($rows) use ($table, $cols, $offset, $prefix, $kdToko, &$copied) {
            foreach ($rows as $row) {
                $payload = [];
                foreach ($cols as $col) {
                    $payload[$col] = $row->{$col};
                }
                if (array_key_exists('kd_toko', $payload)) {
                    $payload['kd_toko'] = $kdToko;
                }
                if (array_key_exists('no_urut', $payload) && is_numeric($payload['no_urut'])) {
                    $payload['no_urut'] = (int) $payload['no_urut'] + $offset;
                }
                if ($prefix !== '') {
                    foreach (['no_fak', 'no_fakjual', 'no_jual', 'no_dok'] as $n) {
                        if (! empty($payload[$n]) && is_string($payload[$n]) && ! str_starts_with($payload[$n], $prefix)) {
                            $payload[$n] = $prefix.$payload[$n];
                        }
                    }
                }
                if ($table === 'mas_brg' && in_array('kd_brg', $cols, true)) {
                    $exists = DB::table('mas_brg')
                        ->where('kd_toko', $payload['kd_toko'])
                        ->where('kd_brg', $payload['kd_brg'])
                        ->exists();
                    if ($exists) {
                        continue;
                    }
                }
                try {
                    DB::table($table)->insert($payload);
                    $copied++;
                } catch (\Throwable $e) {
                    $this->warn("{$table} skip: ".$e->getMessage());
                }
            }
        });

        return $copied;
    }

    private function copyMissingToko(string $fromKd, string $kdToko): void
    {
        if (DB::table('toko')->where('kd_toko', $kdToko)->exists()) {
            return;
        }
        $row = DB::connection('g0src')->table('toko')->where('kd_toko', $fromKd)->first()
            ?: DB::connection('g0src')->table('toko')->where('kd_toko', $kdToko)->first();
        if (! $row) {
            $this->warn("toko {$kdToko} tidak ada di sumber — tambah manual.");

            return;
        }
        $payload = ['kd_toko' => $kdToko, 'nm_toko' => $row->nm_toko ?? $kdToko];
        if (Schema::hasColumn('toko', 'al_toko')) {
            $payload['al_toko'] = $row->al_toko ?? '';
        }
        if (Schema::hasColumn('toko', 'no_telp')) {
            $payload['no_telp'] = $row->no_telp ?? '';
        }
        DB::table('toko')->insert($payload);
        $this->info("Baris toko {$kdToko} ditambahkan.");
    }

    private function copyMissingMasters(string $fromKd, string $kdToko): void
    {
        $this->copyMissingByKey('supplier', 'kd_sup', $fromKd, $kdToko);
        $this->copyMissingByKey('pelanggan', 'kd_pel', $fromKd, $kdToko);
        $this->copyMissingByKey('member', 'kd_member', $fromKd, $kdToko);
        $this->copyMissingByKey('crew', 'kd_crew', $fromKd, $kdToko);
    }

    private function copyMissingByKey(string $table, string $key, string $fromKd, string $kdToko): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $key) || ! $this->sourceHasTable($table)) {
            return;
        }
        $q = DB::connection('g0src')->table($table);
        if (Schema::connection('g0src')->hasColumn($table, 'kd_toko')) {
            $q->where('kd_toko', $fromKd);
        }
        $src = $q->get();
        $have = DB::table($table)->pluck($key)->all();
        $n = 0;
        foreach ($src as $row) {
            $k = $row->{$key} ?? null;
            if ($k === null || in_array($k, $have, true)) {
                continue;
            }
            try {
                $payload = (array) $row;
                unset($payload['no_urut'], $payload['id']);
                if (array_key_exists('kd_toko', $payload)) {
                    $payload['kd_toko'] = $kdToko;
                }
                DB::table($table)->insert($payload);
                $have[] = $k;
                $n++;
            } catch (\Throwable) {
                // skip duplicate
            }
        }
        if ($n) {
            $this->info("{$n} {$table} baru dari sumber (kode belum ada di target).");
        }
    }

    private function reportCollisions(string $kdToko, string $prefix, int $offset): void
    {
        $srcMax = 0;
        if ($this->sourceHasTable('jual')) {
            $srcMax = (int) DB::connection('g0src')->table('jual')->where('kd_toko', $kdToko)->max('no_urut');
        }
        $tgtMax = Schema::hasTable('jual') ? (int) DB::table('jual')->max('no_urut') : 0;
        if ($srcMax + $offset <= $tgtMax) {
            $this->warn("offset {$offset} terlalu kecil: sumber no_urut max {$srcMax} + offset ≤ target max {$tgtMax}.");
        }

        $from = (string) $this->option('from');
        $targetDb = (string) DB::getDatabaseName();
        foreach (['jual' => 'no_fak', 'mas_jual' => 'no_fakjual', 'beli' => 'no_fak'] as $table => $col) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $col) || ! $this->sourceHasTable($table)) {
                continue;
            }
            $srcExpr = $prefix === ''
                ? "s.`{$col}`"
                : "IF(s.`{$col}` LIKE ".DB::getPdo()->quote($prefix.'%').", s.`{$col}`, CONCAT(".DB::getPdo()->quote($prefix).", s.`{$col}`))";
            $hit = (int) (DB::selectOne(
                "SELECT COUNT(DISTINCT t.`{$col}`) AS c
                 FROM `{$targetDb}`.`{$table}` t
                 INNER JOIN `{$from}`.`{$table}` s ON t.`{$col}` = {$srcExpr}
                 WHERE s.kd_toko = ?",
                [$kdToko]
            )->c ?? 0);
            if ($hit > 0) {
                $this->warn("{$hit} {$table}.{$col} sudah ada di target".($prefix === '' ? ' — tambah --fak-prefix.' : ' setelah prefiks.'));
            } else {
                $this->line("{$table}.{$col}: tidak bentrok.");
            }
        }
    }

    private function sourceHasTable(string $table): bool
    {
        $db = (string) $this->option('from');
        $n = DB::connection('g0src')->select(
            'SELECT 1 AS ok FROM information_schema.tables WHERE table_schema = ? AND table_name = ? LIMIT 1',
            [$db, $table]
        );

        return $n !== [];
    }

    /** @return list<string> */
    private function columns(string $connection, string $table): array
    {
        $schema = $connection === 'g0src'
            ? (string) $this->option('from')
            : (string) DB::getDatabaseName();
        $rows = DB::connection($connection)->select(
            'SELECT COLUMN_NAME AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?',
            [$schema, $table]
        );

        return array_map(fn ($r) => $r->c, $rows);
    }
}
