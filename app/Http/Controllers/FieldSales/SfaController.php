<?php

namespace App\Http\Controllers\FieldSales;

use App\Http\Controllers\Controller;
use App\Models\CatalogProduct;
use App\Models\Company;
use App\Models\SfaOrder;
use App\Models\SfaOrderLine;
use App\Models\SfaOutlet;
use App\Models\SfaVisit;
use App\Services\FieldSales\SfaArService;
use App\Services\Inventory\ChannelStockService;
use Illuminate\Http\Request;

class SfaController extends Controller
{
    public function __construct(
        protected ChannelStockService $stock,
        protected SfaArService $ar
    ) {
    }
    protected function toCompanyId(): int
    {
        return (int) Company::where('type', 'field_sales')->value('id');
    }

    public function outlets()
    {
        $outletChoices = SfaOutlet::where('company_id', $this->toCompanyId())->orderBy('name')->get();
        $outlets = SfaOutlet::where('company_id', $this->toCompanyId())->paginate(20);
        $orders = SfaOrder::with(['outlet', 'lines'])->orderByDesc('id')->limit(20)->get();
        $aging = $this->ar->agingAsOf();
        $commissions = $this->ar->recentCommissions();

        return view('admin.fieldsales.outlets', compact('outlets', 'orders', 'outletChoices', 'aging', 'commissions'));
    }

    public function storeOutlet(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:64',
            'name' => 'required|string|max:255',
            'credit_limit' => 'nullable|numeric|min:0',
        ]);

        SfaOutlet::create(array_merge($data, ['company_id' => $this->toCompanyId()]));

        return back()->with('success', 'Outlet TO ditambahkan.');
    }

    public function checkIn(Request $request)
    {
        $data = $request->validate([
            'sfa_outlet_id' => 'required|exists:sfa_outlets,id',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        SfaVisit::create([
            'sfa_outlet_id' => $data['sfa_outlet_id'],
            'sales_user_id' => auth()->id(),
            'checked_in_at' => now(),
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function createOrder(Request $request)
    {
        $data = $request->validate([
            'sfa_outlet_id' => 'required|exists:sfa_outlets,id',
            'total' => 'required|numeric|min:0',
            'kd_brg' => 'required|string|max:50',
            'qty' => 'required|numeric|min:0.01',
        ]);
        if (! CatalogProduct::where('sku', $data['kd_brg'])->exists()) {
            return back()->with('error', 'SKU tidak ada di katalog FAFA.');
        }

        $outlet = SfaOutlet::findOrFail($data['sfa_outlet_id']);
        $openAr = $this->ar->openAmount($outlet->id);
        $limit = (float) $outlet->credit_limit;
        $status = $limit > 0 && ($openAr + $data['total']) > $limit ? 'credit_hold' : 'approved';

        $order = SfaOrder::create([
            'sfa_outlet_id' => $outlet->id,
            'total' => $data['total'],
            'status' => $status,
        ]);
        SfaOrderLine::create([
            'sfa_order_id' => $order->id,
            'kd_brg' => $data['kd_brg'],
            'qty' => $data['qty'],
        ]);

        return back()->with('success', 'SO TO: '.$status);
    }

    public function deliver(SfaOrder $order)
    {
        try {
            $moved = $this->stock->deliverFieldOrder($order);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $moved ? 'Dikirim dari gudang kanvas.' : 'Order ini sudah dikirim.');
    }

    public function recordPayment(Request $request)
    {
        $data = $request->validate([
            'sfa_outlet_id' => 'required|exists:sfa_outlets,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
        ]);

        try {
            $this->ar->recordPayment(
                (int) $data['sfa_outlet_id'],
                (float) $data['amount'],
                $data['payment_date'],
                auth()->id()
            );
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Pelunasan dicatat. Komisi 2% dari cash-in dirilis.');
    }
}
