<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\AppliesMasterSearch;
use App\Models\Bagian;
use Illuminate\Http\Request;

class BagianController extends Controller
{
    use AppliesMasterSearch;

    public function index(Request $request)
    {
        $query = $this->applyMasterSearch(Bagian::query(), $request, ['nm_bag']);
        $editItem = $request->filled('edit') ? Bagian::find($request->edit) : null;

        return view('admin.master.simple-index', [
            'title' => 'Master Bagian',
            'routePrefix' => 'master.bagian',
            'resourceParam' => 'bagian',
            'rowKey' => 'no_urut',
            'columns' => ['no_urut' => 'No', 'nm_bag' => 'Nama Bagian'],
            'items' => $query->orderBy('no_urut')->paginate(20),
            'editItem' => $editItem,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate(['nm_bag' => 'required|string|max:100']);
        Bagian::create(['nm_bag' => strtoupper($request->nm_bag)]);

        return back()->with('success', 'Bagian ditambahkan.');
    }

    public function update(Request $request, Bagian $bagian)
    {
        $request->validate(['nm_bag' => 'required|string|max:100']);
        $bagian->update(['nm_bag' => strtoupper($request->nm_bag)]);

        return redirect()->route('master.bagian.index', $request->only('q'))->with('success', 'Bagian diperbarui.');
    }

    public function destroy(Bagian $bagian)
    {
        $bagian->delete();

        return back()->with('success', 'Bagian dihapus.');
    }
}
