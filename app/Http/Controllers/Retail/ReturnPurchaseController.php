<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use App\Services\Finance\PostingService;
use App\Services\Inventory\InventoryMovementService;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReturnPurchaseController extends Controller
{
    public function __construct(
        protected InventoryMovementService $inventory,
        protected PostingService $posting
    ) {
    }

    public function index(Request $request)
    {
        abort_unless(Schema::hasTable('purchase_returns'), 501);

        $kdToko = BranchContext::kdToko();
        $q = trim((string) $request->get('q', ''));
        $items = DB::table('purchase_returns')
            ->when($kdToko, fn ($qry) => $qry->where('kd_toko', $kdToko))
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($inner) use ($q) {
                    $inner->where('no_retur', 'like', '%'.$q.'%')
                        ->orWhere('no_fak', 'like', '%'.$q.'%')
                        ->orWhere('kd_sup', 'like', '%'.$q.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.retail.return-purchases', [
            'items' => $items,
            'q' => $q,
            'suggestedNo' => $this->suggestReturnNumber($kdToko),
            'businessDate' => BranchContext::businessDate(),
        ]);
    }

    public function searchSuppliers(Request $request)
    {
        $keyword = trim((string) $request->get('keyword', ''));
        $cols = array_values(array_filter([
            'kd_sup',
            'nm_sup',
            Schema::hasColumn('supplier', 'al_sup') ? 'al_sup' : null,
            Schema::hasColumn('supplier', 'nm_sales') ? 'nm_sales' : null,
            Schema::hasColumn('supplier', 'telp_sup') ? 'telp_sup' : null,
            Schema::hasColumn('supplier', 'no_telp') ? 'no_telp' : null,
        ]));

        $rows = Supplier::query()
            ->when($keyword, fn ($q) => $q->where(function ($q) use ($keyword) {
                $q->where('kd_sup', 'like', '%'.$keyword.'%')
                    ->orWhere('nm_sup', 'like', '%'.$keyword.'%');
            }))
            ->orderBy('nm_sup')
            ->limit(15)
            ->get($cols);

        return response()->json($rows);
    }

    public function searchLots(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko && Schema::hasTable('beli_brg'), 403);

        $kdSup = trim((string) $request->get('kd_sup', ''));
        $nmBrg = trim((string) $request->get('nm_brg', ''));
        $noFak = trim((string) $request->get('no_fak', ''));
        $hasStok = Schema::hasColumn('beli_brg', 'stok_jual');

        $query = DB::table('beli_brg as bb')
            ->leftJoin('mas_brg as mb', function ($join) {
                $join->on('mb.kd_brg', '=', 'bb.kd_brg')->on('mb.kd_toko', '=', 'bb.kd_toko');
            })
            ->leftJoin('supplier as s', 's.kd_sup', '=', 'bb.kd_sup')
            ->where('bb.kd_toko', $kdToko)
            ->when($hasStok, fn ($q) => $q->where('bb.stok_jual', '>', 0))
            ->when($kdSup !== '', fn ($q) => $q->where('bb.kd_sup', $kdSup))
            ->when($noFak !== '', fn ($q) => $q->where('bb.no_fak', 'like', '%'.$noFak.'%'))
            ->when($nmBrg !== '', function ($q) use ($nmBrg) {
                $q->where(function ($inner) use ($nmBrg) {
                    $inner->where('mb.nm_brg', 'like', '%'.$nmBrg.'%')
                        ->orWhere('bb.kd_brg', 'like', '%'.$nmBrg.'%');
                });
            })
            ->when(Schema::hasColumn('beli_brg', 'tgl_fak'), fn ($q) => $q->orderByDesc('bb.tgl_fak'))
            ->orderByDesc('bb.no_urut')
            ->select(array_values(array_filter([
                'bb.no_urut',
                'bb.no_fak',
                'bb.tgl_fak',
                'bb.kd_brg',
                'bb.kd_sat',
                'bb.jml_brg',
                'bb.hrg_beli',
                'bb.kd_sup',
                $hasStok ? 'bb.stok_jual' : null,
                'mb.nm_brg',
                's.nm_sup',
            ])));

        $page = $query->paginate(15)->withQueryString();
        $rows = $this->withRemainingQty($page->getCollection(), $kdToko);

        return response()->json([
            'items' => $rows->values(),
            'current_page' => $page->currentPage(),
            'last_page' => $page->lastPage(),
            'total' => $page->total(),
        ]);
    }

    public function lookupPurchase(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $noFak = trim((string) $request->get('no_fak', ''));

        $purchase = Purchase::where('no_fak', $noFak)
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->first();

        if (! $purchase) {
            return response()->json(['items' => [], 'message' => 'Nota beli tidak ditemukan.']);
        }

        $cols = ['no_urut', 'kd_brg', 'kd_sat', 'jml_brg', 'hrg_beli', 'no_fak', 'tgl_fak', 'kd_sup'];
        if (Schema::hasColumn('beli_brg', 'stok_jual')) {
            $cols[] = 'stok_jual';
        }

        $items = PurchaseItem::where('no_fak', $purchase->no_fak)
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->get($cols);

        $names = Schema::hasTable('mas_brg')
            ? DB::table('mas_brg')
                ->where('kd_toko', $kdToko)
                ->whereIn('kd_brg', $items->pluck('kd_brg')->unique()->filter()->all())
                ->pluck('nm_brg', 'kd_brg')
            : collect();

        $items = $this->withRemainingQty($items, $kdToko)->map(function ($it) use ($purchase, $names) {
            $it->nm_brg = $names[$it->kd_brg] ?? '';
            $it->nm_sup = $purchase->nm_sup ?? '';
            $it->kd_sup = $it->kd_sup ?: $purchase->kd_sup;

            return $it;
        });

        return response()->json([
            'items' => $items->values(),
            'kd_sup' => $purchase->kd_sup,
            'nm_sup' => $purchase->nm_sup,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(Schema::hasTable('purchase_returns'), 501);

        $data = $request->validate([
            'no_retur' => 'nullable|string|max:100',
            'no_fak' => 'nullable|string|max:100',
            'tgl_retur' => 'required|date',
            'kd_sup' => 'nullable|string|max:50',
            'ket' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.no_urut' => 'nullable|integer',
            'lines.*.no_fak' => 'nullable|string|max:100',
            'lines.*.kd_brg' => 'required|string',
            'lines.*.kd_sat' => 'nullable|integer|min:1',
            'lines.*.qty' => 'required|numeric|min:0.01',
            'lines.*.hrg_beli' => 'required|numeric|min:0',
        ]);

        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko, 403);

        $lines = collect($data['lines'])->filter(fn ($line) => (float) $line['qty'] > 0)->values();
        if ($lines->isEmpty()) {
            return back()->with('error', 'Pilih barang retur terlebih dahulu.');
        }

        $noFak = trim((string) ($data['no_fak'] ?? ($lines->first()['no_fak'] ?? '')));
        if ($noFak === '') {
            return back()->with('error', 'Nota beli asal belum dipilih.');
        }

        $noRetur = trim((string) ($data['no_retur'] ?? ''));
        if ($noRetur === '' || DB::table('purchase_returns')->where('kd_toko', $kdToko)->where('no_retur', $noRetur)->exists()) {
            $noRetur = $this->suggestReturnNumber($kdToko, $data['tgl_retur']);
        }
        $total = 0;

        DB::transaction(function () use ($data, $kdToko, $noRetur, $noFak, $lines, &$total) {
            foreach ($lines as $line) {
                $total += (float) $line['qty'] * (float) $line['hrg_beli'];
            }

            $payload = [
                'no_retur' => $noRetur,
                'no_fak' => $noFak,
                'tgl_retur' => $data['tgl_retur'],
                'kd_toko' => $kdToko,
                'kd_sup' => $data['kd_sup'] ?? null,
                'total' => $total,
                'ket' => $data['ket'] ?? null,
            ];
            if (Schema::hasColumn('purchase_returns', 'created_at')) {
                $payload['created_at'] = now();
                $payload['updated_at'] = now();
            }
            $returnId = DB::table('purchase_returns')->insertGetId($payload);

            foreach ($lines as $line) {
                DB::table('purchase_return_lines')->insert([
                    'purchase_return_id' => $returnId,
                    'kd_brg' => $line['kd_brg'],
                    'kd_sat' => $line['kd_sat'] ?? 1,
                    'qty' => $line['qty'],
                    'hrg_beli' => $line['hrg_beli'],
                    'subtotal' => (float) $line['qty'] * (float) $line['hrg_beli'],
                ]);

                $this->inventory->issuePurchaseReturnLine(
                    $line['kd_brg'],
                    (float) $line['qty'],
                    (int) ($line['kd_sat'] ?? 1),
                    $kdToko,
                    $noRetur
                );
            }

            $this->posting->postPurchaseReturn($total, $noRetur, $data['tgl_retur']);
        });

        return back()->with('success', 'Retur pembelian '.$noRetur.' disimpan.');
    }

    protected function suggestReturnNumber(?string $kdToko, ?string $date = null): string
    {
        $date = $date ?: BranchContext::businessDate();
        $toko = $kdToko ?: 'TK';
        $stamp = date('dmy', strtotime($date));
        $last = 0;
        if ($kdToko && Schema::hasTable('purchase_returns')) {
            $existing = DB::table('purchase_returns')
                ->where('kd_toko', $kdToko)
                ->where('no_retur', 'like', 'RB.%')
                ->pluck('no_retur');
            foreach ($existing as $no) {
                if (preg_match('/^RB\.(\d+)-/', (string) $no, $m)) {
                    $last = max($last, (int) $m[1]);
                }
            }
        }

        return 'RB.'.($last + 1).'-'.$toko.'.'.$stamp;
    }

    protected function withRemainingQty($items, string $kdToko)
    {
        $hasStok = Schema::hasColumn('beli_brg', 'stok_jual');
        $returned = collect();
        if (! $items->isEmpty() && Schema::hasTable('purchase_return_lines')) {
            $faks = $items->pluck('no_fak')->unique()->filter()->all();
            $returned = DB::table('purchase_return_lines as l')
                ->join('purchase_returns as r', 'r.id', '=', 'l.purchase_return_id')
                ->where('r.kd_toko', $kdToko)
                ->whereIn('r.no_fak', $faks ?: [''])
                ->groupBy('r.no_fak', 'l.kd_brg')
                ->selectRaw('r.no_fak, l.kd_brg, SUM(l.qty) as qty_retur')
                ->get()
                ->mapWithKeys(fn ($row) => [$row->no_fak.'|'.$row->kd_brg => (float) $row->qty_retur]);
        }

        return $items->map(function ($it) use ($hasStok, $returned) {
            $sold = (float) ($it->jml_brg ?? 0);
            $done = (float) ($returned[($it->no_fak ?? '').'|'.($it->kd_brg ?? '')] ?? 0);
            $fromLot = $hasStok ? (float) ($it->stok_jual ?? 0) : max(0, $sold - $done);
            $it->qty_sisa = $hasStok ? max(0, $fromLot) : max(0, round($sold - $done, 4));

            return $it;
        });
    }
}
