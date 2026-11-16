<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class MigrateLegacyTransactionsCommand extends Command
{
    protected $signature = 'erp:migrate-legacy-transactions
                            {--fresh : Truncate target tables first}
                            {--force : Lewati konfirmasi TRUNCATE penjualan}';

    protected $description = 'Jalankan migrasi penjualan + pembelian legacy ke tabel Laravel';

    public function handle(): int
    {
        $fresh = [];
        if ($this->option('fresh')) {
            $fresh['--fresh'] = true;
        }
        if ($this->option('force')) {
            $fresh['--force'] = true;
        }

        $sales = $this->call('erp:migrate-legacy-sales', $fresh);
        if ($sales !== self::SUCCESS) {
            return $sales;
        }

        return $this->call('erp:migrate-legacy-purchases', $fresh);
    }
}
