<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\FactAdsRoas;
use App\Models\FactArAging;
use App\Models\FactSalesDaily;
use App\Models\Sale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AggregateEisFactsCommand extends Command
{
    protected $signature = 'erp:aggregate-eis-facts {--date=}';

    protected $description = 'Aggregate nightly EIS fact tables';

    public function handle(): int
    {
        $date = $this->option('date') ?? now()->subDay()->toDateString();
        $retail = Company::where('type', 'retail')->first();

        if ($retail) {
            $rows = Sale::query()
                ->select('kd_toko', DB::raw('SUM(tot_bayar) as revenue'))
                ->whereDate('tgl_jual', $date)
                ->groupBy('kd_toko')
                ->get();

            foreach ($rows as $row) {
                $branchId = \App\Models\Branch::where('kd_toko', $row->kd_toko)->value('id');
                FactSalesDaily::updateOrCreate(
                    ['fact_date' => $date, 'company_id' => $retail->id, 'branch_id' => $branchId],
                    ['revenue' => $row->revenue, 'cogs' => 0]
                );
            }
        }

        FactArAging::updateOrCreate(
            ['as_of_date' => $date, 'company_id' => $retail?->id ?? 1],
            [
                'bucket_0_30' => (float) Sale::where('byr_hutang', '>', 0)->sum('byr_hutang'),
                'bucket_31_60' => 0,
                'bucket_61_plus' => 0,
            ]
        );

        $digital = Company::where('type', 'digital')->first();
        if ($digital) {
            FactAdsRoas::updateOrCreate(
                ['fact_date' => $date, 'company_id' => $digital->id],
                ['spend' => 0, 'attributed_revenue' => 0]
            );
        }

        $this->info('EIS facts aggregated for '.$date);

        return self::SUCCESS;
    }
}
