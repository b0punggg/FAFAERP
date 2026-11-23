<?php

namespace App\Console\Commands;

use App\Models\InventoryBalance;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class Gelombang0AuditCommand extends Command
{
    protected $signature = 'erp:gelombang0-audit
                            {--sources=fafa_src3:IDTOKO-3,fafa_src4:IDTOKO-4 : DB dump:kd_toko dipisah koma}
                            {--log-dump : Tulis baris freeze log}
                            {--gerai= : Label gerai untuk freeze log (contoh FAFA-3)}
                            {--closed-at= : Jam tutup gerai (Y-m-d H:i)}
                            {--dumped-at= : Jam dump SQL}
                            {--sql-file= : Nama file .sql}
                            {--petugas= : Siapa yang dump}';

    protected $description = 'Gelombang 0: status overlay, dump sumber, bentrok nota/supplier/member';

    public function handle(): int
    {
        $target = (string) DB::getDatabaseName();
        $this->info("Target .env: {$target}");
        $this->comment('Freeze per jam tutup gerai — docs/GELOMBANG_0_RUNBOOK.md');
        $this->newLine();

        $this->reportLocal();
        $this->probeSources((string) $this->option('sources'));

        if ($this->option('log-dump')) {
            return $this->writeFreezeLog();
        }

        return self::SUCCESS;
    }

    private function reportLocal(): void
    {
        $central = (string) config('erp.central_kd_toko', 'PUSAT');
        $kanvas = (string) config('erp.kanvas_kd_toko', 'KANVAS');
        $online = (string) config('erp.online_kd_toko', 'ONLINE');

        $rows = [];
        if (Schema::hasTable('toko')) {
            foreach (DB::table('toko')->orderBy('kd_toko')->get() as $t) {
                $jenis = 'gerai';
                if ($t->kd_toko === $central) {
                    $jenis = 'PUSAT';
                } elseif ($t->kd_toko === $kanvas) {
                    $jenis = 'kanvas';
                } elseif ($t->kd_toko === $online) {
                    $jenis = 'online';
                }
                $sku = Schema::hasTable('mas_brg')
                    ? (int) DB::table('mas_brg')->where('kd_toko', $t->kd_toko)->count()
                    : 0;
                $rows[] = [$t->kd_toko, $t->nm_toko ?? '', $jenis, $sku];
            }
        }
        if ($rows !== []) {
            $this->table(['kd_toko', 'nm_toko', 'jenis', 'sku mas_brg'], $rows);
        }

        if (Schema::hasTable('warehouses')) {
            $this->table(
                ['warehouse', 'kd_toko', 'pusat', 'branch', 'qty_sku', 'qty_sum'],
                Warehouse::query()->orderByDesc('is_central')->orderBy('kd_toko')->get()->map(function (Warehouse $w) {
                    $q = InventoryBalance::where('warehouse_id', $w->id);

                    return [
                        $w->code,
                        $w->kd_toko,
                        $w->is_central ? 'ya' : '',
                        $w->branch_id ?: '',
                        $q->count(),
                        round((float) $q->sum('qty_on_hand'), 2),
                    ];
                })
            );
        }

        $this->line('Backup uji: php artisan erp:backup-database');
        $log = storage_path('app/gelombang0/freeze-log.csv');
        $this->line('Freeze log: '.(is_file($log) ? $log : '(kosong — isi dengan --log-dump setelah dump SQL)'));
    }

    private function probeSources(string $raw): void
    {
        $pairs = array_filter(array_map('trim', explode(',', $raw)));
        if ($pairs === []) {
            return;
        }

        $this->newLine();
        $this->info('Dump sumber');

        foreach ($pairs as $pair) {
            $db = $pair;
            $kd = '';
            if (str_contains($pair, ':')) {
                [$db, $kd] = array_map('trim', explode(':', $pair, 2));
            }
            $ok = $this->openSource($db);
            if (! $ok) {
                $this->warn("  {$db}: belum ada / tidak bisa dibuka (import dump setelah jam tutup gerai).");
                continue;
            }

            $tokoList = DB::connection('g0audit')->table('toko')->pluck('kd_toko')->implode(', ');
            $this->line("  {$db}: OK  toko={$tokoList}");
            if ($kd !== '') {
                $this->auditPair($db, $kd);
            }
        }
    }

    private function openSource(string $database): bool
    {
        config(['database.connections.g0audit' => array_merge(config('database.connections.mysql'), [
            'database' => $database,
        ])]);
        DB::purge('g0audit');
        try {
            DB::connection('g0audit')->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function auditPair(string $db, string $kdToko): void
    {
        $srcCount = (int) DB::connection('g0audit')->table('toko')->where('kd_toko', $kdToko)->count();
        if ($srcCount === 0) {
            $this->warn("    kd_toko {$kdToko} tidak ada di {$db}.");

            return;
        }

        $tgtMax = Schema::hasTable('jual')
            ? (int) DB::table('jual')->max('no_urut')
            : 0;
        $srcMax = $this->sourceHas('jual')
            ? (int) DB::connection('g0audit')->table('jual')->where('kd_toko', $kdToko)->max('no_urut')
            : 0;
        $needOffset = $srcMax + 1;
        $this->line("    jual no_urut sumber max={$srcMax}; target max={$tgtMax}; offset aman ≥ {$needOffset}");

        $targetDb = (string) DB::getDatabaseName();
        foreach (['jual' => 'no_fak', 'beli' => 'no_fak'] as $table => $col) {
            if (! Schema::hasTable($table) || ! $this->sourceHas($table)) {
                continue;
            }
            $n = (int) (DB::selectOne(
                "SELECT COUNT(DISTINCT t.`{$col}`) AS c
                 FROM `{$targetDb}`.`{$table}` t
                 INNER JOIN `{$db}`.`{$table}` s ON t.`{$col}` = s.`{$col}`
                 WHERE s.kd_toko = ?",
                [$kdToko]
            )->c ?? 0);
            if ($n > 0) {
                $samples = DB::select(
                    "SELECT DISTINCT t.`{$col}` AS n
                     FROM `{$targetDb}`.`{$table}` t
                     INNER JOIN `{$db}`.`{$table}` s ON t.`{$col}` = s.`{$col}`
                     WHERE s.kd_toko = ? LIMIT 8",
                    [$kdToko]
                );
                $sample = implode(', ', array_map(fn ($r) => $r->n, $samples));
                $this->warn("    BENTROK {$table}.{$col}: {$n} nomor (contoh: {$sample}). Pakai --fak-prefix saat merge.");
            } else {
                $this->line("    {$table}.{$col}: tidak bentrok — --fak-prefix boleh dihilangkan.");
            }
        }

        $this->mapKey('supplier', 'kd_sup', 'nm_sup', $kdToko, false);
        $this->mapKey('pelanggan', 'kd_pel', 'nm_pel', $kdToko, true);
        $this->mapKey('member', 'kd_member', 'nm_member', $kdToko, true);
    }

    private function mapKey(string $table, string $key, string $name, string $kdToko, bool $filterToko): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $key) || ! $this->sourceHas($table)) {
            return;
        }
        $srcQ = DB::connection('g0audit')->table($table);
        if ($filterToko && Schema::connection('g0audit')->hasColumn($table, 'kd_toko')) {
            $srcQ->where('kd_toko', $kdToko);
        }
        $src = $srcQ->get([$key, $name]);
        $have = DB::table($table)->get([$key, $name])->keyBy($key);
        $new = 0;
        $same = 0;
        $conflict = 0;
        foreach ($src as $row) {
            $k = (string) $row->{$key};
            if (! $have->has($k)) {
                $new++;
                continue;
            }
            $existName = (string) ($have->get($k)->{$name} ?? '');
            $srcName = (string) ($row->{$name} ?? '');
            if ($existName !== '' && $srcName !== '' && strcasecmp($existName, $srcName) !== 0) {
                $conflict++;
            } else {
                $same++;
            }
        }
        $msg = "    {$table}: baru={$new} sama_kode={$same}";
        if ($conflict > 0) {
            $this->warn("{$msg} NAMA BEDA={$conflict} (kode sama, nama beda — jangan overwrite, petakan manual).");
        } else {
            $this->line($msg.' nama selaras.');
        }
    }

    private function sourceHas(string $table): bool
    {
        try {
            return Schema::connection('g0audit')->hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }

    private function writeFreezeLog(): int
    {
        $gerai = (string) $this->option('gerai');
        $closed = (string) $this->option('closed-at');
        $dumped = (string) $this->option('dumped-at');
        $file = (string) $this->option('sql-file');
        $who = (string) $this->option('petugas');
        if ($gerai === '' || $file === '') {
            $this->error('Wajib: --log-dump --gerai=FAFA-3 --sql-file=tokofafa_3.sql [--closed-at= --dumped-at= --petugas=]');

            return self::FAILURE;
        }

        $dir = storage_path('app/gelombang0');
        File::ensureDirectoryExists($dir);
        $path = $dir.DIRECTORY_SEPARATOR.'freeze-log.csv';
        if (! is_file($path)) {
            File::put($path, "gerai,jam_tutup,jam_dump,file_sql,petugas,dicatat\n");
        }
        $line = implode(',', [
            $this->csv($gerai),
            $this->csv($closed),
            $this->csv($dumped),
            $this->csv($file),
            $this->csv($who),
            $this->csv(now()->toDateTimeString()),
        ])."\n";
        File::append($path, $line);
        $this->info('Freeze log: '.$path);

        return self::SUCCESS;
    }

    private function csv(string $v): string
    {
        if (str_contains($v, ',') || str_contains($v, '"')) {
            return '"'.str_replace('"', '""', $v).'"';
        }

        return $v;
    }
}
