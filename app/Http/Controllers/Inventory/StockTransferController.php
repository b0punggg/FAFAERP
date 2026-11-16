<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\StockTransferRequest;
use App\Models\Warehouse;
use App\Services\Inventory\StockTransferService;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    public function __construct(protected StockTransferService $transfers)
    {
    }

    public function index()
    {
        $items = StockTransferRequest::orderByDesc('id')->paginate(20);

        return view('admin.inventory.stock-transfers', compact('items'));
    }

    public function create()
    {
        $warehouses = Warehouse::orderBy('code')->get();

        return view('admin.inventory.stock-transfer-create', compact('warehouses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'from_warehouse_id' => 'required|exists:warehouses,id',
            'to_warehouse_id' => 'required|different:from_warehouse_id|exists:warehouses,id',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.qty' => 'required|numeric|min:0.01',
        ]);

        $this->transfers->create($data, $data['lines']);

        return redirect()->route('inventory.str.index')->with('success', 'STR dibuat.');
    }

    public function approve(StockTransferRequest $str)
    {
        try {
            $this->transfers->shipExisting($str);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'STR dikirim. Stok asal berkurang. Stok tujuan naik saat diterima.');
    }

    public function receive(StockTransferRequest $str)
    {
        try {
            $this->transfers->receive($str);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'STR diterima. Stok tujuan bertambah.');
    }
}
