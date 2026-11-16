<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\AppliesMasterSearch;
use App\Models\BrandReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class BrandReportController extends Controller
{
    use AppliesMasterSearch;

    public function index(Request $request)
    {
        $this->ensureTable();

        $query = BrandReport::query();
        $query = $this->applyMasterSearch($query, $request, ['nm_brand']);
        $sort = $request->get('sort', 'abjad');
        if ($sort === 'id_asc') {
            $query->orderBy('no_urut');
        } else {
            $query->orderBy('nm_brand');
        }

        $editItem = $request->filled('edit') ? BrandReport::find($request->edit) : null;

        return view('admin.master.brand-report', [
            'items' => $query->paginate(12)->withQueryString(),
            'editItem' => $editItem,
            'sort' => $sort,
        ]);
    }

    public function store(Request $request)
    {
        $this->ensureTable();
        $name = $this->validatedName($request);

        $exists = BrandReport::query()
            ->whereRaw('UPPER(nm_brand) = ?', [strtoupper($name)])
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Nama brand sudah ada.');
        }

        BrandReport::create(['nm_brand' => $name]);

        return redirect()->route('master.brand-report.index')->with('success', 'Brand disimpan ('.$name.').');
    }

    public function update(Request $request, BrandReport $brandReport)
    {
        $name = $this->validatedName($request, $brandReport->no_urut);

        $exists = BrandReport::query()
            ->whereRaw('UPPER(nm_brand) = ?', [strtoupper($name)])
            ->where('no_urut', '!=', $brandReport->no_urut)
            ->exists();
        if ($exists) {
            return back()->withInput()->with('error', 'Nama brand sudah ada.');
        }

        $brandReport->update(['nm_brand' => $name]);

        return redirect()->route('master.brand-report.index', $request->only('q', 'sort'))->with('success', 'Brand diperbarui.');
    }

    public function destroy(BrandReport $brandReport)
    {
        $brandReport->delete();

        return back()->with('success', 'Brand dihapus.');
    }

    protected function validatedName(Request $request, ?int $ignore = null): string
    {
        $data = $request->validate([
            'nm_brand' => [
                'required',
                'string',
                'max:100',
                Rule::unique('brand_report', 'nm_brand')->ignore($ignore, 'no_urut'),
            ],
        ]);

        return strtoupper(trim($data['nm_brand']));
    }

    protected function ensureTable(): void
    {
        if (Schema::hasTable('brand_report')) {
            return;
        }

        Schema::create('brand_report', function ($table) {
            $table->increments('no_urut');
            $table->string('nm_brand', 100)->unique();
        });
    }
}
