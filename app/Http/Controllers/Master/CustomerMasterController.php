<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\AppliesMasterSearch;
use App\Models\Customer;
use App\Support\BranchContext;
use App\Support\LegacyMasterSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CustomerMasterController extends Controller
{
    use AppliesMasterSearch;

    public function index(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $query = $this->customerQuery($request, $kdToko);
        $this->applySort($query, $request->get('sort', 'abjad'));

        $editItem = null;
        if ($request->filled('edit')) {
            $editItem = Customer::query()->where('no_urut', $request->edit)->first();
            if ($editItem) {
                $this->authorizeCustomer($editItem);
            }
        }

        return view('admin.master.customers', [
            'items' => $query->paginate(10)->withQueryString(),
            'editItem' => $editItem,
            'nextKd' => $this->nextKdPelanggan(),
            'sort' => $request->get('sort', 'abjad'),
        ]);
    }

    public function store(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko, 403);
        $data = $this->validatedCustomer($request);

        $customer = null;
        DB::transaction(function () use ($data, $kdToko, &$customer) {
            $kd = strtoupper(trim((string) ($data['kd_pel'] ?? '')));
            if ($kd === '') {
                $kd = $this->nextKdPelanggan(true);
            }

            $existing = Customer::query()
                ->where('kd_pel', $kd)
                ->when(
                    Schema::hasColumn('pelanggan', 'kd_toko'),
                    fn ($q) => $q->where(function ($q) use ($kdToko) {
                        $q->where('kd_toko', $kdToko)->orWhereNull('kd_toko');
                    })
                )
                ->lockForUpdate()
                ->orderByDesc('no_urut')
                ->first();

            $phone = $data['telp_pel'] ?? '';
            $payload = [
                'nm_pel' => strtoupper($data['nm_pel']),
                'al_pel' => strtoupper($data['al_pel'] ?? ''),
            ];

            if ($existing) {
                $existing->fill($payload);
                $existing->telp_pel = $phone;
                $existing->save();
                $customer = $existing;

                return;
            }

            $create = $payload + ['kd_pel' => $kd];
            if (Schema::hasColumn('pelanggan', 'kd_toko')) {
                $create['kd_toko'] = $kdToko;
            }
            $customer = new Customer($create);
            $customer->telp_pel = $phone;
            $customer->save();
        });

        return redirect()->route('master.customers.index')->with('success', 'Pelanggan disimpan ('.$customer->kd_pel.').');
    }

    public function update(Request $request, Customer $customer)
    {
        $this->authorizeCustomer($customer);
        $data = $this->validatedCustomer($request);

        $customer->fill([
            'nm_pel' => strtoupper($data['nm_pel']),
            'al_pel' => strtoupper($data['al_pel'] ?? ''),
        ]);
        $customer->telp_pel = $data['telp_pel'] ?? '';
        $customer->save();

        return redirect()->route('master.customers.index', $request->only('q', 'sort'))->with('success', 'Pelanggan diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        $this->authorizeCustomer($customer);
        $customer->delete();

        return back()->with('success', 'Pelanggan dihapus.');
    }

    protected function customerQuery(Request $request, ?string $kdToko)
    {
        $query = Customer::query();
        LegacyMasterSchema::scopeByTokoIfSupported($query, 'pelanggan', $kdToko);
        $query->where(function ($q) {
            $q->whereNull('nm_pel')->orWhere('nm_pel', '!=', '-NONE-');
        });

        $phoneCol = Customer::phoneColumn();

        return $this->applyMasterSearch($query, $request, ['kd_pel', 'nm_pel', 'al_pel', $phoneCol]);
    }

    protected function applySort($query, string $sort): void
    {
        if ($sort === 'id_asc') {
            $query->orderBy('no_urut');
        } else {
            $query->orderBy('nm_pel');
        }
    }

    protected function nextKdPelanggan(bool $lock = false): string
    {
        $query = Customer::query()->where('kd_pel', 'like', 'IDPEL-%');
        if ($lock) {
            $query->lockForUpdate();
        }

        $last = 0;
        foreach ($query->pluck('kd_pel') as $code) {
            $n = $this->pelangganSeqFromCode((string) $code);
            if ($n !== null) {
                $last = max($last, $n);
            }
        }

        $next = max($last, 0) + 1;
        while (Customer::query()->where('kd_pel', 'IDPEL-'.$next)->exists()) {
            $next++;
        }

        return 'IDPEL-'.$next;
    }

    protected function pelangganSeqFromCode(string $code): ?int
    {
        $code = strtoupper(trim($code));
        if (! preg_match('/^IDPEL-(\d{1,6})$/', $code, $match)) {
            return null;
        }

        $n = (int) $match[1];

        return $n > 0 ? $n : null;
    }

    protected function validatedCustomer(Request $request): array
    {
        return $request->validate([
            'kd_pel' => 'nullable|string|max:50',
            'nm_pel' => 'required|string|max:255',
            'al_pel' => 'nullable|string',
            'telp_pel' => 'nullable|string|max:50',
        ]);
    }

    protected function authorizeCustomer(Customer $customer): void
    {
        $kdToko = BranchContext::kdToko();
        if ($kdToko && $customer->kd_toko && $customer->kd_toko !== $kdToko) {
            abort(403);
        }
    }
}
