<?php

namespace App\Services\Executive;

use App\Models\Branch;
use App\Models\Company;
use App\Models\DigitalOrder;
use App\Models\FactAdsRoas;
use App\Models\FactArAging;
use App\Models\FactSalesDaily;
use App\Models\FactStockDaily;
use App\Models\Sale;
use App\Models\SfaOrder;
use App\Models\Warehouse;
use App\Services\FieldSales\SfaArService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EisFactsService
{
    public function __construct(protected SfaArService $sfaAr)
    {
    }

    public function aggregateRange(?string $from = null, ?string $to = null): int
    {
        $end = Carbon::parse($to ?: now()->toDateString())->startOfDay();
        $start = Carbon::parse($from ?: $end->copy()->subDays(13)->toDateString())->startOfDay();
        $days = 0;
        foreach (CarbonPeriod::create($start, $end) as $day) {
            $this->aggregateDate($day->toDateString());
            $days++;
        }

        return $days;
    }

    public function aggregateDate(string $date): void
    {
        $this->salesRetail($date);
        $this->salesDigital($date);
        $this->salesTo($date);
        $this->arRetail($date);
        $this->arTo($date);
        $this->stock($date);
        $this->ads($date);
    }

    private function salesRetail(string $date): void
    {
        $retail = Company::where('type', 'retail')->first();
        if (! $retail) {
            return;
        }

        $rows = Sale::query()
            ->select('kd_toko', DB::raw('SUM(tot_bayar) as revenue'))
            ->whereDate('tgl_jual', $date)
            ->groupBy('kd_toko')
            ->get();

        $seen = [];
        foreach ($rows as $row) {
            $branchId = Branch::where('kd_toko', $row->kd_toko)->value('id');
            $this->upsertSales($date, (int) $retail->id, $branchId ? (int) $branchId : null, (float) $row->revenue);
            $seen[] = $branchId ? (int) $branchId : 0;
        }

        if ($rows->isEmpty()) {
            $this->upsertSales($date, (int) $retail->id, null, 0);
        }
    }

    private function salesDigital(string $date): void
    {
        $digital = Company::where('type', 'digital')->first();
        if (! $digital) {
            return;
        }

        $oms = (float) DigitalOrder::query()
            ->where('company_id', $digital->id)
            ->whereIn('status', ['shipped', 'completed'])
            ->whereDate('updated_at', $date)
            ->sum('total');

        $mp = 0.0;
        if (Schema::hasTable('mp_penjualan')) {
            $mp = (float) DB::table('mp_penjualan')->whereDate('periode', $date)->sum('omzet');
        }

        $this->upsertSales($date, (int) $digital->id, null, $oms + $mp);
    }

    private function salesTo(string $date): void
    {
        $to = Company::where('type', 'field_sales')->first();
        if (! $to) {
            return;
        }

        $revenue = (float) SfaOrder::query()
            ->where('status', 'delivered')
            ->whereDate('updated_at', $date)
            ->sum('total');

        $this->upsertSales($date, (int) $to->id, null, $revenue);
    }

    private function arRetail(string $date): void
    {
        $retail = Company::where('type', 'retail')->first();
        if (! $retail) {
            return;
        }

        $asOf = Carbon::parse($date)->endOfDay();
        $buckets = [0.0, 0.0, 0.0];
        Sale::query()->where('byr_hutang', '>', 0)->orderBy('tgl_jual')->each(function (Sale $sale) use ($asOf, &$buckets) {
            $days = Carbon::parse($sale->tgl_jual)->startOfDay()->diffInDays($asOf->copy()->startOfDay());
            $amt = (float) $sale->byr_hutang;
            if ($days <= 30) {
                $buckets[0] += $amt;
            } elseif ($days <= 60) {
                $buckets[1] += $amt;
            } else {
                $buckets[2] += $amt;
            }
        });

        $this->upsertAr($date, (int) $retail->id, $buckets);
    }

    private function arTo(string $date): void
    {
        $to = Company::where('type', 'field_sales')->first();
        if (! $to) {
            return;
        }
        $aging = $this->sfaAr->agingAsOf($date);
        $this->upsertAr($date, (int) $to->id, [
            $aging['totals']['bucket_0_30'],
            $aging['totals']['bucket_31_60'],
            $aging['totals']['bucket_61_plus'],
        ]);
    }

    private function stock(string $date): void
    {
        if (! Schema::hasTable('inventory_balances') || ! Schema::hasTable('fact_stock_daily')) {
            return;
        }

        $rows = DB::table('inventory_balances')
            ->select('warehouse_id', DB::raw('SUM(qty_on_hand) as qty_total'))
            ->groupBy('warehouse_id')
            ->get();

        foreach ($rows as $row) {
            FactStockDaily::updateOrCreate(
                ['fact_date' => $date, 'warehouse_id' => $row->warehouse_id],
                ['qty_total' => $row->qty_total, 'value_total' => 0]
            );
        }

        if ($rows->isEmpty()) {
            foreach (Warehouse::all() as $warehouse) {
                FactStockDaily::updateOrCreate(
                    ['fact_date' => $date, 'warehouse_id' => $warehouse->id],
                    ['qty_total' => 0, 'value_total' => 0]
                );
            }
        }
    }

    private function ads(string $date): void
    {
        $digital = Company::where('type', 'digital')->first();
        if (! $digital || ! Schema::hasTable('ad_campaigns')) {
            return;
        }

        $spend = (float) DB::table('ad_campaigns')->where('company_id', $digital->id)->sum('spend');
        $revenue = (float) FactSalesDaily::where('fact_date', $date)->where('company_id', $digital->id)->sum('revenue');

        $row = FactAdsRoas::query()
            ->where('fact_date', $date)
            ->where('company_id', $digital->id)
            ->first() ?: new FactAdsRoas();
        $row->fill([
            'fact_date' => $date,
            'company_id' => $digital->id,
            'spend' => $spend,
            'attributed_revenue' => $revenue,
        ]);
        $row->save();
    }

    private function upsertSales(string $date, int $companyId, ?int $branchId, float $revenue): void
    {
        $query = FactSalesDaily::query()
            ->where('fact_date', $date)
            ->where('company_id', $companyId);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        } else {
            $query->whereNull('branch_id');
        }
        $row = $query->first() ?: new FactSalesDaily();
        $row->fill([
            'fact_date' => $date,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'revenue' => $revenue,
            'cogs' => $row->cogs ?? 0,
        ]);
        $row->save();
    }

    /** @param array{0:float,1:float,2:float} $buckets */
    private function upsertAr(string $date, int $companyId, array $buckets): void
    {
        $row = FactArAging::query()
            ->where('as_of_date', $date)
            ->where('company_id', $companyId)
            ->first() ?: new FactArAging();
        $row->fill([
            'as_of_date' => $date,
            'company_id' => $companyId,
            'bucket_0_30' => $buckets[0],
            'bucket_31_60' => $buckets[1],
            'bucket_61_plus' => $buckets[2],
        ]);
        $row->save();
    }
}
