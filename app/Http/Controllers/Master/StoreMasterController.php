<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\AppliesMasterSearch;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StoreMasterController extends Controller
{
    use AppliesMasterSearch;

    public function index(Request $request)
    {
        $retailId = Company::where('type', 'retail')->value('id');
        $query = Branch::where('company_id', $retailId);
        $query = $this->applyMasterSearch($query, $request, ['kd_toko', 'name', 'address']);

        $editItem = $request->filled('edit') ? Branch::find($request->edit) : null;

        return view('admin.master.simple-index', [
            'title' => 'Master Gerai / Toko',
            'description' => 'Data gerai = cabang PT retail (branches)',
            'routePrefix' => 'master.stores',
            'resourceParam' => 'branch',
            'rowKey' => 'id',
            'columns' => ['kd_toko' => 'Kode', 'name' => 'Nama', 'address' => 'Alamat', 'is_active' => 'Status'],
            'items' => $query->orderBy('sort_order')->paginate(20),
            'hideCreate' => true,
            'hideDelete' => true,
            'editItem' => $editItem,
        ]);
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'is_active' => 'required|in:0,1',
        ]);
        $branch->update([
            'name' => $data['name'],
            'address' => $data['address'] ?? null,
            'is_active' => (bool) $data['is_active'],
        ]);
        $this->mirrorToko($branch->fresh());

        return redirect()->route('master.stores.index', $request->only('q'))->with('success', 'Gerai diperbarui.');
    }

    private function mirrorToko(Branch $branch): void
    {
        if (! $branch->kd_toko || $branch->kd_toko === config('erp.central_kd_toko')) {
            return;
        }

        $row = ['nm_toko' => mb_substr($branch->name, 0, 30)];
        if (Schema::hasColumn('toko', 'al_toko')) {
            $row['al_toko'] = mb_substr((string) $branch->address, 0, 60);
        }

        DB::table('toko')->where('kd_toko', $branch->kd_toko)->update($row);

        $warehouse = $branch->warehouses()->first();
        if ($warehouse) {
            $warehouse->name = 'Gudang '.$branch->name;
            $warehouse->save();
        }
    }
}
