<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Services\Sales\MemberPointService;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PointExchangeController extends Controller
{
    public function index()
    {
        return view('admin.retail.point-exchange');
    }

    public function searchMember(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $keyword = $request->get('keyword', '');

        $rows = Member::query()
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->where(function ($q) use ($keyword) {
                $q->where('kd_member', 'like', "%{$keyword}%")
                    ->orWhere('nm_member', 'like', "%{$keyword}%");
            })
            ->orderBy('nm_member')
            ->limit(15)
            ->get(['kd_member', 'nm_member', 'poin']);

        return response()->json($rows);
    }

    public function store(Request $request, MemberPointService $points)
    {
        $data = $request->validate([
            'kd_member' => 'required|string|max:50',
            'poin_tukar' => 'required|numeric|min:0.01',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $points->exchangePoints($data['kd_member'], (float) $data['poin_tukar'], $data['keterangan'] ?? '');

        return back()->with('success', 'Poin ditukar.');
    }

    public function history(Request $request)
    {
        abort_unless(Schema::hasTable('member_poin_history'), 501);

        $kdToko = BranchContext::kdToko();
        $kdMember = $request->get('kd_member');

        $rows = DB::table('member_poin_history')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->when($kdMember, fn ($q) => $q->where('kd_member', $kdMember))
            ->orderByDesc('no_urut')
            ->paginate(30)
            ->withQueryString();

        return view('admin.retail.point-history', compact('rows', 'kdMember'));
    }
}
