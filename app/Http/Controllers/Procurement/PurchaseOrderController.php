<?php

namespace App\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use App\Models\CatalogProduct;
use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Models\Warehouse;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderController extends Controller
{
    public function index()
    {
        $orders = PurchaseOrder::orderByDesc('id')->paginate(20);

        return view('admin.procurement.po-index', compact('orders'));
    }

    public function create()
    {
        $warehouse = Warehouse::where('is_central', true)->first();
        $companyId = BranchContext::companyId() ?? Company::where('type', 'retail')->value('id');

        return view('admin.procurement.po-create', compact('warehouse', 'companyId'));
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $rows = CatalogProduct::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")->orWhere('sku', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->limit(15)
            ->get(['id', 'sku', 'name']);

        return response()->json($rows);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.qty' => 'required|numeric|min:0.01',
            'lines.*.unit_cost' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'company_id' => $data['company_id'],
                'warehouse_id' => $data['warehouse_id'],
                'po_no' => 'PO-'.Str::upper(Str::random(8)),
                'order_date' => now()->toDateString(),
                'status' => 'draft',
            ]);

            foreach ($data['lines'] as $line) {
                DB::table('purchase_order_lines')->insert([
                    'purchase_order_id' => $po->id,
                    'product_id' => $line['product_id'],
                    'qty' => $line['qty'],
                    'unit_cost' => $line['unit_cost'] ?? 0,
                ]);
            }
        });

        return redirect()->route('procurement.po.index')->with('success', 'PO pusat dibuat.');
    }
}
