<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\CatalogProduct;
use App\Services\Inventory\InventoryMovementService;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockAdjustmentController extends Controller
{
    public function __construct(protected InventoryMovementService $inventory)
    {
    }

    public function index(Request $request)
    {
        $warehouseId = BranchContext::warehouseId();
        $filter = $request->get('filter', 'all');
        $items = collect();

        if ($warehouseId) {
            $items = $this->inventory->stockReportQuery($warehouseId, $filter)->paginate(40)->withQueryString();
        }

        $movements = collect();
        if ($warehouseId && Schema::hasTable('mutasi_gudang')) {
            $movements = DB::table('mutasi_gudang')
                ->where('warehouse_id', $warehouseId)
                ->orderByDesc('id')
                ->limit(20)
                ->get();
        }

        $products = CatalogProduct::orderBy('name')->limit(200)->get(['id', 'sku', 'name']);

        return view('admin.retail.stock-adjustments', compact('items', 'movements', 'warehouseId', 'filter', 'products'));
    }

    public function store(Request $request)
    {
        $warehouseId = BranchContext::warehouseId();
        abort_unless($warehouseId && Schema::hasTable('mutasi_gudang'), 403);

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'direction' => 'required|in:in,out',
            'qty' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
            'tgl' => 'required|date',
        ]);

        $product = CatalogProduct::findOrFail($data['product_id']);

        DB::transaction(function () use ($warehouseId, $data, $product) {
            $id = DB::table('mutasi_gudang')->insertGetId([
                'warehouse_id' => $warehouseId,
                'tgl' => $data['tgl'],
                'direction' => $data['direction'],
                'product_id' => $data['product_id'],
                'qty' => $data['qty'],
                'reference' => $data['reference'] ?? 'adjustment',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $kdToko = BranchContext::kdToko();
            if ($data['direction'] === 'in') {
                $this->inventory->restoreForSaleLine($product->sku, (float) $data['qty'], 1, $kdToko, 'adj-in:'.$id);
            } else {
                $this->inventory->issuePurchaseReturnLine($product->sku, (float) $data['qty'], 1, $kdToko, 'adj-out:'.$id);
            }
        });

        return back()->with('success', 'Penyesuaian stok tercatat. Untuk antar gudang gunakan STR.');
    }
}
