<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\AppliesMasterSearch;
use App\Models\Kemas;
use Illuminate\Http\Request;

class KemasController extends Controller
{
    use AppliesMasterSearch;

    public function index(Request $request)
    {
        $query = $this->applyMasterSearch(Kemas::query(), $request, ['nm_sat1', 'nm_sat2']);

        $editItem = $request->filled('edit') ? Kemas::find($request->edit) : null;

        return view('admin.master.simple-index', $this->viewData($query->orderBy('no_urut')->paginate(20), $editItem));
    }

    public function store(Request $request)
    {
        $request->validate(['nm_sat1' => 'required|string|max:50']);
        Kemas::create([
            'nm_sat1' => strtoupper($request->nm_sat1),
            'nm_sat2' => $request->nm_sat2 ? strtoupper($request->nm_sat2) : null,
        ]);

        return back()->with('success', 'Kemasan ditambahkan.');
    }

    public function update(Request $request, Kemas $kema)
    {
        $request->validate(['nm_sat1' => 'required|string|max:50']);
        $kema->update([
            'nm_sat1' => strtoupper($request->nm_sat1),
            'nm_sat2' => $request->nm_sat2 ? strtoupper($request->nm_sat2) : null,
        ]);

        return redirect()->route('master.kemas.index', $request->only('q'))->with('success', 'Kemasan diperbarui.');
    }

    public function destroy(Kemas $kema)
    {
        $kema->delete();

        return back()->with('success', 'Kemasan dihapus.');
    }

    protected function viewData($items, $editItem = null): array
    {
        return [
            'title' => 'Master Kemasan',
            'routePrefix' => 'master.kemas',
            'resourceParam' => 'kema',
            'rowKey' => 'no_urut',
            'columns' => ['no_urut' => 'No', 'nm_sat1' => 'Satuan 1', 'nm_sat2' => 'Satuan 2'],
            'items' => $items,
            'editItem' => $editItem,
        ];
    }
}
