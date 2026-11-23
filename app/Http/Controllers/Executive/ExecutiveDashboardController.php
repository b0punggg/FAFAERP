<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\FactAdsRoas;
use App\Models\FactArAging;
use App\Models\FactSalesDaily;
use App\Models\FactStockDaily;
use App\Models\RopAlert;
use App\Services\Executive\EisFactsService;
use App\Support\Reports\ReportOutput;
use App\Support\Reports\ReportSheet;
use Illuminate\Http\Request;

class ExecutiveDashboardController extends Controller
{
    public function index(Request $request)
    {
        $sales = FactSalesDaily::query()
            ->leftJoin('branches', 'branches.id', '=', 'fact_sales_daily.branch_id')
            ->leftJoin('companies', 'companies.id', '=', 'fact_sales_daily.company_id')
            ->orderByDesc('fact_sales_daily.fact_date')
            ->limit(30)
            ->get([
                'fact_sales_daily.fact_date',
                'fact_sales_daily.revenue',
                'fact_sales_daily.cogs',
                'branches.name as branch_name',
                'companies.code as company_code',
            ]);
        $arRows = FactArAging::query()
            ->leftJoin('companies', 'companies.id', '=', 'fact_ar_aging.company_id')
            ->orderByDesc('fact_ar_aging.as_of_date')
            ->limit(6)
            ->get([
                'fact_ar_aging.as_of_date',
                'fact_ar_aging.bucket_0_30',
                'fact_ar_aging.bucket_31_60',
                'fact_ar_aging.bucket_61_plus',
                'companies.code as company_code',
            ]);
        $ar = $arRows->first();
        $stock = FactStockDaily::query()
            ->leftJoin('warehouses', 'warehouses.id', '=', 'fact_stock_daily.warehouse_id')
            ->orderByDesc('fact_stock_daily.fact_date')
            ->limit(20)
            ->get([
                'fact_stock_daily.fact_date',
                'fact_stock_daily.qty_total',
                'fact_stock_daily.value_total',
                'warehouses.code as warehouse_code',
                'warehouses.kd_toko',
            ]);
        $roas = FactAdsRoas::query()
            ->leftJoin('companies', 'companies.id', '=', 'fact_ads_roas.company_id')
            ->orderByDesc('fact_ads_roas.fact_date')
            ->limit(10)
            ->get([
                'fact_ads_roas.fact_date',
                'fact_ads_roas.spend',
                'fact_ads_roas.attributed_revenue',
                'companies.code as company_code',
            ]);
        $budgets = Budget::with('company')->orderByDesc('id')->limit(5)->get();
        $anomalies = RopAlert::query()
            ->leftJoin('branches', 'branches.id', '=', 'rop_alerts.branch_id')
            ->leftJoin('products', 'products.id', '=', 'rop_alerts.product_id')
            ->whereColumn('rop_alerts.qty_on_hand', '<=', 'rop_alerts.reorder_point')
            ->limit(10)
            ->get([
                'rop_alerts.qty_on_hand',
                'rop_alerts.reorder_point',
                'branches.name as branch_name',
                'products.sku as sku',
                'products.name as product_name',
            ]);

        $sheet = new ReportSheet('Dashboard holding', 'Ringkasan fakta terakhir', [], [], [], [
            [
                'title' => 'Penjualan harian',
                'columns' => [
                    ['key' => 'fact_date', 'label' => 'Tanggal', 'date' => true],
                    ['key' => 'company', 'label' => 'PT'],
                    ['key' => 'branch', 'label' => 'Gerai'],
                    ['key' => 'revenue', 'label' => 'Omset', 'money' => true],
                    ['key' => 'cogs', 'label' => 'HPP', 'money' => true],
                ],
                'rows' => $sales->map(fn ($row) => [
                    'fact_date' => $row->fact_date,
                    'company' => $row->company_code,
                    'branch' => $row->branch_name,
                    'revenue' => $row->revenue,
                    'cogs' => $row->cogs,
                ])->all(),
            ],
            [
                'title' => 'Stok di bawah batas',
                'columns' => [
                    ['key' => 'branch', 'label' => 'Gerai'],
                    ['key' => 'barang', 'label' => 'Barang'],
                    ['key' => 'qty', 'label' => 'Stok', 'qty' => true],
                    ['key' => 'rop', 'label' => 'Batas', 'qty' => true],
                ],
                'rows' => $anomalies->map(fn ($row) => [
                    'branch' => $row->branch_name,
                    'barang' => trim(($row->sku ? $row->sku.' · ' : '').$row->product_name),
                    'qty' => $row->qty_on_hand,
                    'rop' => $row->reorder_point,
                ])->all(),
            ],
            [
                'title' => 'Umur piutang',
                'columns' => [
                    ['key' => 'as_of', 'label' => 'Per tanggal', 'date' => true],
                    ['key' => 'company', 'label' => 'PT'],
                    ['key' => 'b1', 'label' => '0–30 hari', 'money' => true],
                    ['key' => 'b2', 'label' => '31–60 hari', 'money' => true],
                    ['key' => 'b3', 'label' => '61 hari atau lebih', 'money' => true],
                ],
                'rows' => $arRows->map(fn ($row) => [
                    'as_of' => $row->as_of_date,
                    'company' => $row->company_code,
                    'b1' => $row->bucket_0_30,
                    'b2' => $row->bucket_31_60,
                    'b3' => $row->bucket_61_plus,
                ])->all(),
            ],
            [
                'title' => 'Stok harian',
                'columns' => [
                    ['key' => 'fact_date', 'label' => 'Tanggal', 'date' => true],
                    ['key' => 'gudang', 'label' => 'Gudang'],
                    ['key' => 'qty', 'label' => 'Qty', 'qty' => true],
                ],
                'rows' => $stock->map(fn ($row) => [
                    'fact_date' => $row->fact_date,
                    'gudang' => $row->warehouse_code ?: $row->kd_toko,
                    'qty' => $row->qty_total,
                ])->all(),
            ],
            [
                'title' => 'Iklan',
                'columns' => [
                    ['key' => 'fact_date', 'label' => 'Tanggal', 'date' => true],
                    ['key' => 'company', 'label' => 'PT'],
                    ['key' => 'spend', 'label' => 'Belanja iklan', 'money' => true],
                    ['key' => 'revenue', 'label' => 'Omset terkait', 'money' => true],
                ],
                'rows' => $roas->map(fn ($row) => [
                    'fact_date' => $row->fact_date,
                    'company' => $row->company_code,
                    'spend' => $row->spend,
                    'revenue' => $row->attributed_revenue,
                ])->all(),
            ],
            [
                'title' => 'Program budget',
                'columns' => [
                    ['key' => 'title', 'label' => 'Program'],
                    ['key' => 'company', 'label' => 'PT'],
                    ['key' => 'status', 'label' => 'Status'],
                ],
                'rows' => $budgets->map(fn ($budget) => [
                    'title' => $budget->title,
                    'company' => $budget->company?->code,
                    'status' => $budget->statusLabel(),
                ])->all(),
            ],
        ], 'Belum ada fakta pada bagian ini.');

        if (ReportOutput::wants($request)) {
            return ReportOutput::send($request, $sheet);
        }

        return view('admin.executive.dashboard', compact('sales', 'ar', 'arRows', 'roas', 'budgets', 'anomalies', 'stock', 'sheet'));
    }

    public function refresh(EisFactsService $facts)
    {
        $days = $facts->aggregateRange(now()->subDays(13)->toDateString(), now()->toDateString());

        return back()->with('success', 'Fakta EIS dihitung ulang untuk '.$days.' hari (penjualan gerai/digital/TO, stok, AR).');
    }
}
