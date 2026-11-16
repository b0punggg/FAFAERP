<?php

namespace App\Services\Reports;

use App\Support\BranchContext;
use Illuminate\Support\Collection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReportService
{
    public function kdToko(): ?string
    {
        return BranchContext::kdToko();
    }

    public function salesByProduct(?string $kdToko, string $from, string $to): Collection
    {
        return DB::table('jual_brg')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->whereBetween('tgl_jual', [$from, $to])
            ->selectRaw('kd_brg, MAX(nm_brg) as nm_brg, SUM(jml_brg) as qty, SUM(tot_jual - disc) as revenue')
            ->groupBy('kd_brg')
            ->orderByDesc('revenue')
            ->get();
    }

    public function salesByCustomer(?string $kdToko, string $from, string $to): Collection
    {
        return DB::table('jual')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->whereBetween('tgl_jual', [$from, $to])
            ->selectRaw("COALESCE(NULLIF(kd_pel,''), '-') as kd_pel, COALESCE(NULLIF(nm_pel,''), 'Umum') as nm_pel, COUNT(*) as trx_count, SUM(tot_bayar) as revenue")
            ->groupBy('kd_pel', 'nm_pel')
            ->orderByDesc('revenue')
            ->get();
    }

    public function purchasesBySupplier(?string $kdToko, string $from, string $to): Collection
    {
        return DB::table('beli')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->whereBetween('tgl_fak', [$from, $to])
            ->selectRaw("COALESCE(NULLIF(kd_sup,''), '-') as kd_sup, COALESCE(NULLIF(nm_sup,''), 'Tanpa supplier') as nm_sup, COUNT(*) as trx_count, SUM(tot_bayar) as total")
            ->groupBy('kd_sup', 'nm_sup')
            ->orderByDesc('total')
            ->get();
    }

    public function salesProfitLines(?string $kdToko, string $from, string $to): array
    {
        $useLaba = Schema::hasTable('jual_brg') && Schema::hasColumn('jual_brg', 'laba');

        $query = DB::table('jual_brg as jb')
            ->when($kdToko, fn ($q) => $q->where('jb.kd_toko', $kdToko))
            ->whereBetween('jb.tgl_jual', [$from, $to]);

        if ($useLaba) {
            $rows = $query
                ->selectRaw('jb.no_fak, jb.tgl_jual, jb.kd_brg, jb.nm_brg, jb.jml_brg, (jb.tot_jual - jb.disc) as revenue, jb.laba as profit')
                ->orderByDesc('jb.tgl_jual')
                ->limit(500)
                ->get();
        } else {
            $tokoSql = $kdToko ? "'".addslashes($kdToko)."'" : 'jb.kd_toko';
            $rows = $query
                ->selectRaw("jb.no_fak, jb.tgl_jual, jb.kd_brg, jb.nm_brg, jb.jml_brg, (jb.tot_jual - jb.disc) as revenue,
                    COALESCE((SELECT bb.hrg_beli FROM beli_brg bb WHERE bb.kd_brg = jb.kd_brg AND bb.kd_toko = {$tokoSql} ORDER BY bb.tgl_fak DESC LIMIT 1), 0) as unit_cost")
                ->orderByDesc('jb.tgl_jual')
                ->limit(500)
                ->get()
                ->map(function ($row) {
                    $cost = (float) ($row->unit_cost ?? 0) * (float) $row->jml_brg;
                    $row->profit = (float) $row->revenue - $cost;

                    return $row;
                });
        }

        $totalRevenue = $rows->sum(fn ($r) => (float) $r->revenue);
        $totalProfit = $rows->sum(fn ($r) => (float) $r->profit);

        return compact('rows', 'totalRevenue', 'totalProfit', 'useLaba');
    }

    public function receivableCollections(?string $kdToko, string $from, string $to): Collection
    {
        if (! Schema::hasTable('piutang_payments')) {
            return collect();
        }

        return DB::table('piutang_payments as pp')
            ->join('jual as j', 'j.no_urut', '=', 'pp.sale_id')
            ->when($kdToko, fn ($q) => $q->where('pp.kd_toko', $kdToko))
            ->whereBetween('pp.tgl_bayar', [$from, $to])
            ->selectRaw('pp.id, pp.tgl_bayar, pp.amount, j.no_fak, j.tgl_jual, j.tot_bayar, j.byr_hutang')
            ->orderByDesc('pp.tgl_bayar')
            ->get();
    }

    public function salesByBagian(?string $kdToko, string $from, string $to): ?Collection
    {
        if (! Schema::hasTable('mas_brg') || ! Schema::hasColumn('mas_brg', 'id_bag')) {
            return null;
        }

        return DB::table('jual_brg as jb')
            ->join('mas_brg as mb', function ($j) use ($kdToko) {
                $j->on('jb.kd_brg', '=', 'mb.kd_brg');
                if ($kdToko) {
                    $j->where('mb.kd_toko', '=', $kdToko);
                }
            })
            ->leftJoin('bag_brg as bag', 'bag.no_urut', '=', 'mb.id_bag')
            ->when($kdToko, fn ($q) => $q->where('jb.kd_toko', $kdToko))
            ->whereBetween('jb.tgl_jual', [$from, $to])
            ->selectRaw("COALESCE(bag.nm_bag, 'Tanpa bagian') as nm_bag, SUM(jb.tot_jual - jb.disc) as revenue, SUM(jb.jml_brg) as qty")
            ->groupBy('nm_bag')
            ->orderByDesc('revenue')
            ->get();
    }

    public function salesByPayment(?string $kdToko, string $from, string $to): Collection
    {
        $cara = "CASE
            WHEN kd_bayar IN ('TUNAI', 'TEMPO', 'COD', 'QRIS', 'BNI') THEN kd_bayar
            WHEN COALESCE(byr_hutang, 0) > 0 THEN 'TEMPO'
            ELSE 'TUNAI'
        END";

        return DB::table('jual')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->whereBetween('tgl_jual', [$from, $to])
            ->selectRaw("{$cara} as cara, COUNT(*) as trx_count, SUM(tot_bayar) as revenue, SUM(COALESCE(byr_hutang, 0)) as sisa")
            ->groupByRaw($cara)
            ->orderByDesc('revenue')
            ->get();
    }

    public function salesByBrand(?string $kdToko, string $from, string $to): Collection
    {
        $this->ensureBrandReport();
        $brands = DB::table('brand_report')
            ->orderByRaw('CHAR_LENGTH(nm_brand) DESC')
            ->pluck('nm_brand')
            ->map(fn ($name) => (string) $name)
            ->filter(fn ($name) => $name !== '')
            ->values();

        $lines = DB::table('jual_brg')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->whereBetween('tgl_jual', [$from, $to])
            ->get(['nm_brg', 'jml_brg', 'tot_jual', 'disc']);

        $buckets = [];
        foreach ($lines as $line) {
            $name = mb_strtoupper((string) $line->nm_brg);
            $brand = 'Lainnya';
            foreach ($brands as $candidate) {
                if (mb_stripos($name, mb_strtoupper($candidate)) !== false) {
                    $brand = $candidate;
                    break;
                }
            }
            if (! isset($buckets[$brand])) {
                $buckets[$brand] = ['brand' => $brand, 'qty' => 0.0, 'revenue' => 0.0];
            }
            $buckets[$brand]['qty'] += (float) $line->jml_brg;
            $buckets[$brand]['revenue'] += (float) $line->tot_jual - (float) $line->disc;
        }

        return collect($buckets)
            ->sortByDesc('revenue')
            ->values()
            ->map(fn ($row) => (object) $row);
    }

    private function ensureBrandReport(): void
    {
        if (! Schema::hasTable('brand_report')) {
            Schema::create('brand_report', function (Blueprint $table) {
                $table->increments('no_urut');
                $table->string('nm_brand', 100)->unique();
            });
        }

        if ((int) DB::table('brand_report')->count() > 0) {
            return;
        }

        $now = collect($this->brandNames())->map(fn ($name) => ['nm_brand' => $name])->all();
        DB::table('brand_report')->insert($now);
    }

    /** @return list<string> */
    private function brandNames(): array
    {
        return [
            'AFIQA', 'ANDO', 'ANIMATE', 'ARMOD', 'AXE', 'BC OMG', 'BENING', 'BIORE', 'CARVIL',
            'CITRA', 'CLEAR', 'CLOSEUP', 'CUSSONS', 'DAZZLE ME', 'DOVE', 'EMINA', 'EUREKA',
            'EVANGELINE', 'FACETOLOGY', 'FAIR & LOVELY', 'G2G', 'GARNIER', 'GATSBY', 'GLAD2GLOW',
            'GLOW & LOVELY', 'HADALABO', 'HANASUI', 'HONOR', 'IMPLORA', 'JHONSONS', 'JIMAN PRO',
            'KAHF', 'KOJIESAN', 'LAURIER', 'LOGO', 'LUBRENA', 'Lunefa', 'MAKARIZO', 'MAKE OVER',
            'MARINA', 'MATO CARE', 'MIRANDA', 'MITU', 'MOELL', 'MONTIS', 'MY BABY', 'NEW ERA',
            'NIVEA', 'NPURE', 'NYU', 'OMG', 'OPTIMA PRO', 'PEPSODENT', 'PIGEON', 'PIGEON BABY',
            'PIXY', 'PONDS', 'PRO ATT', 'PUCELLE', 'PURBASARI', 'REXONA', 'SCARLET', 'SCORA',
            'SKINTIFIC', 'SLAVINA', 'SPEED', 'SUNLIGHT', 'SUNSILK', 'T-SOFT', 'TRESEMME',
            'VASELINE', 'VAUSTIN', 'VEET', 'VITALIS', 'VIVA', 'WARDAH', 'XI XIU', 'YOU', 'ZWITSAL',
        ];
    }

    public function promoSummary(?string $kdToko, string $from, string $to): Collection
    {
        $discSales = DB::table('jual')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->whereBetween('tgl_jual', [$from, $to])
            ->where('disc', '>', 0)
            ->selectRaw("'Diskon nota' as label, COUNT(*) as trx_count, SUM(disc) as total_disc, SUM(tot_bayar) as revenue")
            ->first();

        $promos = collect();
        if ($discSales) {
            $promos->push($discSales);
        }

        if (Schema::hasTable('disc_promo')) {
            $q = DB::table('disc_promo')->when($kdToko, fn ($query) => $query->where('kd_toko', $kdToko));
            if (Schema::hasColumn('disc_promo', 'is_active')) {
                $q->where('is_active', true);
            }
            $labelCol = Schema::hasColumn('disc_promo', 'nama_promo') ? 'nama_promo' : (Schema::hasColumn('disc_promo', 'nm_promo') ? 'nm_promo' : 'no_promo');
            $active = $q->selectRaw("{$labelCol} as label, 0 as trx_count, 0 as total_disc, 0 as revenue")->limit(20)->get();
            $promos = $promos->merge($active);
        }

        return $promos;
    }

    public function arApSnapshot(?string $kdToko): array
    {
        $ar = DB::table('jual')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->where('byr_hutang', '>', 0)
            ->selectRaw('COUNT(*) as count, SUM(byr_hutang) as total')
            ->first();

        $apBase = DB::table('beli')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->where('byr_hutang', '>', 0);

        $apRows = (clone $apBase)
            ->orderByDesc('tgl_fak')
            ->limit(100)
            ->get(['no_fak', 'nm_sup', 'byr_hutang', 'tgl_jt']);
        $apTotal = (float) (clone $apBase)->sum('byr_hutang');

        $arRows = DB::table('jual')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->where('byr_hutang', '>', 0)
            ->orderByDesc('tgl_jual')
            ->limit(100)
            ->get(['no_fak', 'tgl_jual', 'nm_pel', 'byr_hutang']);

        return [
            'ar_count' => (int) ($ar->count ?? 0),
            'ar_total' => (float) ($ar->total ?? 0),
            'ap_total' => $apTotal,
            'ar_rows' => $arRows,
            'ap_rows' => $apRows,
        ];
    }

    public function activityLog(?string $kdToko, string $from, string $to): Collection
    {
        $items = collect();

        if (Schema::hasTable('mutasi_gudang')) {
            $wh = BranchContext::warehouseId();
            DB::table('mutasi_gudang')
                ->when($wh, fn ($q) => $q->where('warehouse_id', $wh))
                ->whereBetween('tgl', [$from, $to])
                ->orderByDesc('tgl')
                ->limit(50)
                ->get()
                ->each(fn ($r) => $items->push((object) [
                    'tgl' => $r->tgl,
                    'type' => 'Mutasi '.$r->direction,
                    'ref' => 'product #'.$r->product_id,
                    'qty' => $r->qty,
                ]));
        }

        if (Schema::hasTable('sales_returns')) {
            DB::table('sales_returns')
                ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
                ->whereBetween('tgl_retur', [$from, $to])
                ->orderByDesc('tgl_retur')
                ->limit(30)
                ->get()
                ->each(fn ($r) => $items->push((object) [
                    'tgl' => $r->tgl_retur,
                    'type' => 'Retur jual',
                    'ref' => $r->no_retur,
                    'qty' => $r->total,
                ]));
        }

        if (Schema::hasTable('purchase_returns')) {
            DB::table('purchase_returns')
                ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
                ->whereBetween('tgl_retur', [$from, $to])
                ->limit(30)
                ->get()
                ->each(fn ($r) => $items->push((object) [
                    'tgl' => $r->tgl_retur,
                    'type' => 'Retur beli',
                    'ref' => $r->no_retur,
                    'qty' => $r->total,
                ]));
        }

        if (Schema::hasTable('stok_opname')) {
            $wh = BranchContext::warehouseId();
            DB::table('stok_opname')
                ->when($wh, fn ($q) => $q->where('warehouse_id', $wh))
                ->whereBetween('tgl_opname', [$from, $to])
                ->orderByDesc('tgl_opname')
                ->limit(20)
                ->get()
                ->each(fn ($r) => $items->push((object) [
                    'tgl' => $r->tgl_opname,
                    'type' => 'Opname '.$r->status,
                    'ref' => '#'.$r->id,
                    'qty' => null,
                ]));
        }

        return $items->sortByDesc('tgl')->values();
    }

    public function monthlyRecap(?string $kdToko, int $month, int $year): array
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to = date('Y-m-t', strtotime($from));

        $omset = (float) DB::table('jual')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->whereBetween('tgl_jual', [$from, $to])
            ->sum('tot_bayar');

        $beli = (float) DB::table('beli')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->whereBetween('tgl_fak', [$from, $to])
            ->sum('tot_bayar');

        $piutang = (float) DB::table('jual')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->whereBetween('tgl_jual', [$from, $to])
            ->sum('byr_hutang');

        $biaya = 0.0;
        if (Schema::hasTable('operating_expenses')) {
            $biaya = (float) DB::table('operating_expenses')
                ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
                ->whereBetween('tgl_biaya', [$from, $to])
                ->sum('nominal');
        }

        $profit = $this->salesProfitLines($kdToko, $from, $to);

        return [
            'from' => $from,
            'to' => $to,
            'omset' => $omset,
            'beli' => $beli,
            'piutang_period' => $piutang,
            'biaya' => $biaya,
            'laba_kotor_est' => $profit['totalProfit'],
            'month' => $month,
            'year' => $year,
        ];
    }

    public function monthlyPurchasesByProduct(?string $kdToko, int $month, int $year): Collection
    {
        $from = sprintf('%04d-%02d-01', $year, $month);
        $to = date('Y-m-t', strtotime($from));

        return DB::table('beli_brg')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->whereBetween('tgl_fak', [$from, $to])
            ->selectRaw('kd_brg, SUM(jml_brg) as qty_in, SUM(jml_brg * hrg_beli) as value_in')
            ->groupBy('kd_brg')
            ->orderByDesc('value_in')
            ->get();
    }
}
