<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Services\Finance\PostingService;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class OperatingExpenseController extends Controller
{
    public function __construct(protected PostingService $posting)
    {
    }

    public function index(Request $request)
    {
        abort_unless(Schema::hasTable('operating_expenses'), 501);

        $kdToko = BranchContext::kdToko();
        $q = trim((string) $request->get('q', ''));

        $items = DB::table('operating_expenses')
            ->where('kd_toko', $kdToko)
            ->when($q !== '', fn ($query) => $query->where('ket_biaya', 'like', '%'.$q.'%'))
            ->orderByDesc('tgl_biaya')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $jenis = [
            11 => 'LAIN-LAIN',
            1 => 'LISTRIK',
            2 => 'AIR',
            3 => 'GAJI',
            4 => 'TRANSPORT',
        ];

        return view('admin.retail.expenses', compact('items', 'jenis', 'q'));
    }

    public function store(Request $request)
    {
        abort_unless(Schema::hasTable('operating_expenses'), 501);

        $data = $request->validate([
            'tgl_biaya' => 'required|date',
            'nominal' => 'required|numeric|min:0.01',
            'ket_biaya' => 'nullable|string|max:255',
            'id_jenis' => 'nullable|integer|min:1',
            'post_to_gl' => 'nullable|boolean',
        ]);

        $kdToko = BranchContext::kdToko();
        $jenisMap = [11 => 'LAIN-LAIN', 1 => 'LISTRIK', 2 => 'AIR', 3 => 'GAJI', 4 => 'TRANSPORT'];
        $idJenis = (int) ($data['id_jenis'] ?? 11);

        $expenseId = DB::table('operating_expenses')->insertGetId([
            'kd_toko' => $kdToko,
            'tgl_biaya' => $data['tgl_biaya'],
            'nominal' => $data['nominal'],
            'ket_biaya' => $data['ket_biaya'] ?? null,
            'id_jenis' => $idJenis,
            'nm_jenis' => $jenisMap[$idJenis] ?? 'LAIN-LAIN',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if ($request->boolean('post_to_gl')) {
            $entry = $this->posting->postOperatingExpense((float) $data['nominal'], $data['tgl_biaya'], (string) $expenseId);
            if ($entry) {
                DB::table('operating_expenses')->where('id', $expenseId)->update(['journal_entry_id' => $entry->id]);
            }
        }

        return back()->with('success', 'Biaya operasional tercatat.');
    }

    public function destroy(int $expense)
    {
        $kdToko = BranchContext::kdToko();
        DB::table('operating_expenses')
            ->where('id', $expense)
            ->where('kd_toko', $kdToko)
            ->whereNull('journal_entry_id')
            ->delete();

        return back()->with('success', 'Biaya dihapus (hanya jika belum diposting GL).');
    }
}
