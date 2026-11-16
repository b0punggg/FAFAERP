<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Finance\PostingService;
use App\Services\Inventory\InventoryMovementService;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReturnSaleController extends Controller
{
    public function __construct(
        protected InventoryMovementService $inventory,
        protected PostingService $posting
    ) {
    }

    public function index(Request $request)
    {
        abort_unless(Schema::hasTable('sales_returns'), 501);

        $kdToko = BranchContext::kdToko();
        $q = trim((string) $request->get('q', ''));
        $items = DB::table('sales_returns')
            ->when($kdToko, fn ($qry) => $qry->where('kd_toko', $kdToko))
            ->when($q !== '', function ($qry) use ($q) {
                $qry->where(function ($inner) use ($q) {
                    $inner->where('no_retur', 'like', '%'.$q.'%')
                        ->orWhere('no_fak', 'like', '%'.$q.'%');
                });
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.retail.return-sales', [
            'items' => $items,
            'q' => $q,
            'suggestedNo' => $this->suggestReturnNumber($kdToko),
            'businessDate' => BranchContext::businessDate(),
        ]);
    }

    public function searchSoldItems(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko && Schema::hasTable('jual_brg'), 403);

        $tgl = trim((string) $request->get('tgl', ''));
        $noFak = trim((string) $request->get('no_fak', ''));
        $nmBrg = trim((string) $request->get('nm_brg', ''));
        $nmPel = trim((string) $request->get('nm_pel', ''));

        $query = DB::table('jual_brg as jb')
            ->leftJoin('jual as j', function ($join) {
                $join->on('j.no_fak', '=', 'jb.no_fak')->on('j.kd_toko', '=', 'jb.kd_toko');
            })
            ->where('jb.kd_toko', $kdToko)
            ->when($tgl !== '', fn ($q) => $q->whereDate('jb.tgl_jual', $tgl))
            ->when($noFak !== '', fn ($q) => $q->where('jb.no_fak', 'like', '%'.$noFak.'%'))
            ->when($nmBrg !== '', function ($q) use ($nmBrg) {
                $q->where(function ($inner) use ($nmBrg) {
                    $inner->where('jb.nm_brg', 'like', '%'.$nmBrg.'%')
                        ->orWhere('jb.kd_brg', 'like', '%'.$nmBrg.'%');
                });
            })
            ->when($nmPel !== '', fn ($q) => $q->where('j.nm_pel', 'like', '%'.$nmPel.'%'))
            ->orderByDesc('jb.tgl_jual')
            ->orderByDesc('jb.no_urut')
            ->select(array_values(array_filter([
                'jb.no_urut',
                'jb.no_fak',
                'jb.tgl_jual',
                'jb.kd_brg',
                'jb.nm_brg',
                'jb.kd_sat',
                Schema::hasColumn('jual_brg', 'nm_sat') ? 'jb.nm_sat' : null,
                'jb.jml_brg',
                'jb.hrg_jual',
                Schema::hasColumn('jual_brg', 'disc') ? 'jb.disc' : null,
                'j.nm_pel',
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

    public function lookupSale(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $noFak = trim((string) $request->get('no_fak', ''));

        $sale = Sale::where('no_fak', $noFak)
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->first();

        if (! $sale) {
            return response()->json(['items' => [], 'message' => 'Nota jual tidak ditemukan.']);
        }

        $items = SaleItem::where('no_fak', $sale->no_fak)
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->get(['no_urut', 'kd_brg', 'nm_brg', 'kd_sat', 'nm_sat', 'jml_brg', 'hrg_jual', 'disc', 'no_fak', 'tgl_jual']);

        $items = $this->withRemainingQty($items, $kdToko)->map(function ($it) use ($sale) {
            $it->nm_pel = $sale->nm_pel;

            return $it;
        });

        return response()->json(['items' => $items->values(), 'tgl_jual' => $sale->tgl_jual, 'nm_pel' => $sale->nm_pel]);
    }

    public function store(Request $request)
    {
        abort_unless(Schema::hasTable('sales_returns'), 501);

        $data = $request->validate([
            'no_retur' => 'nullable|string|max:100',
            'no_fak' => 'nullable|string|max:100',
            'tgl_retur' => 'required|date',
            'ket' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.no_urut' => 'nullable|integer',
            'lines.*.no_fak' => 'nullable|string|max:100',
            'lines.*.kd_brg' => 'required|string',
            'lines.*.nm_brg' => 'nullable|string',
            'lines.*.kd_sat' => 'nullable|integer|min:1',
            'lines.*.qty' => 'required|numeric|min:0.01',
            'lines.*.hrg_jual' => 'required|numeric|min:0',
        ]);

        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko, 403);

        $lines = collect($data['lines'])->filter(fn ($line) => (float) $line['qty'] > 0)->values();
        if ($lines->isEmpty()) {
            return back()->with('error', 'Pilih barang retur terlebih dahulu.');
        }

        $noFak = trim((string) ($data['no_fak'] ?? ($lines->first()['no_fak'] ?? '')));
        if ($noFak === '') {
            return back()->with('error', 'Nota jual asal belum dipilih.');
        }

        $noRetur = trim((string) ($data['no_retur'] ?? ''));
        if ($noRetur === '' || DB::table('sales_returns')->where('kd_toko', $kdToko)->where('no_retur', $noRetur)->exists()) {
            $noRetur = $this->suggestReturnNumber($kdToko, $data['tgl_retur']);
        }
        $total = 0;

        DB::transaction(function () use ($data, $kdToko, $noRetur, $noFak, $lines, &$total) {
            foreach ($lines as $line) {
                $total += (float) $line['qty'] * (float) $line['hrg_jual'];
            }

            $payload = [
                'no_retur' => $noRetur,
                'no_fak' => $noFak,
                'tgl_retur' => $data['tgl_retur'],
                'kd_toko' => $kdToko,
                'total' => $total,
                'ket' => $data['ket'] ?? null,
            ];
            if (Schema::hasColumn('sales_returns', 'created_at')) {
                $payload['created_at'] = now();
                $payload['updated_at'] = now();
            }
            $returnId = DB::table('sales_returns')->insertGetId($payload);

            foreach ($lines as $line) {
                $sub = (float) $line['qty'] * (float) $line['hrg_jual'];
                DB::table('sales_return_lines')->insert([
                    'sales_return_id' => $returnId,
                    'kd_brg' => $line['kd_brg'],
                    'nm_brg' => $line['nm_brg'] ?? '',
                    'kd_sat' => $line['kd_sat'] ?? 1,
                    'qty' => $line['qty'],
                    'hrg_jual' => $line['hrg_jual'],
                    'subtotal' => $sub,
                ]);

                $this->inventory->restoreForSaleLine(
                    $line['kd_brg'],
                    (float) $line['qty'],
                    (int) ($line['kd_sat'] ?? 1),
                    $kdToko,
                    $noRetur
                );
            }

            $this->posting->postSalesReturn($total, $noRetur, $data['tgl_retur']);
        });

        return redirect()->route('retail.returns.sales.print', $noRetur)->with('success', 'Retur penjualan disimpan.');
    }

    public function print(string $noRetur)
    {
        $kdToko = BranchContext::kdToko();
        $header = DB::table('sales_returns')
            ->where('no_retur', $noRetur)
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->first();

        abort_unless($header, 404);

        $lines = DB::table('sales_return_lines')
            ->where('sales_return_id', $header->id)
            ->get();

        return view('admin.retail.return-sales-print', compact('header', 'lines'));
    }

    protected function suggestReturnNumber(?string $kdToko, ?string $date = null): string
    {
        $date = $date ?: BranchContext::businessDate();
        $toko = preg_replace('/[^A-Za-z0-9]/', '', (string) $kdToko) ?: 'TK';
        $user = (int) (auth()->id() ?: 0);
        $prefix = 'JR'.$toko.'.'.$user.'.'.date('dmy', strtotime($date)).'-';
        $last = 0;
        if ($kdToko && Schema::hasTable('sales_returns')) {
            $existing = DB::table('sales_returns')
                ->where('kd_toko', $kdToko)
                ->where('no_retur', 'like', $prefix.'%')
                ->pluck('no_retur');
            foreach ($existing as $no) {
                $n = (int) substr((string) $no, strlen($prefix));
                if ($n > $last) {
                    $last = $n;
                }
            }
        }

        return $prefix.($last + 1);
    }

    protected function withRemainingQty($items, string $kdToko)
    {
        if ($items->isEmpty() || ! Schema::hasTable('sales_return_lines')) {
            return $items->map(function ($it) {
                $it->qty_sisa = (float) ($it->jml_brg ?? 0);

                return $it;
            });
        }

        $faks = $items->pluck('no_fak')->unique()->filter()->all();
        $returned = DB::table('sales_return_lines as l')
            ->join('sales_returns as r', 'r.id', '=', 'l.sales_return_id')
            ->where('r.kd_toko', $kdToko)
            ->whereIn('r.no_fak', $faks ?: [''])
            ->groupBy('r.no_fak', 'l.kd_brg')
            ->selectRaw('r.no_fak, l.kd_brg, SUM(l.qty) as qty_retur')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->no_fak.'|'.$row->kd_brg => (float) $row->qty_retur]);

        return $items->map(function ($it) use ($returned) {
            $sold = (float) ($it->jml_brg ?? 0);
            $done = (float) ($returned[($it->no_fak ?? '').'|'.($it->kd_brg ?? '')] ?? 0);
            $it->qty_sisa = max(0, round($sold - $done, 4));

            return $it;
        });
    }
}
