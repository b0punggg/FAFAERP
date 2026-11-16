<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\CatalogProduct;
use App\Models\InventoryBalance;
use App\Support\BranchContext;

class InventoryController extends Controller
{
    public function index()
    {
        $warehouseId = BranchContext::warehouseId();
        $items = collect();

        if ($warehouseId) {
            $items = InventoryBalance::with('product')
                ->where('warehouse_id', $warehouseId)
                ->paginate(30);
        }

        return view('admin.retail.inventory', compact('items', 'warehouseId'));
    }

    public function legacyStock()
    {
        $kdToko = BranchContext::kdToko();
        $products = \App\Models\Product::where('kd_toko', $kdToko)
            ->orderBy('nm_brg')
            ->paginate(30);

        return view('admin.master.simple-index', [
            'title' => 'Stok Barang (Legacy)',
            'routePrefix' => 'retail.legacy-stock',
            'columns' => ['kd_brg' => 'SKU', 'nm_brg' => 'Nama', 'jml_brg' => 'Qty'],
            'items' => $products,
            'hideCreate' => true,
        ]);
    }
}

