<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MigrateLegacyAllCommand extends Command
{
    protected $signature = 'erp:migrate-legacy-all
                            {--fresh : Kosongkan tabel target ERP sebelum import (transaksi + modul opsional)}
                            {--force : Lewati konfirmasi (--fresh)}
                            {--skip-seed : Jangan jalankan db:seed (foundation + CoA)}
                            {--skip-catalog : Lewati erp:migrate-legacy-catalog}
                            {--skip-transactions : Lewati penjualan & pembelian}
                            {--skip-inventory : Lewati inventory:sync-legacy-qty}
                            {--with-kas : Import kas_harian → kas_entries}
                            {--with-expenses : Import biaya_ops → operating_expenses}
                            {--with-returns : Import retur_jual → sales_returns}
                            {--dedupe=barcode : barcode|sku untuk katalog}
                            {--kd-toko= : Batasi sync stok ke satu gerai}';

    protected $description = 'Jalankan migrasi data legacy → Laravel (foundation, katalog, transaksi, stok, modul opsional)';

    /** @var array<string, string> */
    protected array $countTables = [
        'mas_jual' => 'Penjualan legacy (header)',
        'dum_jual' => 'Penjualan legacy (baris)',
        'mas_brg' => 'Master barang legacy',
        'beli_brg' => 'Pembelian legacy (baris)',
        'jual' => 'Penjualan Laravel',
        'jual_brg' => 'Baris penjualan Laravel',
        'beli' => 'Header pembelian Laravel',
        'products' => 'Katalog ERP products',
        'kas_harian' => 'Kas harian legacy',
        'kas_entries' => 'Kas Laravel',
        'biaya_ops' => 'Biaya legacy',
        'operating_expenses' => 'Biaya Laravel',
        'retur_jual' => 'Retur legacy',
        'sales_returns' => 'Retur Laravel',
    ];

    public function handle(): int
    {
        $this->line('');
        $this->info('=== Migrasi legacy → Laravel (FAFA ERP) ===');
        $this->line('Database: '.config('database.connections.'.config('database.default').'.database'));
        $this->printCounts('Sebelum');

        if ($this->option('fresh') && ! $this->option('force')) {
            if (! $this->confirm('--fresh akan mengosongkan tabel target (jual, beli, modul opsional). Lanjut?', false)) {
                $this->warn('Dibatalkan.');

                return self::SUCCESS;
            }
        }

        $freshArgs = $this->option('fresh') ? ['--fresh' => true] : [];

        if (! $this->option('skip-seed')) {
            $this->newLine();
            $this->info('[1/6] Foundation: db:seed (branches, gudang, CoA sample)…');
            if ($this->call('db:seed') !== self::SUCCESS) {
                return self::FAILURE;
            }
        } else {
            $this->warn('[1/6] db:seed dilewati.');
        }

        if (! $this->option('skip-catalog')) {
            $this->newLine();
            $this->info('[2/6] Katalog: mas_brg → products / harga / stok ERP…');
            if ($this->call('erp:migrate-legacy-catalog', ['--dedupe' => $this->option('dedupe')]) !== self::SUCCESS) {
                return self::FAILURE;
            }
        } else {
            $this->warn('[2/6] Katalog dilewati.');
        }

        if (! $this->option('skip-transactions')) {
            $this->newLine();
            $this->info('[3/6] Transaksi: mas_jual/dum_jual → jual/jual_brg, beli_brg → beli…');
            if ($this->option('fresh')) {
                $freshArgs['--force'] = true;
            }
            if ($this->call('erp:migrate-legacy-transactions', $freshArgs) !== self::SUCCESS) {
                return self::FAILURE;
            }
        } else {
            $this->warn('[3/6] Transaksi dilewati.');
        }

        if (! $this->option('skip-inventory')) {
            $this->newLine();
            $this->info('[4/6] Reconcile stok ERP dari mas_brg…');
            $syncArgs = [];
            if ($this->option('kd-toko')) {
                $syncArgs['--kd-toko'] = $this->option('kd-toko');
            }
            if ($this->call('inventory:sync-legacy-qty', $syncArgs) !== self::SUCCESS) {
                return self::FAILURE;
            }
        } else {
            $this->warn('[4/6] Sync inventory dilewati.');
        }

        $this->newLine();
        $this->info('[5/6] Modul opsional…');

        $optional = [
            'with-kas' => 'erp:migrate-legacy-kas',
            'with-expenses' => 'erp:migrate-legacy-expenses',
            'with-returns' => 'erp:migrate-legacy-returns',
        ];

        $ranOptional = false;
        foreach ($optional as $flag => $command) {
            if (! $this->option($flag)) {
                continue;
            }
            $ranOptional = true;
            if ($this->call($command, $freshArgs) !== self::SUCCESS) {
                return self::FAILURE;
            }
        }

        if (! $ranOptional) {
            $this->line('  (tidak ada — tambahkan --with-kas, --with-expenses, --with-returns jika perlu)');
        }

        $this->newLine();
        $this->info('[6/6] Ringkasan');
        $this->printCounts('Sesudah');
        $this->newLine();
        $this->comment('Master (supplier, pelanggan, member, crew) tetap di tabel legacy — Laravel membaca langsung.');
        $this->comment('Cek: /dashboard, /sales, /purchases, /products, /retail/kas (jika --with-kas).');
        $this->info('Selesai.');

        return self::SUCCESS;
    }

    protected function printCounts(string $label): void
    {
        $this->table(
            ['Tabel', 'Keterangan', $label],
            collect($this->countTables)->map(function ($desc, $table) use ($label) {
                if (! Schema::hasTable($table)) {
                    return [$table, $desc, '— (tidak ada)'];
                }

                return [$table, $desc, number_format((int) DB::table($table)->count(), 0, ',', '.')];
            })->values()->all()
        );
    }
}
