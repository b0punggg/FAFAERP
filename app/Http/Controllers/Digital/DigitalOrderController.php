<?php

namespace App\Http\Controllers\Digital;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Models\CatalogProduct;
use App\Models\Company;
use App\Models\DigitalImport;
use App\Models\DigitalImportRow;
use App\Models\DigitalOrder;
use App\Models\DigitalOrderLine;
use App\Services\Digital\BigSellerImporter;
use App\Services\Digital\MarketplaceBridgeService;
use App\Services\Inventory\ChannelStockService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DigitalOrderController extends Controller
{
    public function __construct(
        protected ChannelStockService $stock,
        protected BigSellerImporter $sheets,
        protected MarketplaceBridgeService $marketplace
    ) {
    }

    public function index(Request $request)
    {
        $companyId = Company::where('type', 'digital')->value('id');
        $orders = DigitalOrder::with('lines')->where('company_id', $companyId)->orderByDesc('id')->paginate(20);

        $importList = $companyId
            ? DigitalImport::where('company_id', $companyId)->orderByDesc('id')->limit(20)->get()
            : collect();
        $currentImport = $request->filled('import')
            ? DigitalImport::where('company_id', $companyId)->find($request->import)
            : $importList->first();
        if ($currentImport && ! $importList->contains('id', $currentImport->id)) {
            $importList->prepend($currentImport);
        }

        $q = trim((string) $request->get('q', ''));
        $cocok = (string) $request->get('cocok', '');
        $sheetRows = new LengthAwarePaginator([], 0, 20);
        $central = collect();
        $sheetTotals = null;
        if ($currentImport) {
            $sheetRows = DigitalImportRow::where('digital_import_id', $currentImport->id)
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('sku', 'like', "%{$q}%")
                            ->orWhere('product_name', 'like', "%{$q}%")
                            ->orWhere('kd_brg', 'like', "%{$q}%");
                    });
                })
                ->when($cocok === 'ya', fn ($query) => $query->whereNotNull('kd_brg'))
                ->when($cocok === 'tidak', fn ($query) => $query->whereNull('kd_brg'))
                ->orderBy('product_name')
                ->paginate(20, ['*'], 'import_page')
                ->withQueryString();

            $codes = $sheetRows->getCollection()->pluck('kd_brg')->filter()->unique()->values();
            if ($codes->isNotEmpty()) {
                $central = DB::table('inventory_balances as ib')
                    ->join('products as p', 'p.id', '=', 'ib.product_id')
                    ->join('warehouses as w', 'w.id', '=', 'ib.warehouse_id')
                    ->where(function ($query) {
                        $query->where('w.code', 'CENTRAL')
                            ->orWhere('w.is_central', 1)
                            ->orWhere('w.kd_toko', config('erp.central_kd_toko', 'PUSAT'));
                    })
                    ->whereIn('p.sku', $codes)
                    ->pluck('ib.qty_on_hand', 'p.sku');
            }
            $sheetTotals = DB::table('digital_import_rows')
                ->where('digital_import_id', $currentImport->id)
                ->selectRaw('SUM(dana_bersih) as dana, SUM(orders_net) as orders, SUM(cancel_orders) as batal, SUM(refund_amount) as retur')
                ->first();
        }

        $packing = $this->marketplace->packingRows(15);
        $omzetMp = $this->marketplace->omzetRows(18);
        $omzetMpTotal = $this->marketplace->omzetTotal();

        return view('admin.digital.orders', compact(
            'orders',
            'importList',
            'currentImport',
            'sheetRows',
            'central',
            'sheetTotals',
            'q',
            'cocok',
            'packing',
            'omzetMp',
            'omzetMpTotal'
        ));
    }

    public function pullPacking()
    {
        $n = $this->marketplace->pullPackingToOms();

        return back()->with('success', $n
            ? $n.' paket packing masuk OMS (selesai, stok pusat tidak dipotong lagi).'
            : 'Tidak ada paket baru. Resi yang sudah ada di OMS dilewati.');
    }

    public function importSheet(Request $request)
    {
        $companyId = Company::where('type', 'digital')->value('id');
        abort_unless($companyId, 500, 'Company digital belum di-seed.');
        $data = $request->validate([
            'file' => 'required|file|max:8192',
            'channel' => 'nullable|string|max:64',
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
        ]);
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        if (! in_array($ext, ['csv', 'txt', 'xlsx'], true)) {
            return back()->with('error', 'Pakai file CSV atau Excel (.xlsx).');
        }

        try {
            $import = $this->sheets->import(
                $file->getRealPath(),
                $ext,
                (int) $companyId,
                trim((string) ($data['channel'] ?? '')),
                $data['period_start'] ?? null,
                $data['period_end'] ?? null,
                $file->getClientOriginalName()
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('digital.orders.index', ['import' => $import->id])
            ->with('success', 'Rekap diimpor: '.$import->rows_count.' baris, '.$import->matched_count.' cocok dengan katalog. Stok FAFA tidak berubah.');
    }

    public function store(Request $request)
    {
        $companyId = Company::where('type', 'digital')->value('id');
        abort_unless($companyId, 500, 'Company digital belum di-seed.');
        $data = $request->validate([
            'channel' => 'required|string|max:64',
            'external_order_id' => 'nullable|string|max:128',
            'total' => 'required|numeric|min:0',
            'kd_brg' => 'required|string|max:50',
            'qty' => 'required|numeric|min:0.01',
        ]);
        if (! CatalogProduct::where('sku', $data['kd_brg'])->exists()) {
            return back()->with('error', 'SKU tidak ada di katalog FAFA.');
        }

        $order = DigitalOrder::create([
            'company_id' => $companyId,
            'channel' => $data['channel'],
            'external_order_id' => $data['external_order_id'] ?? null,
            'total' => $data['total'],
            'status' => 'pending',
        ]);
        DigitalOrderLine::create([
            'digital_order_id' => $order->id,
            'kd_brg' => $data['kd_brg'],
            'qty' => $data['qty'],
        ]);

        return back()->with('success', 'Order digital dicatat. Stok pusat berkurang saat dikirim.');
    }

    public function ship(DigitalOrder $order)
    {
        try {
            $moved = $this->stock->shipDigital($order);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $moved ? 'Dikirim dari gudang pusat.' : 'Order ini sudah dikirim.');
    }

    public function complete(DigitalOrder $order)
    {
        if (! in_array($order->status, ['shipped', 'completed'], true)) {
            return back()->with('error', 'Selesaikan order setelah barang dikirim dari gudang pusat.');
        }
        $order->update(['status' => 'completed']);
        if ($code = request('affiliate_code')) {
            AffiliateCommission::create([
                'digital_order_id' => $order->id,
                'affiliate_code' => $code,
                'amount' => request('commission_amount', 0),
                'status' => 'accrued',
            ]);
        }

        return back()->with('success', 'Order completed; komisi affiliate (jika ada) diakru.');
    }
}
