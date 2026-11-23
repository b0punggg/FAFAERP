<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\CatalogProduct;
use App\Models\Product;
use App\Services\Inventory\InventoryMovementService;
use App\Services\Master\CatalogMirror;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StockOpnameController extends Controller
{
    public function __construct(protected InventoryMovementService $inventory)
    {
    }

    public function index()
    {
        $warehouseId = BranchContext::warehouseId();
        $kdToko = BranchContext::kdToko();
        $central = (string) config('erp.central_kd_toko', 'PUSAT');
        $isPusat = $kdToko === $central;

        return view('admin.retail.opname', [
            'warehouseId' => $warehouseId,
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
            'isPusat' => $isPusat,
            'opnameHint' => $isPusat
                ? 'Opname gudang PUSAT dikerjakan di aplikasi inventory (Opname Pusat), bukan di kasir gerai.'
                : 'Opname gerai hanya mengubah stok gudang gerai ini. Tidak menambah gudang PUSAT.',
        ]);
    }

    public function history()
    {
        $warehouseId = BranchContext::warehouseId();
        if (! Schema::hasTable('stok_opname') || ! $warehouseId) {
            $sessions = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 15);
        } else {
            $sessions = DB::table('stok_opname')
                ->where('warehouse_id', $warehouseId)
                ->orderByDesc('id')
                ->paginate(15);
        }

        return view('admin.retail.opname-history', compact('sessions', 'warehouseId'));
    }

    public function catalog(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko, 403);

        $q = trim((string) $request->get('q', ''));
        $barcode = trim((string) $request->get('barcode', ''));
        $filter = (string) $request->get('filter', 'semua');
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 15;

        $query = $this->itemQuery($kdToko, $filter, $q, $barcode);
        $total = (clone $query)->count();
        $rows = $query->orderBy('b.nm_brg')->forPage($page, $perPage)->get();
        $last = $this->lastChecks($rows->pluck('kd_brg')->all());

        $items = $rows->map(function ($row) use ($last) {
            $pack = $this->packOf($row);
            $qty = (float) $row->qty;
            $small = $this->unitName($row->nm_kem1);
            $note = $last[$row->kd_brg] ?? null;

            return [
                'kd_brg' => $row->kd_brg,
                'kd_bar' => $row->kd_bar,
                'nm_brg' => $row->nm_brg,
                'qty' => $qty,
                'nm_sat' => $small,
                'has_pack' => $pack['has'],
                'factor' => $pack['factor'],
                'nm_pack' => $pack['name'],
                'qty_pack' => $pack['has'] ? round($qty / $pack['factor'], 2) : null,
                'last_date' => $note->tgl_opname ?? null,
                'last_ket' => $note->ket ?? '',
                'note_count' => (int) ($note->jumlah ?? 0),
            ];
        })->values();

        return response()->json([
            'items' => $items,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
        ]);
    }

    public function progress(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $warehouseId = BranchContext::warehouseId();
        abort_unless($kdToko && $warehouseId, 403);

        $from = (string) $request->get('from', now()->startOfMonth()->toDateString());
        $to = (string) $request->get('to', now()->toDateString());
        $filter = (string) $request->get('filter', 'semua');
        if ($from > $to) {
            return response()->json(['ok' => false, 'message' => 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.'], 422);
        }

        $base = $this->itemQuery($kdToko, $filter, '', '');
        $total = (clone $base)->count();
        $done = (clone $base)->whereExists(function ($query) use ($from, $to, $warehouseId) {
            $query->select(DB::raw(1))
                ->from('stok_opname_lines as l')
                ->join('stok_opname as o', 'o.id', '=', 'l.stok_opname_id')
                ->join('products as p', 'p.id', '=', 'l.product_id')
                ->whereColumn('p.sku', 'b.kd_brg')
                ->where('o.warehouse_id', $warehouseId)
                ->where('o.status', 'posted')
                ->whereBetween('o.tgl_opname', [$from, $to]);
        })->count();

        return response()->json([
            'ok' => true,
            'total' => $total,
            'sudah' => $done,
            'belum' => max(0, $total - $done),
            'persen' => $total > 0 ? round($done / $total * 100, 1) : 0,
        ]);
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'kd_brg' => 'required|string|max:50',
            'qty' => 'required|numeric|min:0',
            'mode' => 'required|in:kecil,pack',
            'ket' => 'nullable|string|max:255',
        ]);

        $kdToko = BranchContext::kdToko();
        $warehouseId = BranchContext::warehouseId();
        abort_unless($kdToko && $warehouseId, 403);
        abort_if($kdToko === (string) config('erp.central_kd_toko', 'PUSAT'), 403, 'Opname PUSAT hanya di inventory.');

        $product = Product::query()->where('kd_brg', $data['kd_brg'])->where('kd_toko', $kdToko)->first();
        abort_unless($product, 404);

        $pack = $this->packOf($product);
        $qty = (float) $data['qty'];
        $baseQty = $data['mode'] === 'pack' && $pack['has'] ? $qty * $pack['factor'] : $qty;

        $result = DB::transaction(function () use ($product, $warehouseId, $baseQty, $data) {
            app(CatalogMirror::class)->upsert($product);
            $catalog = CatalogProduct::where('sku', $product->kd_brg)->first();
            abort_unless($catalog, 422);
            $system = $this->inventory->qtyOnHand($warehouseId, $catalog->id);
            $this->inventory->setPhysicalCount($warehouseId, $catalog->id, $baseQty, $product->kd_brg);

            $header = [
                'warehouse_id' => $warehouseId,
                'tgl_opname' => now()->toDateString(),
                'status' => 'posted',
                'created_at' => now(),
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('stok_opname', 'notes')) {
                $header['notes'] = $data['ket'] ?? null;
            }
            $opnameId = DB::table('stok_opname')->insertGetId($header);
            $line = [
                'stok_opname_id' => $opnameId,
                'product_id' => $catalog->id,
                'qty_system' => $system,
                'qty_physical' => $baseQty,
            ];
            if (Schema::hasColumn('stok_opname_lines', 'ket')) {
                $line['ket'] = $data['ket'] ?? null;
            }
            DB::table('stok_opname_lines')->insert($line);

            return ['qty' => $baseQty, 'before' => $system];
        });

        return response()->json(['ok' => true] + $result);
    }

    public function notes(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $warehouseId = BranchContext::warehouseId();
        $sku = trim((string) $request->get('kd_brg', ''));
        abort_unless($kdToko && $warehouseId && $sku !== '', 422);

        $rows = DB::table('stok_opname_lines as l')
            ->join('stok_opname as o', 'o.id', '=', 'l.stok_opname_id')
            ->join('products as p', 'p.id', '=', 'l.product_id')
            ->where('p.sku', $sku)
            ->where('o.warehouse_id', $warehouseId)
            ->orderByDesc('o.tgl_opname')
            ->orderByDesc('l.id')
            ->limit(30)
            ->get([
                'l.id',
                'o.tgl_opname',
                'l.qty_system',
                'l.qty_physical',
                Schema::hasColumn('stok_opname_lines', 'ket') ? 'l.ket' : DB::raw('o.notes as ket'),
            ]);

        return response()->json($rows);
    }

    public function updateNote(Request $request, int $lineId)
    {
        $data = $request->validate(['ket' => 'nullable|string|max:255']);
        $warehouseId = BranchContext::warehouseId();
        $line = DB::table('stok_opname_lines as l')
            ->join('stok_opname as o', 'o.id', '=', 'l.stok_opname_id')
            ->where('l.id', $lineId)
            ->where('o.warehouse_id', $warehouseId)
            ->select('l.id', 'o.id as opname_id')
            ->first();
        abort_unless($line, 404);

        if (Schema::hasColumn('stok_opname_lines', 'ket')) {
            DB::table('stok_opname_lines')->where('id', $lineId)->update(['ket' => $data['ket'] ?? null]);
        }
        DB::table('stok_opname')->where('id', $line->opname_id)->update([
            'notes' => $data['ket'] ?? null,
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    public function store(Request $request)
    {
        $warehouseId = BranchContext::warehouseId();
        abort_unless($warehouseId && Schema::hasTable('stok_opname'), 403);

        $data = $request->validate([
            'tgl_opname' => 'required|date',
            'notes' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.qty_physical' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($warehouseId, $data) {
            $opnameId = DB::table('stok_opname')->insertGetId([
                'warehouse_id' => $warehouseId,
                'tgl_opname' => $data['tgl_opname'],
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['lines'] as $line) {
                $productId = (int) $line['product_id'];
                $system = $this->inventory->qtyOnHand($warehouseId, $productId);

                DB::table('stok_opname_lines')->insert([
                    'stok_opname_id' => $opnameId,
                    'product_id' => $productId,
                    'qty_system' => $system,
                    'qty_physical' => $line['qty_physical'],
                ]);
            }
        });

        return back()->with('success', 'Opname draft disimpan — approve untuk posting stok.');
    }

    public function approve(int $opnameId)
    {
        $warehouseId = BranchContext::warehouseId();
        abort_unless($warehouseId, 403);

        $session = DB::table('stok_opname')
            ->where('id', $opnameId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', 'draft')
            ->first();

        abort_unless($session, 404);

        $lines = DB::table('stok_opname_lines')->where('stok_opname_id', $opnameId)->get();

        DB::transaction(function () use ($lines, $warehouseId, $opnameId) {
            foreach ($lines as $line) {
                $this->inventory->setPhysicalCount(
                    $warehouseId,
                    (int) $line->product_id,
                    (float) $line->qty_physical,
                    (string) $opnameId
                );
            }

            DB::table('stok_opname')->where('id', $opnameId)->update([
                'status' => 'posted',
                'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'Opname #'.$opnameId.' diposting.');
    }

    protected function itemQuery(string $kdToko, string $filter, string $q, string $barcode)
    {
        $stock = DB::table('v_stok_lokasi')
            ->select('kd_brg', DB::raw('SUM(qty) as qty'))
            ->where('kd_toko', $kdToko)
            ->groupBy('kd_brg');

        $query = DB::table('mas_brg as b')
            ->leftJoinSub($stock, 'st', 'st.kd_brg', '=', 'b.kd_brg')
            ->where('b.kd_toko', $kdToko)
            ->select(
                'b.kd_brg',
                'b.kd_bar',
                'b.nm_brg',
                'b.nm_kem1',
                'b.nm_kem2',
                'b.jum_kem2',
                'b.nm_kem3',
                'b.jum_kem3',
                DB::raw('COALESCE(st.qty, 0) as qty')
            );

        if ($barcode !== '') {
            $query->where('b.kd_bar', $barcode);
        } elseif ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('b.nm_brg', 'like', "%{$q}%")
                    ->orWhere('b.kd_brg', 'like', "%{$q}%")
                    ->orWhere('b.kd_bar', 'like', "%{$q}%");
            });
        }
        if ($filter === 'ada_stok') {
            $query->where('st.qty', '>', 0);
        } elseif ($filter === 'stok_nol') {
            $query->where(function ($inner) {
                $inner->whereNull('st.qty')->orWhere('st.qty', '<=', 0);
            });
        }

        return $query;
    }

    protected function lastChecks(array $skus)
    {
        $warehouseId = BranchContext::warehouseId();
        if ($skus === [] || ! $warehouseId) {
            return [];
        }
        $ket = Schema::hasColumn('stok_opname_lines', 'ket') ? 'l.ket' : 'o.notes';
        $ids = DB::table('stok_opname_lines as l')
            ->join('stok_opname as o', 'o.id', '=', 'l.stok_opname_id')
            ->join('products as p', 'p.id', '=', 'l.product_id')
            ->where('o.warehouse_id', $warehouseId)
            ->where('o.status', 'posted')
            ->whereIn('p.sku', $skus)
            ->select('p.sku', DB::raw('MAX(l.id) as id'))
            ->groupBy('p.sku');

        return DB::table('stok_opname_lines as l')
            ->joinSub($ids, 'last', 'last.id', '=', 'l.id')
            ->join('stok_opname as o', 'o.id', '=', 'l.stok_opname_id')
            ->join('products as p', 'p.id', '=', 'l.product_id')
            ->select('p.sku as kd_brg', 'o.tgl_opname', DB::raw($ket.' as ket'), DB::raw('(SELECT COUNT(*) FROM stok_opname_lines lx JOIN products px ON px.id = lx.product_id JOIN stok_opname ox ON ox.id = lx.stok_opname_id WHERE px.sku = p.sku AND ox.warehouse_id = o.warehouse_id) as jumlah'))
            ->get()
            ->keyBy('kd_brg')
            ->all();
    }

    protected function packOf(object $row): array
    {
        foreach ([2, 3] as $n) {
            $name = trim((string) ($row->{'nm_kem'.$n} ?? ''));
            $factor = (float) ($row->{'jum_kem'.$n} ?? 0);
            if ($name !== '' && ! preg_match('/^\d+$/', $name) && stripos($name, 'NONE') === false && $factor > 1) {
                return ['has' => true, 'factor' => $factor, 'name' => $name];
            }
        }

        return ['has' => false, 'factor' => 1, 'name' => ''];
    }

    protected function unitName(?string $name): string
    {
        $text = trim((string) $name);

        return $text !== '' && ! preg_match('/^\d+$/', $text) && stripos($text, 'NONE') === false ? $text : 'pcs';
    }
}
