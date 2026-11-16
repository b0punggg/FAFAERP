<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Inventory\WarehouseDocumentService;
use Illuminate\Http\Request;

class WarehouseDocumentController extends Controller
{
    public function store(Request $request, WarehouseDocumentService $documents)
    {
        $data = $request->validate([
            'idempotency_key' => 'required|string|max:64',
            'type' => 'required|in:receipt,supplier_return,adjustment,opname,issue,transfer_ship,transfer_receive',
            'doc_no' => 'required_if:type,transfer_ship,transfer_receive|nullable|string|max:64',
            'kd_toko' => 'required_unless:type,transfer_ship,transfer_receive|string|max:50',
            'from_kd_toko' => 'required_if:type,transfer_ship,transfer_receive|string|max:50',
            'to_kd_toko' => 'required_if:type,transfer_ship,transfer_receive|string|max:50',
            'lines' => 'required|array|min:1',
            'lines.*.kd_brg' => 'required|string|max:50',
            'lines.*.qty' => 'required_unless:type,adjustment|numeric|min:0',
            'lines.*.qty_delta' => 'required_if:type,adjustment|numeric',
            'lines.*.kd_sat' => 'nullable',
        ]);

        $result = $documents->apply($data['idempotency_key'], $data['type'], $data);

        return response()->json($result, ($result['replayed'] ?? false) ? 200 : 201);
    }
}
