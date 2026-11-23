<?php

namespace App\Console\Commands;

use App\Models\Budget;
use App\Models\DigitalOrder;
use App\Models\FactArAging;
use App\Models\FactSalesDaily;
use App\Models\FactStockDaily;
use App\Models\InterCompanyInvoice;
use App\Models\SfaOutlet;
use App\Services\Executive\EisFactsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Gelombang5AuditCommand extends Command
{
    protected $signature = 'erp:gelombang5-audit {--aggregate : Hitung fakta 14 hari}';

    protected $description = 'Gelombang 5: cek OMS, packing/omzet, IC posted, AR TO, fakta EIS, budget 5W2H';

    public function handle(EisFactsService $facts): int
    {
        if ($this->option('aggregate')) {
            $days = $facts->aggregateRange(now()->subDays(13)->toDateString(), now()->toDateString());
            $this->info("Fakta dihitung: {$days} hari");
        }

        $oms = Schema::hasTable('digital_orders') ? DigitalOrder::count() : 0;
        $omsShip = Schema::hasTable('digital_orders')
            ? DigitalOrder::whereIn('status', ['shipped', 'completed'])->count()
            : 0;
        $packing = Schema::hasTable('paket_ready') ? (int) DB::table('paket_ready')->count() : 0;
        $omzet = Schema::hasTable('mp_penjualan') ? (float) DB::table('mp_penjualan')->sum('omzet') : 0;
        $icPosted = Schema::hasTable('inter_company_invoices')
            ? InterCompanyInvoice::where('status', 'posted')->count()
            : 0;
        $icDraft = Schema::hasTable('inter_company_invoices')
            ? InterCompanyInvoice::where('status', 'draft')->count()
            : 0;
        $outlets = Schema::hasTable('sfa_outlets') ? SfaOutlet::count() : 0;
        $salesFacts = Schema::hasTable('fact_sales_daily') ? FactSalesDaily::count() : 0;
        $stockFacts = Schema::hasTable('fact_stock_daily') ? FactStockDaily::count() : 0;
        $arFacts = Schema::hasTable('fact_ar_aging') ? FactArAging::count() : 0;
        $budgets = Schema::hasTable('budgets') ? Budget::count() : 0;

        $this->info('OMS digital: '.$oms.' (terkirim/selesai '.$omsShip.')');
        $this->info('Packing paket_ready: '.$packing);
        $this->info('Omzet mp_penjualan: Rp '.number_format($omzet, 0, ',', '.'));
        $this->info('Invoice antar-PT posted: '.$icPosted.' · draf: '.$icDraft);
        $this->info('Outlet SFA: '.$outlets);
        $this->info('Fact sales: '.$salesFacts.' · stock: '.$stockFacts.' · AR: '.$arFacts);
        $this->info('Budget 5W2H: '.$budgets);

        $this->info('Exit 5 lokal: kirim OMS memotong PUSAT + IC posted; packing/omzet tampil di order digital; TO punya aging + komisi cash-in + IC; dashboard holding baca fact_* setelah aggregate.');

        return self::SUCCESS;
    }
}
