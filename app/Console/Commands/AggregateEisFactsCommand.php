<?php

namespace App\Console\Commands;

use App\Services\Executive\EisFactsService;
use Illuminate\Console\Command;

class AggregateEisFactsCommand extends Command
{
    protected $signature = 'erp:aggregate-eis-facts {--date=} {--from=} {--to=}';

    protected $description = 'Hitung fakta EIS (penjualan, stok, AR, iklan) ke fact_* ';

    public function handle(EisFactsService $facts): int
    {
        $date = $this->option('date');
        if ($date) {
            $facts->aggregateDate($date);
            $this->info('EIS facts untuk '.$date);
        } else {
            $from = $this->option('from') ?: now()->subDays(13)->toDateString();
            $to = $this->option('to') ?: now()->toDateString();
            $days = $facts->aggregateRange($from, $to);
            $this->info("EIS facts {$from} s/d {$to} ({$days} hari)");
        }

        return self::SUCCESS;
    }
}
