<?php

namespace App\Http\Controllers\Executive;

use App\Http\Controllers\Controller;
use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\BudgetNote;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $status = (string) $request->get('status', '');
        $companyId = $request->get('company_id');

        $budgets = Budget::with('company')
            ->withSum('lines as total_rencana', 'planned_amount')
            ->when($q !== '', fn ($query) => $query->where('title', 'like', "%{$q}%"))
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $companies = Company::orderBy('legal_name')->get();

        return view('admin.executive.budgets', compact('budgets', 'companies', 'q', 'status', 'companyId'));
    }

    public function create()
    {
        return view('admin.executive.budget-form', [
            'budget' => null,
            'companies' => Company::orderBy('legal_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $budget = DB::transaction(function () use ($request) {
            $budget = new Budget();
            $this->fillBudget($budget, $request, true);

            return $budget;
        });

        return redirect()->route('executive.budgets.show', $budget)->with('success', 'Program disimpan sebagai draf.');
    }

    public function show(Budget $budget)
    {
        $budget->load(['company', 'lines', 'notes']);

        return view('admin.executive.budget-show', compact('budget'));
    }

    public function edit(Budget $budget)
    {
        abort_unless($budget->status === 'draft', 403);
        $budget->load('lines');

        return view('admin.executive.budget-form', [
            'budget' => $budget,
            'companies' => Company::orderBy('legal_name')->get(),
        ]);
    }

    public function update(Request $request, Budget $budget)
    {
        abort_unless($budget->status === 'draft', 403);
        DB::transaction(function () use ($request, $budget) {
            $this->fillBudget($budget, $request, false);
        });

        return redirect()->route('executive.budgets.show', $budget)->with('success', 'Draf program diperbarui.');
    }

    public function print(Budget $budget)
    {
        $budget->load(['company', 'lines']);

        return view('admin.executive.budget-print', compact('budget'));
    }

    public function submit(Budget $budget)
    {
        abort_unless($budget->status === 'draft', 403);
        $budget->update(['status' => 'submitted']);

        return back()->with('success', 'Program diajukan.');
    }

    public function validateBudget(Budget $budget)
    {
        abort_unless($budget->status === 'submitted', 403);
        $plan = $budget->plan_5w2h ?? [];
        $plan['mengetahui'] = auth()->user()->nm_user ?? 'Finance';
        $budget->update(['status' => 'validated', 'plan_5w2h' => $plan]);

        return back()->with('success', 'Program diperiksa.');
    }

    public function approve(Budget $budget)
    {
        abort_unless($budget->status === 'validated', 403);
        $plan = $budget->plan_5w2h ?? [];
        $plan['menyetujui'] = auth()->user()->nm_user ?? 'Penyetuju';
        $budget->update(['status' => 'approved', 'plan_5w2h' => $plan]);

        return back()->with('success', 'Program disetujui.');
    }

    public function storeNote(Request $request, Budget $budget)
    {
        $data = $request->validate([
            'jenis' => 'required|in:crm,zoom,visit',
            'tgl' => 'required|date',
            'pelanggan' => 'nullable|string|max:150',
            'hasil' => 'required|string|max:2000',
        ]);
        $budget->notes()->create($data);

        return back()->with('success', 'Hasil kegiatan dicatat.');
    }

    public function actual(Request $request, Budget $budget)
    {
        abort_unless($budget->status === 'approved', 403);
        $data = $request->validate([
            'actual' => 'array',
            'actual.*' => 'nullable|numeric|min:0',
        ]);
        foreach ($data['actual'] ?? [] as $lineId => $amount) {
            BudgetLine::where('budget_id', $budget->id)->where('id', $lineId)->update([
                'actual_amount' => $amount ?? 0,
            ]);
        }

        return back()->with('success', 'Realisasi disimpan. Angka ini belum masuk laba rugi.');
    }

    protected function fillBudget(Budget $budget, Request $request, bool $creating): void
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'title' => 'required|string|max:255',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'tempat' => 'nullable|string|max:100',
            'sasaran' => 'nullable|string|max:1000',
            'sasaran_jumlah' => 'nullable|integer|min:0',
            'why' => 'array',
            'why.*' => 'nullable|string|max:500',
            'pelaksana' => 'array',
            'pelaksana.*.peran' => 'nullable|string|max:80',
            'pelaksana.*.nama' => 'nullable|string|max:120',
            'how' => 'array',
            'how.*.nama' => 'nullable|string|max:100',
            'how.*.langkah' => 'nullable|string|max:4000',
            'lines' => 'array',
            'lines.*.kegiatan' => 'nullable|string|max:100',
            'lines.*.nama' => 'nullable|string|max:255',
            'lines.*.qty' => 'nullable|numeric|min:0',
            'lines.*.harga' => 'nullable|numeric|min:0',
        ]);

        $plan = $budget->plan_5w2h ?? [];
        $plan['why'] = array_values(array_filter(array_map('trim', $data['why'] ?? [])));
        $plan['sasaran'] = trim((string) ($data['sasaran'] ?? ''));
        $plan['sasaran_jumlah'] = $data['sasaran_jumlah'] ?? null;
        $plan['pelaksana'] = array_values(array_filter($data['pelaksana'] ?? [], function ($row) {
            return trim((string) ($row['peran'] ?? '')) !== '' || trim((string) ($row['nama'] ?? '')) !== '';
        }));
        $plan['how'] = [];
        foreach ($data['how'] ?? [] as $block) {
            $nama = trim((string) ($block['nama'] ?? ''));
            $steps = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) ($block['langkah'] ?? '')))));
            if ($nama === '' && $steps === []) {
                continue;
            }
            $plan['how'][] = ['nama' => $nama, 'langkah' => $steps];
        }
        if ($creating) {
            $plan['dibuat'] = auth()->user()->nm_user ?? '';
        }

        $start = $data['period_start'];
        $budget->fill([
            'company_id' => $data['company_id'],
            'title' => $data['title'],
            'year' => (int) substr($start, 0, 4),
            'month' => (int) substr($start, 5, 2),
            'period_start' => $start,
            'period_end' => $data['period_end'],
            'tempat' => $data['tempat'] ?? null,
            'plan_5w2h' => $plan,
            'status' => $budget->status ?: 'draft',
        ]);
        $budget->save();

        $budget->lines()->delete();
        foreach ($data['lines'] ?? [] as $line) {
            $nama = trim((string) ($line['nama'] ?? ''));
            if ($nama === '') {
                continue;
            }
            $qty = (float) ($line['qty'] ?? 0);
            $harga = (float) ($line['harga'] ?? 0);
            BudgetLine::create([
                'budget_id' => $budget->id,
                'cost_center' => mb_substr($nama, 0, 64),
                'kegiatan' => trim((string) ($line['kegiatan'] ?? '')) ?: null,
                'uraian' => $nama,
                'qty' => $qty,
                'unit_price' => $harga,
                'planned_amount' => round($qty * $harga, 2),
                'actual_amount' => 0,
            ]);
        }
    }
}
