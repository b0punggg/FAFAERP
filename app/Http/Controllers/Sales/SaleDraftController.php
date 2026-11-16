<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SaleDraft;
use App\Services\Retail\DocumentNumberService;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleDraftController extends Controller
{
    public function index()
    {
        $kdToko = BranchContext::kdToko();
        $drafts = SaleDraft::query()
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->orderByDesc('updated_at')
            ->paginate(20);

        return view('admin.sales.drafts', compact('drafts'));
    }

    public function __construct(protected DocumentNumberService $numbers)
    {
    }

    public function store(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko, 403);

        if ($request->isJson()) {
            $request->merge($request->json()->all());
        }

        $data = $request->validate([
            'no_fak' => 'required|string|max:100',
            'tgl_jual' => 'required|date',
            'payload' => 'required|array',
        ]);

        $next = DB::transaction(function () use ($kdToko, $data) {
            SaleDraft::updateOrCreate(
                ['kd_toko' => $kdToko, 'no_fak' => $data['no_fak']],
                [
                    'user_id' => auth()->id(),
                    'tgl_jual' => $data['tgl_jual'],
                    'payload' => $data['payload'],
                ]
            );
            $this->numbers->consumeSale($kdToko, $data['no_fak'], (int) auth()->id());

            return $this->numbers->suggestSale($kdToko, (int) auth()->id());
        });

        return response()->json(['ok' => true, 'message' => 'Draft disimpan.', 'next_no' => $next]);
    }

    public function show(SaleDraft $draft)
    {
        $this->authorizeDraft($draft);

        return response()->json($draft);
    }

    public function destroy(SaleDraft $draft)
    {
        $this->authorizeDraft($draft);
        $draft->delete();

        return back()->with('success', 'Draft dihapus.');
    }

    protected function authorizeDraft(SaleDraft $draft): void
    {
        $kdToko = BranchContext::kdToko();
        if ($kdToko && $draft->kd_toko !== $kdToko) {
            abort(403);
        }
    }
}
