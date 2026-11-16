<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\AppliesMasterSearch;
use App\Models\Paket;
use App\Models\PaketLine;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PaketController extends Controller
{
    use AppliesMasterSearch;

    public function index(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $query = Paket::query()->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko));
        $query = $this->applyMasterSearch($query, $request, ['nm_paket', 'kd_toko']);

        $editItem = $request->filled('edit') ? Paket::find($request->edit) : null;
        if ($editItem) {
            $this->authorizePaket($editItem);
        }

        return view('admin.master.paket', [
            'items' => $query->orderBy('nm_paket')->paginate(12)->withQueryString(),
            'editItem' => $editItem,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nm_paket' => 'required|string|max:255',
        ]);

        $paket = Paket::create([
            'nm_paket' => strtoupper($data['nm_paket']),
            'kd_toko' => BranchContext::kdToko(),
        ]);

        return redirect()->route('master.paket.show', $paket)->with('success', 'Paket dibuat. Tambahkan barang ke daftar.');
    }

    public function show(Paket $paket)
    {
        $this->authorizePaket($paket);
        $lines = PaketLine::where('kd_paket', $paket->no_urut)->orderBy('no_urut')->get();
        $this->attachProductNames($lines, $paket->kd_toko);

        return view('admin.master.paket-show', compact('paket', 'lines'));
    }

    public function update(Request $request, Paket $paket)
    {
        $this->authorizePaket($paket);
        $data = $request->validate(['nm_paket' => 'required|string|max:255']);
        $paket->update(['nm_paket' => strtoupper($data['nm_paket'])]);

        return redirect()->route('master.paket.index', $request->only('q'))->with('success', 'Paket diperbarui.');
    }

    public function destroy(Paket $paket)
    {
        $this->authorizePaket($paket);
        PaketLine::where('kd_paket', $paket->no_urut)->delete();
        $paket->delete();

        return back()->with('success', 'Paket dihapus.');
    }

    public function storeLine(Request $request, Paket $paket)
    {
        $this->authorizePaket($paket);
        $data = $request->validate([
            'kd_brg' => 'required|string|max:50',
            'qty_brg' => 'required|numeric|min:0.01',
            'kd_sat' => 'nullable|integer|min:1',
        ]);

        $sku = $this->resolveSku($data['kd_brg'], $paket->kd_toko) ?: strtoupper(trim($data['kd_brg']));

        PaketLine::create([
            'kd_paket' => $paket->no_urut,
            'kd_brg' => $sku,
            'qty_brg' => $data['qty_brg'],
            'kd_sat' => $data['kd_sat'] ?? 1,
            'kd_toko' => $paket->kd_toko,
        ]);

        return back()->with('success', 'Barang ditambahkan ke paket.');
    }

    public function destroyLine(PaketLine $line)
    {
        $paket = Paket::find($line->kd_paket);
        if ($paket) {
            $this->authorizePaket($paket);
        }
        $line->delete();

        return back()->with('success', 'Baris paket dihapus.');
    }

    protected function attachProductNames($lines, ?string $kdToko): void
    {
        if ($lines->isEmpty() || ! Schema::hasTable('mas_brg')) {
            return;
        }
        $q = DB::table('mas_brg')->whereIn('kd_brg', $lines->pluck('kd_brg')->all());
        if ($kdToko) {
            $q->where('kd_toko', $kdToko);
        }
        $names = $q->get(['kd_brg', 'nm_brg'])->keyBy('kd_brg');
        $lines->each(function ($line) use ($names) {
            $line->nm_brg = $names->get($line->kd_brg)->nm_brg ?? '';
        });
    }

    protected function resolveSku(string $keyword, ?string $kdToko): ?string
    {
        if (! Schema::hasTable('mas_brg')) {
            return null;
        }
        $keyword = trim($keyword);
        $q = DB::table('mas_brg')->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko));
        $row = (clone $q)->where('kd_brg', $keyword)->first();
        if (! $row && Schema::hasColumn('mas_brg', 'kd_bar')) {
            $row = (clone $q)->where('kd_bar', $keyword)->first();
        }

        return $row->kd_brg ?? null;
    }

    protected function authorizePaket(Paket $paket): void
    {
        $kdToko = BranchContext::kdToko();
        if ($kdToko && $paket->kd_toko !== $kdToko) {
            abort(403);
        }
    }
}
