<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleVoidController extends Controller
{
    public function requestVoid(Request $request, int $saleId)
    {
        $sale = Sale::findOrFail($saleId);
        $data = $request->validate(['reason' => 'required|string|max:500']);

        DB::table('sale_void_requests')->insert([
            'sale_id' => $sale->no_urut,
            'requested_by' => auth()->id(),
            'status' => 'pending',
            'reason' => $data['reason'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditLogger::log($sale, 'void_requested', null, ['reason' => $data['reason']]);

        return back()->with('success', 'Permintaan void menunggu approval manager.');
    }

    public function approve(int $requestId)
    {
        $row = DB::table('sale_void_requests')->where('id', $requestId)->first();
        abort_unless($row && $row->status === 'pending', 404);

        DB::table('sale_void_requests')->where('id', $requestId)->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Void disetujui.');
    }
}
