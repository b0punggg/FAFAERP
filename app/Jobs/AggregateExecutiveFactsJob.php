<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\FactSalesDaily;
use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class AggregateExecutiveFactsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $date = now()->subDay()->toDateString();
        $retailId = Company::where('type', 'retail')->value('id');

        if (! $retailId) {
            return;
        }

        $rows = Sale::query()
            ->select('kd_toko', DB::raw('SUM(tot_bayar) as revenue'))
            ->whereDate('tgl_jual', $date)
            ->groupBy('kd_toko')
            ->get();

        foreach ($rows as $row) {
            $branchId = DB::table('branches')->where('kd_toko', $row->kd_toko)->value('id');
            FactSalesDaily::updateOrCreate(
                ['fact_date' => $date, 'company_id' => $retailId, 'branch_id' => $branchId],
                ['revenue' => $row->revenue, 'cogs' => 0]
            );
        }
    }
}
