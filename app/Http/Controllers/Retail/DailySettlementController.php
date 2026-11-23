<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\DailySettlement;
use App\Services\Retail\DailySettlementService;
use App\Support\BranchContext;
use App\Support\StaffAccess;
use Illuminate\Http\Request;

class DailySettlementController extends Controller
{
    public function __construct(protected DailySettlementService $settlements)
    {
    }

    public function index(Request $request)
    {
        $branchId = BranchContext::branchId();
        $date = $request->get('settlement_date', date('Y-m-d'));
        $pos = $this->settlements->posTotals(BranchContext::kdToko(), $date);
        $today = $this->settlements->today($branchId, $date);
        $items = DailySettlement::when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('settlement_date')
            ->paginate(20);

        return view('admin.retail.settlements', compact('items', 'pos', 'date', 'today'));
    }

    public function store(Request $request)
    {
        $branchId = BranchContext::branchId();
        abort_unless($branchId, 403);

        $data = $request->validate([
            'settlement_date' => 'required|date',
            'physical_cash' => 'required|numeric|min:0',
        ]);
        $pos = $this->settlements->posTotals(BranchContext::kdToko(), $data['settlement_date']);

        DailySettlement::updateOrCreate(
            ['branch_id' => $branchId, 'settlement_date' => $data['settlement_date']],
            [
                'pos_cash_total' => $pos['pos_cash_total'],
                'qris_total' => $pos['qris_total'],
                'edc_total' => $pos['edc_total'],
                'physical_cash' => $data['physical_cash'],
                'status' => 'draft',
            ]
        );

        return back()->with('success', 'Tutup toko tersimpan. Selisih kas = fisik − POS.');
    }

    public function approve(DailySettlement $settlement)
    {
        abort_unless(StaffAccess::can(auth()->user(), 'store'), 403);
        $settlement->update(['status' => 'approved', 'verified_by' => auth()->id()]);

        return back()->with('success', 'Settlement disetujui kepala toko.');
    }
}
