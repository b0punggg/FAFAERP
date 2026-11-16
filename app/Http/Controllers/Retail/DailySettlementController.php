<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\DailySettlement;
use App\Support\BranchContext;
use Illuminate\Http\Request;

class DailySettlementController extends Controller
{
    public function index()
    {
        $branchId = BranchContext::branchId();
        $items = DailySettlement::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('settlement_date')
            ->paginate(20);

        return view('admin.retail.settlements', compact('items'));
    }

    public function store(Request $request)
    {
        $branchId = BranchContext::branchId();
        abort_unless($branchId, 403);

        $data = $request->validate([
            'settlement_date' => 'required|date',
            'pos_cash_total' => 'required|numeric|min:0',
            'physical_cash' => 'required|numeric|min:0',
            'qris_total' => 'nullable|numeric|min:0',
            'edc_total' => 'nullable|numeric|min:0',
        ]);

        DailySettlement::updateOrCreate(
            ['branch_id' => $branchId, 'settlement_date' => $data['settlement_date']],
            array_merge($data, ['status' => 'draft'])
        );

        return back()->with('success', 'Settlement harian disimpan.');
    }

    public function approve(DailySettlement $settlement)
    {
        $settlement->update(['status' => 'approved', 'verified_by' => auth()->id()]);

        return back()->with('success', 'Settlement disetujui kepala toko.');
    }
}
