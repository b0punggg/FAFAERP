<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Master\Concerns\AppliesMasterSearch;
use App\Models\Bagian;
use App\Models\DiscPromo;
use App\Models\DiscPromoDetail;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DiscountPromoController extends Controller
{
    use AppliesMasterSearch;

    public function index(Request $request)
    {
        abort_unless(Schema::hasTable('disc_promo'), 501);

        $kdToko = BranchContext::kdToko();
        $query = DiscPromo::query()->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko));
        $query = $this->applyMasterSearch($query, $request, ['no_promo', 'nama_promo']);

        $editPromo = $request->filled('edit') ? DiscPromo::find($request->edit) : null;
        $details = $editPromo ? $this->promoLines($editPromo) : collect();
        $items = $query->orderByDesc('tgl_akhir')->paginate(15);

        return view('admin.master.discount-promo', [
            'items' => $items,
            'editPromo' => $editPromo,
            'details' => $details,
            'lineMap' => $this->linesForPromos($items->getCollection(), $kdToko),
            'bagian' => Schema::hasTable('bag_brg') ? Bagian::orderBy('nm_bag')->get() : collect(),
        ]);
    }

    public function searchProducts(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko && Schema::hasTable('mas_brg'), 403);

        $byNama = trim((string) $request->input('by_nama', ''));
        $byBagian = trim((string) $request->input('id_bag', ''));
        if ($byNama === '' && $byBagian === '') {
            return response()->json([
                'items' => [],
                'message' => 'Masukkan nama brand/kategori atau pilih bagian terlebih dahulu.',
            ], 422);
        }

        $query = DB::table('mas_brg as m')->where('m.kd_toko', $kdToko);

        if ($byBagian !== '' && Schema::hasTable('beli_brg') && Schema::hasColumn('beli_brg', 'id_bag')) {
            $query->join('beli_brg as b', function ($join) use ($kdToko) {
                $join->on('b.kd_brg', '=', 'm.kd_brg')->where('b.kd_toko', $kdToko);
            })->where('b.id_bag', $byBagian);
        }

        if ($byNama !== '') {
            $query->where(function ($q) use ($byNama) {
                $q->where('m.nm_brg', 'like', '%'.$byNama.'%')
                    ->orWhere('m.kd_brg', 'like', '%'.$byNama.'%');
                if (Schema::hasColumn('mas_brg', 'kd_bar')) {
                    $q->orWhere('m.kd_bar', 'like', '%'.$byNama.'%');
                }
            });
        }

        $select = ['m.kd_brg', 'm.nm_brg'];
        if (Schema::hasColumn('mas_brg', 'kd_bar')) {
            $select[] = 'm.kd_bar';
        }

        $rows = $query->select($select)
            ->distinct()
            ->orderBy('m.nm_brg')
            ->limit(3000)
            ->get()
            ->map(function ($row) {
                $sku = trim((string) ($row->kd_bar ?? ''));

                return [
                    'kd_brg' => $row->kd_brg,
                    'sku' => $sku !== '' ? $sku : $row->kd_brg,
                    'nm_brg' => $row->nm_brg,
                ];
            })
            ->values();

        return response()->json([
            'items' => $rows,
            'truncated' => $rows->count() >= 3000,
        ]);
    }

    public function store(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko, 403);

        $data = $this->validatedPromo($request);
        $noPromo = null;

        DB::transaction(function () use ($data, $kdToko, &$noPromo) {
            $noPromo = $this->nextNoPromo($kdToko);
            DiscPromo::create([
                'no_promo' => $noPromo,
                'nama_promo' => $data['nama_promo'],
                'tgl_awal' => $data['tgl_awal'],
                'tgl_akhir' => $data['tgl_akhir'],
                'disc_rupiah' => $data['disc_rupiah'] ?? 0,
                'disc_persen' => $data['disc_persen'] ?? 0,
                'kd_bayar' => $data['kd_bayar'],
                'by_nama' => $data['by_nama'] ?? '',
                'kd_toko' => $kdToko,
            ]);
            $this->syncLines($noPromo, $kdToko, $data['lines'] ?? []);
        });

        return back()->with('success', 'Promo disimpan ('.$noPromo.').');
    }

    public function update(Request $request, DiscPromo $discountPromo)
    {
        $this->authorizePromo($discountPromo);
        $data = $this->validatedPromo($request);

        DB::transaction(function () use ($data, $discountPromo) {
            $discountPromo->update([
                'nama_promo' => $data['nama_promo'],
                'tgl_awal' => $data['tgl_awal'],
                'tgl_akhir' => $data['tgl_akhir'],
                'disc_rupiah' => $data['disc_rupiah'] ?? 0,
                'disc_persen' => $data['disc_persen'] ?? 0,
                'kd_bayar' => $data['kd_bayar'],
                'by_nama' => $data['by_nama'] ?? '',
            ]);
            $this->syncLines($discountPromo->no_promo, $discountPromo->kd_toko, $data['lines'] ?? []);
        });

        return redirect()->route('master.discount-promo.index')->with('success', 'Promo diperbarui.');
    }

    public function destroy(DiscPromo $discountPromo)
    {
        $this->authorizePromo($discountPromo);
        DiscPromoDetail::where('no_promo', $discountPromo->no_promo)
            ->where('kd_toko', $discountPromo->kd_toko)
            ->delete();
        $discountPromo->delete();

        return back()->with('success', 'Promo dihapus.');
    }

    protected function nextNoPromo(string $kdToko): string
    {
        $prefix = 'PRM-'.now()->format('ym').'.';
        $last = 0;

        $existing = DiscPromo::query()
            ->where('kd_toko', $kdToko)
            ->where('no_promo', 'like', $prefix.'%')
            ->lockForUpdate()
            ->pluck('no_promo');

        foreach ($existing as $no) {
            $suffix = substr((string) $no, strlen($prefix));
            if ($suffix === '' || ! ctype_digit($suffix)) {
                continue;
            }
            $n = (int) $suffix;
            if ($n > $last) {
                $last = $n;
            }
        }

        return $prefix.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function authorizePromo(DiscPromo $promo): void
    {
        $kdToko = BranchContext::kdToko();
        if ($kdToko && $promo->kd_toko !== $kdToko) {
            abort(403);
        }
    }

    protected function validatedPromo(Request $request): array
    {
        $data = $request->validate([
            'nama_promo' => 'required|string|max:255',
            'tgl_awal' => 'required|date',
            'tgl_akhir' => 'required|date|after_or_equal:tgl_awal',
            'disc_rupiah' => 'nullable|numeric|min:0',
            'disc_persen' => 'nullable|numeric|min:0|max:100',
            'kd_bayar' => 'nullable|string|max:10',
            'by_nama' => 'nullable|string|max:255',
            'lines' => 'nullable|array',
            'lines.*.kd_brg' => 'nullable|string|max:50',
            'lines.*.disc_rupiah' => 'nullable|numeric|min:0',
            'lines.*.disc_persen' => 'nullable|numeric|min:0|max:100',
            'lines_json' => 'nullable|string',
        ]);

        $channel = strtoupper(trim((string) ($data['kd_bayar'] ?? '')));
        $data['kd_bayar'] = in_array($channel, ['BNI', 'TUNAI', 'TEMPO', 'COD', 'QRIS'], true) ? $channel : '';
        $data['by_nama'] = trim((string) ($data['by_nama'] ?? ''));

        if (! empty($data['lines_json'])) {
            $decoded = json_decode($data['lines_json'], true);
            if (is_array($decoded)) {
                $data['lines'] = $decoded;
            }
        }

        return $data;
    }

    protected function promoLines(DiscPromo $promo)
    {
        return $this->attachProductNames(
            DiscPromoDetail::query()
                ->where('no_promo', $promo->no_promo)
                ->where('kd_toko', $promo->kd_toko)
                ->orderBy('no_urut')
                ->get()
                ->values(),
            $promo->kd_toko
        );
    }

    protected function linesForPromos($promos, ?string $kdToko)
    {
        if ($promos->isEmpty() || ! Schema::hasTable('disc_promo_detail')) {
            return collect();
        }

        $details = DiscPromoDetail::query()
            ->whereIn('no_promo', $promos->pluck('no_promo'))
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->orderBy('no_urut')
            ->get();

        return $this->attachProductNames($details, $kdToko ?: (string) $promos->first()->kd_toko)
            ->groupBy('no_promo');
    }

    protected function attachProductNames($details, string $kdToko)
    {
        if ($details->isEmpty() || ! Schema::hasTable('mas_brg')) {
            return $details;
        }

        $cols = ['kd_brg', 'nm_brg'];
        if (Schema::hasColumn('mas_brg', 'kd_bar')) {
            $cols[] = 'kd_bar';
        }
        $names = DB::table('mas_brg')
            ->where('kd_toko', $kdToko)
            ->whereIn('kd_brg', $details->pluck('kd_brg')->unique()->filter()->all())
            ->get($cols)
            ->keyBy('kd_brg');

        return $details->map(function ($line) use ($names) {
            $row = $names->get($line->kd_brg);
            $line->nm_brg = $row->nm_brg ?? '';
            $sku = trim((string) ($row->kd_bar ?? ''));
            $line->sku = $sku !== '' ? $sku : $line->kd_brg;

            return $line;
        });
    }

    protected function syncLines(string $noPromo, string $kdToko, array $lines): void
    {
        DiscPromoDetail::where('no_promo', $noPromo)->where('kd_toko', $kdToko)->delete();

        foreach ($lines as $line) {
            $sku = trim((string) ($line['kd_brg'] ?? ''));
            if ($sku === '') {
                continue;
            }
            DiscPromoDetail::create([
                'no_promo' => $noPromo,
                'kd_brg' => $sku,
                'disc_rupiah' => $line['disc_rupiah'] ?? 0,
                'disc_persen' => $line['disc_persen'] ?? 0,
                'kd_toko' => $kdToko,
            ]);
        }
    }
}
