<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\AppliesMasterSearch;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SupplierMasterController extends Controller
{
    use AppliesMasterSearch;

    public function index(Request $request)
    {
        $query = $this->supplierQuery($request);
        $this->applySort($query, $request->get('sort', 'abjad'));

        $editItem = null;
        if ($request->filled('edit')) {
            $editItem = Supplier::query()->where('no_urut', $request->edit)->first()
                ?: Supplier::find($request->edit);
        }

        return view('admin.master.suppliers', [
            'items' => $query->paginate(10)->withQueryString(),
            'editItem' => $editItem,
            'nextKd' => $this->nextKdSupplier(),
            'sort' => $request->get('sort', 'abjad'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedSupplier($request);

        $supplier = null;
        DB::transaction(function () use ($data, &$supplier) {
            $kd = strtoupper(trim((string) ($data['kd_sup'] ?? '')));
            if ($kd === '') {
                $kd = $this->nextKdSupplier(true);
            }

            $existing = Supplier::query()
                ->where('kd_sup', $kd)
                ->lockForUpdate()
                ->first();

            $payload = $this->supplierRow($data, false);

            if ($existing) {
                $existing->update($payload);
                $supplier = $existing;

                return;
            }

            $supplier = Supplier::create($payload + ['kd_sup' => $kd]);
        });

        return redirect()->route('master.suppliers.index')->with('success', 'Supplier disimpan ('.$supplier->kd_sup.').');
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $this->validatedSupplier($request);
        $supplier->update($this->supplierRow($data, false));

        return redirect()->route('master.suppliers.index', $request->only('q', 'sort'))->with('success', 'Supplier diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return back()->with('success', 'Supplier dihapus.');
    }

    protected function supplierQuery(Request $request)
    {
        $query = Supplier::query()->where(function ($q) {
            $q->whereNull('nm_sup')->orWhere('nm_sup', '!=', 'NONE');
        });

        $search = ['kd_sup', 'nm_sup', 'al_sup'];
        if (Schema::hasColumn('supplier', 'nm_sales')) {
            $search[] = 'nm_sales';
        }
        if (Schema::hasColumn('supplier', 'no_telp')) {
            $search[] = 'no_telp';
        }
        if (Schema::hasColumn('supplier', 'telp_sup')) {
            $search[] = 'telp_sup';
        }

        return $this->applyMasterSearch($query, $request, $search);
    }

    protected function applySort($query, string $sort): void
    {
        if ($sort === 'id_asc') {
            $query->orderBy('no_urut');
        } else {
            $query->orderBy('nm_sup');
        }
    }

    protected function nextKdSupplier(bool $lock = false): string
    {
        $query = Supplier::query()->where('kd_sup', 'like', 'IDPEM-%');
        if ($lock) {
            $query->lockForUpdate();
        }

        $last = 0;
        foreach ($query->pluck('kd_sup') as $code) {
            $n = $this->supplierSeqFromCode((string) $code);
            if ($n !== null) {
                $last = max($last, $n);
            }
        }

        $next = $last + 1;
        while (Supplier::query()->where('kd_sup', 'IDPEM-'.$next)->exists()) {
            $next++;
        }

        return 'IDPEM-'.$next;
    }

    protected function supplierSeqFromCode(string $code): ?int
    {
        $code = strtoupper(trim($code));
        if (! preg_match('/^IDPEM-(\d{1,6})$/', $code, $match)) {
            return null;
        }

        $n = (int) $match[1];

        return $n > 0 ? $n : null;
    }

    protected function validatedSupplier(Request $request): array
    {
        return $request->validate([
            'kd_sup' => 'nullable|string|max:50',
            'nm_sup' => 'required|string|max:255',
            'al_sup' => 'nullable|string',
            'no_telp' => 'nullable|string|max:50',
            'nm_sales' => 'nullable|string|max:255',
        ]);
    }

    private function supplierRow(array $data, bool $creating): array
    {
        $phone = strtoupper($data['no_telp'] ?? $data['telp_sup'] ?? '');
        $row = [
            'nm_sup' => strtoupper($data['nm_sup']),
            'al_sup' => strtoupper($data['al_sup'] ?? ''),
        ];
        if ($creating && ! empty($data['kd_sup'])) {
            $row['kd_sup'] = strtoupper($data['kd_sup']);
        }
        if (Schema::hasColumn('supplier', 'no_telp')) {
            $row['no_telp'] = $phone;
        }
        if (Schema::hasColumn('supplier', 'telp_sup')) {
            $row['telp_sup'] = $phone;
        }
        if (Schema::hasColumn('supplier', 'nm_sales')) {
            $row['nm_sales'] = strtoupper($data['nm_sales'] ?? '');
        }

        return $row;
    }
}
