<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'erp:backup-database {--output= : Path file .sql}';

    protected $description = 'Backup database MySQL (mysqldump) ke storage/app/backups';

    public function handle(): int
    {
        $host = config('database.connections.mysql.host', '127.0.0.1');
        $port = config('database.connections.mysql.port', '3306');
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');

        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $output = $this->option('output') ?: $dir.DIRECTORY_SEPARATOR.'backup-'.date('Ymd-His').'.sql';

        $mysqldump = $this->findMysqldump();
        if (! $mysqldump) {
            $this->error('mysqldump tidak ditemukan. Install MySQL client atau backup manual via phpMyAdmin.');

            return self::FAILURE;
        }

        $cmd = sprintf(
            '%s --single-transaction --quick -h%s -P%s -u%s %s %s > %s',
            escapeshellarg($mysqldump),
            escapeshellarg($host),
            escapeshellarg((string) $port),
            escapeshellarg($username),
            $password !== null && $password !== '' ? '-p'.escapeshellarg($password) : '',
            escapeshellarg($database),
            escapeshellarg($output)
        );

        exec($cmd, $lines, $code);

        if ($code !== 0 || ! is_file($output) || filesize($output) < 32) {
            $this->error('Backup gagal (exit '.$code.').');

            return self::FAILURE;
        }

        $this->info('Backup: '.$output);

        return self::SUCCESS;
    }

    protected function findMysqldump(): ?string
    {
        $candidates = ['mysqldump'];
        if (PHP_OS_FAMILY === 'Windows') {
            $laragon = getenv('LARAGON_ROOT') ?: 'C:\\laragon';
            $candidates[] = $laragon.'\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe';
            $candidates[] = $laragon.'\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe';
        }

        foreach ($candidates as $bin) {
            $where = PHP_OS_FAMILY === 'Windows' ? 'where.exe '.escapeshellarg($bin) : 'command -v '.escapeshellarg($bin);
            exec($where, $out, $code);
            if ($code === 0 && ! empty($out[0]) && is_executable(trim($out[0]))) {
                return trim($out[0]);
            }
            if (is_file($bin)) {
                return $bin;
            }
        }

        return null;
    }
}
