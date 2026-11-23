<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Gelombang4AssetsCommand extends Command
{
    protected $signature = 'erp:gelombang4-assets {--copy : Salin fafa-modern.css ke folder assets/ di root}';

    protected $description = 'Gelombang 4: cek CSS public/assets dan tautan gudang';

    public function handle(): int
    {
        $css = public_path('assets/css/fafa-modern.css');
        if (! is_file($css)) {
            $this->error('Hilang: public/assets/css/fafa-modern.css');

            return self::FAILURE;
        }
        $this->info('OK public/assets/css/fafa-modern.css ('.filesize($css).' byte)');

        $htaccess = base_path('.htaccess');
        $rewrite = is_file($htaccess) && str_contains((string) file_get_contents($htaccess), 'public/assets/');
        $this->info($rewrite
            ? 'OK .htaccess: /assets/ → public/assets/'
            : 'Peringatan: .htaccess belum me-rewrite /assets/ ke public/assets/');

        if ($this->option('copy')) {
            $destDir = base_path('assets/css');
            if (! is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            copy($css, $destDir.'/fafa-modern.css');
            $this->info('Disalin ke assets/css/fafa-modern.css (Hostinger tanpa rewrite).');
        }

        $this->info('INVENTORY_APP_URL='.config('inventory.url'));
        $this->info('SSO: '.url('/sso/gudang').' → gudang; gudang tombol Kasir FAFA → '.url('/sso/kasir'));
        $this->info('Username pemakai FAFA harus sama dengan users.username inventory.');

        return self::SUCCESS;
    }
}
