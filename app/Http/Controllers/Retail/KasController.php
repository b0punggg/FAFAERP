<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Services\Finance\PostingService;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class KasController extends Controller
{
    public function __construct(protected PostingService $posting)
    {
    }

    public function index(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $month = (int) $request->get('month', (int) date('n'));
        $year = (int) $request->get('year', (int) date('Y'));

        $entries = DB::table('kas_entries')
            ->where('kd_toko', $kdToko)
            ->whereYear('tgl', $year)
            ->whereMonth('tgl', $month)
            ->orderByDesc('tgl')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $saldo = DB::table('kas_entries')
            ->where('kd_toko', $kdToko)
            ->selectRaw("SUM(CASE WHEN direction = 'in' THEN amount ELSE -amount END) as saldo")
            ->value('saldo') ?? 0;

        return view('admin.retail.kas', compact('entries', 'saldo', 'month', 'year'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tgl' => 'required|date',
            'direction' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
            'post_to_gl' => 'nullable|boolean',
        ]);

        $postToGl = $request->boolean('post_to_gl');

        $id = DB::table('kas_entries')->insertGetId([
            'kd_toko' => BranchContext::kdToko(),
            'tgl' => $data['tgl'],
            'direction' => $data['direction'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'post_to_gl' => $postToGl,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($postToGl) {
            $entry = $this->posting->postKasEntry($data['direction'], (float) $data['amount'], $data['tgl'], (string) $id, true);
            if ($entry) {
                DB::table('kas_entries')->where('id', $id)->update(['journal_entry_id' => $entry->id]);
            }
        }

        return back()->with('success', 'Transaksi kas tercatat.');
    }

    public function print(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $month = (int) $request->get('month', (int) date('n'));
        $year = (int) $request->get('year', (int) date('Y'));

        $entries = DB::table('kas_entries')
            ->where('kd_toko', $kdToko)
            ->whereYear('tgl', $year)
            ->whereMonth('tgl', $month)
            ->orderBy('tgl')
            ->orderBy('id')
            ->get();

        $expenses = collect();
        if (Schema::hasTable('operating_expenses')) {
            $expenses = DB::table('operating_expenses')
                ->where('kd_toko', $kdToko)
                ->whereYear('tgl_biaya', $year)
                ->whereMonth('tgl_biaya', $month)
                ->orderBy('tgl_biaya')
                ->get();
        }

        $totIn = $entries->where('direction', 'in')->sum('amount');
        $totOut = $entries->where('direction', 'out')->sum('amount');
        $totBiaya = $expenses->sum('nominal');

        return view('admin.retail.kas-print', compact('entries', 'expenses', 'month', 'year', 'totIn', 'totOut', 'totBiaya', 'kdToko'));
    }
}
