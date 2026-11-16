<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Services\Inventory\InventoryMovementService;
use App\Services\Utility\DeadStockService;
use App\Support\BranchContext;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        protected InventoryMovementService $inventory,
        protected DeadStockService $deadStock
    ) {
    }

    public function switchBranch(Request $request)
    {
        abort_unless($request->user()?->canSwitchContext(), 403);

        $branch = Branch::with('warehouses')
            ->where('is_active', true)
            ->whereNotNull('kd_toko')
            ->findOrFail($request->integer('branch_id'));
        abort_unless($request->user()->canAccessBranch($branch->id), 403);

        BranchContext::applyBranch($branch);

        return back();
    }

    public function index()
    {
        $kd_toko = BranchContext::kdToko();
        $warehouseId = BranchContext::warehouseId();
        $today = BranchContext::businessDate();
        $businessDate = $today;
        $currentYear = (int) date('Y');

        $totalInventory = 0;
        $monthStart = date('Y-m-01', strtotime($today));
        $nextMonth = date('Y-m-01', strtotime($monthStart.' +1 month'));

        $penjualanHariIni = (float) DB::table('jual')
            ->where('kd_toko', $kd_toko)
            ->whereDate('tgl_jual', $today)
            ->sum('tot_bayar');

        $penjualanBulanIni = (float) DB::table('jual')
            ->where('kd_toko', $kd_toko)
            ->where('tgl_jual', '>=', $monthStart)
            ->where('tgl_jual', '<', $nextMonth)
            ->sum('tot_bayar');

        $pembelianBulanIni = (float) DB::table('beli')
            ->where('kd_toko', $kd_toko)
            ->where('tgl_fak', '>=', $monthStart)
            ->where('tgl_fak', '<', $nextMonth)
            ->sum('tot_bayar');

        if ($warehouseId) {
            $summary = $this->inventory->stockSummaryForWarehouse($warehouseId);
            $totalJenisBarang = $summary['sku_with_stock'];
            $zeroStock = $summary['zero_stock_skus'];
            $lowStock = $summary['low_stock_skus'];
        } else {
            $totalJenisBarang = DB::table('beli_brg')
                ->where('kd_toko', $kd_toko)
                ->select('kd_brg', DB::raw('SUM(stok_jual) as total_stok'))
                ->groupBy('kd_brg')
                ->havingRaw('SUM(stok_jual) > 0')
                ->get()
                ->count();

            $zeroStock = DB::table('beli_brg')
                ->where('kd_toko', $kd_toko)
                ->select('kd_brg', DB::raw('SUM(stok_jual) as total_stok'))
                ->groupBy('kd_brg')
                ->havingRaw('SUM(stok_jual) = 0')
                ->get()
                ->count();

            $lowStock = DB::table('beli_brg')
                ->where('kd_toko', $kd_toko)
                ->select('kd_brg', DB::raw('SUM(stok_jual) as total_stok'))
                ->groupBy('kd_brg')
                ->havingRaw('SUM(stok_jual) > 0 AND SUM(stok_jual) <= 5')
                ->get()
                ->count();
        }

        $topProducts = DB::table('jual_brg')
            ->select('jual_brg.kd_brg', 'jual_brg.nm_brg', DB::raw('SUM(jml_brg) as qty_terjual'))
            ->where('jual_brg.kd_toko', $kd_toko)
            ->where('jual_brg.tgl_jual', '>=', $monthStart)
            ->where('jual_brg.tgl_jual', '<', $nextMonth)
            ->groupBy('jual_brg.kd_brg', 'jual_brg.nm_brg')
            ->orderByDesc('qty_terjual')
            ->limit(10)
            ->get();

        if ($warehouseId) {
            $zeroStockList = $this->inventory->zeroStockList($warehouseId, 10);
            $lowStockList = $this->inventory->lowStockList($warehouseId, 10);
        } else {
            $zeroStockList = DB::table('beli_brg')
                ->join('mas_brg', function ($j) use ($kd_toko) {
                    $j->on('beli_brg.kd_brg', '=', 'mas_brg.kd_brg')->where('mas_brg.kd_toko', '=', $kd_toko);
                })
                ->where('beli_brg.kd_toko', $kd_toko)
                ->select('beli_brg.kd_brg', 'mas_brg.nm_brg', DB::raw('SUM(beli_brg.stok_jual) as stok'))
                ->groupBy('beli_brg.kd_brg', 'mas_brg.nm_brg')
                ->havingRaw('SUM(beli_brg.stok_jual) = 0')
                ->limit(10)
                ->get();

            $lowStockList = DB::table('beli_brg')
                ->join('mas_brg', function ($j) use ($kd_toko) {
                    $j->on('beli_brg.kd_brg', '=', 'mas_brg.kd_brg')->where('mas_brg.kd_toko', '=', $kd_toko);
                })
                ->where('beli_brg.kd_toko', $kd_toko)
                ->select('beli_brg.kd_brg', 'mas_brg.nm_brg', DB::raw('SUM(beli_brg.stok_jual) as stok'))
                ->groupBy('beli_brg.kd_brg', 'mas_brg.nm_brg')
                ->havingRaw('SUM(beli_brg.stok_jual) > 0 AND SUM(beli_brg.stok_jual) <= 5')
                ->orderBy('stok')
                ->limit(10)
                ->get();
        }

        $piutangPelanggan = (float) DB::table('jual')
            ->where('kd_toko', $kd_toko)
            ->sum('byr_hutang');

        $chartData = [];
        $deadStockList = collect();

        return view('admin.dashboard', compact(
            'totalInventory',
            'totalJenisBarang',
            'penjualanHariIni',
            'pembelianBulanIni',
            'penjualanBulanIni',
            'zeroStock',
            'lowStock',
            'topProducts',
            'zeroStockList',
            'lowStockList',
            'piutangPelanggan',
            'chartData',
            'currentYear',
            'deadStockList',
            'businessDate'
        ));
    }
}
