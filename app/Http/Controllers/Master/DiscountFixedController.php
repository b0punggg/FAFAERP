<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\AppliesMasterSearch;
use App\Models\DiscTetap;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DiscountFixedController extends Controller
{
    use AppliesMasterSearch;

    public function index(Request $request)
    {
        abort_unless(Schema::hasTable('disctetap'), 501, 'Tabel disctetap belum tersedia. Jalankan migrate.');

        $kdToko = BranchContext::kdToko();
        $query = DiscTetap::query()->when($kdToko, fn ($q) => $q->where(function ($q) use ($kdToko) {
            $q->where('kd_toko', $kdToko)->orWhereNull('kd_toko');
        }));
        $query = $this->applyMasterSearch($query, $request, ['kd_brg']);

        $edit = $request->filled('edit') ? DiscTetap::find($request->edit) : null;

        return view('admin.master.discount-fixed', [
            'items' => $query->orderByDesc('no_urut')->paginate(25),
            'edit' => $edit,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kd_brg' => 'required|string|max:50',
            'kd_sat' => 'required|integer|min:1',
            'hrg_jual' => 'required|numeric|min:0',
            'lim_jual' => 'nullable|numeric|min:0',
        ]);

        DiscTetap::updateOrCreate(
            [
                'kd_brg' => $data['kd_brg'],
                'kd_sat' => $data['kd_sat'],
                'kd_toko' => BranchContext::kdToko(),
            ],
            [
                'hrg_jual' => $data['hrg_jual'],
                'lim_jual' => $data['lim_jual'] ?? 0,
            ]
        );

        return back()->with('success', 'Diskon tetap disimpan.');
    }

    public function update(Request $request, DiscTetap $discountFixed)
    {
        $data = $request->validate([
            'kd_brg' => 'required|string|max:50',
            'kd_sat' => 'required|integer|min:1',
            'hrg_jual' => 'required|numeric|min:0',
            'lim_jual' => 'nullable|numeric|min:0',
        ]);

        $discountFixed->update([
            'kd_brg' => $data['kd_brg'],
            'kd_sat' => $data['kd_sat'],
            'hrg_jual' => $data['hrg_jual'],
            'lim_jual' => $data['lim_jual'] ?? 0,
        ]);

        return redirect()->route('master.discount-fixed.index')->with('success', 'Diskon diperbarui.');
    }

    public function destroy(DiscTetap $discountFixed)
    {
        $discountFixed->delete();

        return back()->with('success', 'Diskon dihapus.');
    }
}
