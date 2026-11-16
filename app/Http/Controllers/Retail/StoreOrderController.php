<?php

namespace App\Http\Controllers\Retail;

use App\Http\Controllers\Controller;
use App\Models\CatalogProduct;
use App\Models\Kemas;
use App\Models\Product;
use App\Services\Master\CatalogMirror;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class StoreOrderController extends Controller
{
    public function index()
    {
        abort_unless(Schema::hasTable('store_orders'), 501);

        $kdToko = BranchContext::kdToko();
        $orders = DB::table('store_orders')
            ->where('kd_toko', $kdToko)
            ->orderByDesc('tgl_po')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.retail.store-orders', compact('orders'));
    }

    public function create()
    {
        abort_unless(Schema::hasTable('store_orders'), 501);

        $kdToko = BranchContext::kdToko();
        $suggestedNo = $this->nextNoPo($kdToko);
        $units = Kemas::query()
            ->where('nm_sat1', '!=', '')
            ->where('nm_sat1', 'not like', '%NONE%')
            ->orderBy('nm_sat1')
            ->get(['no_urut', 'nm_sat1']);
        $recent = DB::table('store_orders')
            ->where('kd_toko', $kdToko)
            ->orderByDesc('tgl_po')
            ->orderByDesc('id')
            ->limit(20)
            ->get(['id', 'no_po', 'tgl_po', 'nm_sup', 'kd_sup', 'tot_pesan']);

        return view('admin.retail.store-order-create', compact('suggestedNo', 'units', 'recent'));
    }

    public function catalog(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko, 403);

        $q = trim((string) $request->get('q', ''));
        $filter = (string) $request->get('filter', 'semua');
        $noPo = strtoupper(trim((string) $request->get('no_po', '')));
        $page = max(1, (int) $request->get('page', 1));
        $perPage = 15;

        $stock = DB::table('v_stok_lokasi')
            ->select('kd_brg', DB::raw('SUM(qty) as qty'))
            ->where('kd_toko', $kdToko)
            ->groupBy('kd_brg');

        $query = DB::table('mas_brg as b')
            ->leftJoinSub($stock, 'st', 'st.kd_brg', '=', 'b.kd_brg')
            ->where('b.kd_toko', $kdToko)
            ->select('b.kd_brg', 'b.nm_brg', 'b.kd_kem1', 'b.nm_kem1', DB::raw('COALESCE(st.qty, 0) as qty'));

        if ($q !== '') {
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

        $total = (clone $query)->count();
        $rows = $query->orderBy('b.nm_brg')->forPage($page, $perPage)->get();
        $prices = $this->lastBuyPrices($kdToko, $rows->pluck('kd_brg')->all());
        $onOrder = $this->qtyOnOrder($kdToko, $noPo);

        $items = $rows->map(function ($row) use ($prices, $onOrder) {
            $last = $prices[$row->kd_brg] ?? null;
            $name = $this->unitName($last->nm_sat ?? null, $row->nm_kem1);

            return [
                'kd_brg' => $row->kd_brg,
                'nm_brg' => $row->nm_brg,
                'qty' => (float) $row->qty,
                'kd_sat' => (int) ($last->kd_sat ?? $row->kd_kem1 ?: 1),
                'nm_sat' => $name,
                'hrg_beli' => (float) ($last->hrg_beli ?? 0),
                'qty_pesan' => isset($onOrder[$row->kd_brg]) ? (float) $onOrder[$row->kd_brg] : null,
            ];
        })->values();

        return response()->json([
            'items' => $items,
            'page' => $page,
            'pages' => max(1, (int) ceil($total / $perPage)),
            'total' => $total,
        ]);
    }

    public function search(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $q = trim((string) $request->get('q', ''));
        $rows = DB::table('store_orders')
            ->where('kd_toko', $kdToko)
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('no_po', 'like', "%{$q}%")
                        ->orWhere('nm_sup', 'like', "%{$q}%")
                        ->orWhere('kd_sup', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('tgl_po')
            ->limit(20)
            ->get(['id', 'no_po', 'tgl_po', 'nm_sup', 'kd_sup', 'tot_pesan']);

        return response()->json($rows);
    }

    public function sheet(int $id)
    {
        $order = $this->orderForBranch($id);

        return response()->json([
            'order' => $order,
            'lines' => $this->linesFor($order->id),
        ]);
    }

    public function saveLines(Request $request)
    {
        $data = $request->validate([
            'no_po' => 'required|string|max:50',
            'tgl_po' => 'required|date',
            'kd_sup' => 'required|string|max:50',
            'nm_sup' => 'nullable|string|max:255',
            'ket' => 'nullable|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.kd_brg' => 'required|string|max:50',
            'items.*.nm_brg' => 'nullable|string|max:255',
            'items.*.qty' => 'nullable|numeric|min:0',
            'items.*.hrg_beli' => 'nullable|numeric|min:0',
            'items.*.kd_sat' => 'nullable|integer|min:1',
            'items.*.nm_sat' => 'nullable|string|max:50',
        ]);

        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko, 403);

        try {
            $order = DB::transaction(function () use ($data, $kdToko) {
            $order = $this->ensureOrder($kdToko, $data);
            foreach ($data['items'] as $item) {
                $qty = (float) ($item['qty'] ?? 0);
                if ($qty <= 0) {
                    $qty = 1;
                }
                $price = (float) ($item['hrg_beli'] ?? 0);
                $payload = [
                    'kd_brg' => $item['kd_brg'],
                    'nm_brg' => $item['nm_brg'] ?? '',
                    'kd_sat' => $item['kd_sat'] ?? 1,
                    'qty_pesan' => $qty,
                    'hrg_beli' => $price,
                    'jumlah' => $qty * $price,
                ];
                if (Schema::hasColumn('store_order_lines', 'nm_sat')) {
                    $payload['nm_sat'] = $this->unitName($item['nm_sat'] ?? null, null);
                }
                $existing = DB::table('store_order_lines')
                    ->where('store_order_id', $order->id)
                    ->where('kd_brg', $item['kd_brg'])
                    ->first();
                if ($existing) {
                    DB::table('store_order_lines')->where('id', $existing->id)->update($payload);
                } else {
                    $payload['store_order_id'] = $order->id;
                    DB::table('store_order_lines')->insert($payload);
                }
            }
            $this->refreshTotal($order->id);

            return $order;
        });
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'order_id' => $order->id,
            'lines' => $this->linesFor($order->id),
            'total' => (float) DB::table('store_orders')->where('id', $order->id)->value('tot_pesan'),
        ]);
    }

    public function destroyLine(int $lineId)
    {
        $kdToko = BranchContext::kdToko();
        $line = DB::table('store_order_lines')->where('id', $lineId)->first();
        abort_unless($line, 404);
        $order = DB::table('store_orders')->where('id', $line->store_order_id)->where('kd_toko', $kdToko)->first();
        abort_unless($order, 403);

        DB::table('store_order_lines')->where('id', $lineId)->delete();
        $this->refreshTotal($order->id);

        return response()->json([
            'ok' => true,
            'lines' => $this->linesFor($order->id),
            'total' => (float) DB::table('store_orders')->where('id', $order->id)->value('tot_pesan'),
        ]);
    }

    public function newItem(Request $request)
    {
        $data = $request->validate([
            'no_po' => 'required|string|max:50',
            'tgl_po' => 'required|date',
            'kd_sup' => 'required|string|max:50',
            'nm_sup' => 'nullable|string|max:255',
            'ket' => 'nullable|string|max:255',
            'nm_brg' => 'required|string|max:255',
            'kd_sat' => 'required|integer|min:1',
            'nm_sat' => 'required|string|max:50',
            'qty' => 'nullable|numeric|min:0',
            'hrg_beli' => 'nullable|numeric|min:0',
        ]);

        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko, 403);
        $sku = $this->nextNewSku();
        $qty = (float) ($data['qty'] ?? 0);
        if ($qty <= 0) {
            $qty = 1;
        }

        try {
            $order = DB::transaction(function () use ($data, $kdToko, $sku, $qty) {
            $product = Product::create([
                'kd_brg' => $sku,
                'kd_bar' => '',
                'nm_brg' => $data['nm_brg'],
                'kd_toko' => $kdToko,
                'kd_kem1' => $data['kd_sat'],
                'nm_kem1' => $this->unitName($data['nm_sat'], null),
                'jum_kem1' => 1,
                'hrg_jum1' => 0,
                'jml_brg' => 0,
                'brg_msk' => 0,
                'brg_klr' => 0,
                'cetak' => 0,
                'pilih' => 0,
                'copy' => 0,
                'id_user' => auth()->id() ?: 0,
            ]);
            app(CatalogMirror::class)->upsert($product);

            $order = $this->ensureOrder($kdToko, $data);
            $price = (float) ($data['hrg_beli'] ?? 0);
            $payload = [
                'store_order_id' => $order->id,
                'kd_brg' => $sku,
                'nm_brg' => $data['nm_brg'],
                'kd_sat' => $data['kd_sat'],
                'qty_pesan' => $qty,
                'hrg_beli' => $price,
                'jumlah' => $qty * $price,
            ];
            if (Schema::hasColumn('store_order_lines', 'nm_sat')) {
                $payload['nm_sat'] = $this->unitName($data['nm_sat'], null);
            }
            DB::table('store_order_lines')->insert($payload);
            $this->refreshTotal($order->id);

            return $order;
        });
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok' => true,
            'order_id' => $order->id,
            'lines' => $this->linesFor($order->id),
            'total' => (float) DB::table('store_orders')->where('id', $order->id)->value('tot_pesan'),
        ]);
    }

    public function excel(string $noPo)
    {
        $kdToko = BranchContext::kdToko();
        $header = DB::table('store_orders')->where('no_po', $noPo)->where('kd_toko', $kdToko)->first();
        abort_unless($header, 404);
        $lines = $this->linesFor($header->id);

        $filename = 'PO-'.$header->no_po.'.csv';

        return response()->streamDownload(function () use ($header, $lines) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['No PO', $header->no_po]);
            fputcsv($out, ['Tanggal', $header->tgl_po]);
            fputcsv($out, ['Supplier', $header->nm_sup ?: $header->kd_sup]);
            fputcsv($out, []);
            fputcsv($out, ['Barang', 'Qty', 'Satuan', 'Harga beli', 'Jumlah']);
            foreach ($lines as $line) {
                fputcsv($out, [$line->nm_brg, $line->qty_pesan, $line->nm_sat ?? '', $line->hrg_beli, $line->jumlah]);
            }
            fputcsv($out, ['', '', '', 'Total', $header->tot_pesan]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function store(Request $request)
    {
        abort_unless(Schema::hasTable('store_orders'), 501);

        $data = $request->validate([
            'tgl_po' => 'required|date',
            'kd_sup' => 'nullable|string|max:50',
            'ket' => 'nullable|string|max:255',
            'lines' => 'required|array|min:1',
            'lines.*.kd_brg' => 'required|string',
            'lines.*.nm_brg' => 'nullable|string',
            'lines.*.qty_pesan' => 'required|numeric|min:0.01',
            'lines.*.hrg_beli' => 'nullable|numeric|min:0',
            'lines.*.kd_sat' => 'nullable|integer|min:1',
        ]);

        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko, 403);

        $noPo = 'PS-'.now()->format('ymd').'-'.Str::upper(Str::random(4));
        $total = 0;

        DB::transaction(function () use ($data, $kdToko, $noPo, &$total) {
            foreach ($data['lines'] as $line) {
                $total += (float) $line['qty_pesan'] * (float) ($line['hrg_beli'] ?? 0);
            }

            $orderId = DB::table('store_orders')->insertGetId([
                'no_po' => $noPo,
                'kd_toko' => $kdToko,
                'kd_sup' => $data['kd_sup'] ?? '',
                'tgl_po' => $data['tgl_po'],
                'ket' => $data['ket'] ?? null,
                'tot_pesan' => $total,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($data['lines'] as $line) {
                $sub = (float) $line['qty_pesan'] * (float) ($line['hrg_beli'] ?? 0);
                DB::table('store_order_lines')->insert([
                    'store_order_id' => $orderId,
                    'kd_brg' => $line['kd_brg'],
                    'nm_brg' => $line['nm_brg'] ?? '',
                    'kd_sat' => $line['kd_sat'] ?? 1,
                    'qty_pesan' => $line['qty_pesan'],
                    'hrg_beli' => $line['hrg_beli'] ?? 0,
                    'jumlah' => $sub,
                ]);
            }
        });

        return redirect()->route('retail.orders.index')->with('success', 'Pemesanan '.$noPo.' disimpan (draft).');
    }

    public function submit(int $orderId)
    {
        $kdToko = BranchContext::kdToko();
        $updated = DB::table('store_orders')
            ->where('id', $orderId)
            ->where('kd_toko', $kdToko)
            ->where('status', 'draft')
            ->update(['status' => 'submitted', 'updated_at' => now()]);

        abort_unless($updated, 404);

        return back()->with('success', 'Pemesanan dikirim ke pusat (status: submitted).');
    }

    public function print(string $noPo)
    {
        $kdToko = BranchContext::kdToko();
        $header = DB::table('store_orders')
            ->where('no_po', $noPo)
            ->where('kd_toko', $kdToko)
            ->first();

        abort_unless($header, 404);

        $lines = $this->linesFor($header->id);

        return view('admin.retail.store-order-print', compact('header', 'lines'));
    }

    public function linkPo(Request $request, int $orderId)
    {
        $data = $request->validate(['purchase_order_id' => 'required|exists:purchase_orders,id']);

        $kdToko = BranchContext::kdToko();
        DB::table('store_orders')
            ->where('id', $orderId)
            ->where('kd_toko', $kdToko)
            ->update([
                'purchase_order_id' => $data['purchase_order_id'],
                'status' => 'linked_po',
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Pemesanan dihubungkan ke PO pusat #'.$data['purchase_order_id'].'.');
    }

    public function lookupProduct(Request $request)
    {
        $sku = trim((string) $request->get('sku', ''));
        $product = CatalogProduct::where('sku', $sku)->first();

        if (! $product) {
            return response()->json([]);
        }

        return response()->json([
            'kd_brg' => $product->sku,
            'nm_brg' => $product->name,
            'hrg_beli' => 0,
        ]);
    }

    protected function nextNoPo(?string $kdToko): string
    {
        $prefix = 'PO-'.now()->format('Ymd').'-';
        $last = DB::table('store_orders')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->where('no_po', 'like', $prefix.'%')
            ->orderByDesc('no_po')
            ->value('no_po');
        $n = 1;
        if ($last) {
            $seq = (int) substr($last, strlen($prefix));
            if ($seq > 0) {
                $n = $seq + 1;
            }
        }

        return $prefix.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    protected function nextNewSku(): string
    {
        $prefix = 'NEW-'.now()->format('Ymd').'-';
        $last = Product::query()->where('kd_brg', 'like', $prefix.'%')->orderByDesc('kd_brg')->value('kd_brg');
        $n = 1;
        if ($last) {
            $seq = (int) substr($last, strlen($prefix));
            if ($seq > 0) {
                $n = $seq + 1;
            }
        }

        return $prefix.str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    protected function ensureOrder(string $kdToko, array $data): object
    {
        $noPo = strtoupper(trim($data['no_po']));
        $existing = DB::table('store_orders')->where('kd_toko', $kdToko)->where('no_po', $noPo)->first();
        if ($existing) {
            if ($existing->kd_sup !== '' && $existing->kd_sup !== $data['kd_sup']) {
                throw new \RuntimeException('Satu PO hanya untuk satu supplier.');
            }
            $update = [
                'tgl_po' => $data['tgl_po'],
                'kd_sup' => $data['kd_sup'],
                'ket' => $data['ket'] ?? $existing->ket,
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('store_orders', 'nm_sup')) {
                $update['nm_sup'] = $data['nm_sup'] ?? $existing->nm_sup;
            }
            DB::table('store_orders')->where('id', $existing->id)->update($update);

            return DB::table('store_orders')->where('id', $existing->id)->first();
        }

        $insert = [
            'no_po' => $noPo,
            'kd_toko' => $kdToko,
            'kd_sup' => $data['kd_sup'],
            'tgl_po' => $data['tgl_po'],
            'ket' => $data['ket'] ?? null,
            'tot_pesan' => 0,
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('store_orders', 'nm_sup')) {
            $insert['nm_sup'] = $data['nm_sup'] ?? '';
        }
        $id = DB::table('store_orders')->insertGetId($insert);

        return DB::table('store_orders')->where('id', $id)->first();
    }

    protected function refreshTotal(int $orderId): void
    {
        $total = (float) DB::table('store_order_lines')->where('store_order_id', $orderId)->sum('jumlah');
        DB::table('store_orders')->where('id', $orderId)->update([
            'tot_pesan' => $total,
            'updated_at' => now(),
        ]);
    }

    protected function linesFor(int $orderId)
    {
        return DB::table('store_order_lines')->where('store_order_id', $orderId)->orderBy('id')->get();
    }

    protected function orderForBranch(int $id): object
    {
        $order = DB::table('store_orders')->where('id', $id)->where('kd_toko', BranchContext::kdToko())->first();
        abort_unless($order, 404);

        return $order;
    }

    protected function qtyOnOrder(?string $kdToko, string $noPo): array
    {
        if ($noPo === '' || ! $kdToko) {
            return [];
        }
        $order = DB::table('store_orders')->where('kd_toko', $kdToko)->where('no_po', $noPo)->first();
        if (! $order) {
            return [];
        }

        return DB::table('store_order_lines')
            ->where('store_order_id', $order->id)
            ->pluck('qty_pesan', 'kd_brg')
            ->all();
    }

    protected function lastBuyPrices(?string $kdToko, array $skus): array
    {
        if ($skus === [] || ! $kdToko) {
            return [];
        }
        $ids = DB::table('beli_brg')
            ->select('kd_brg', DB::raw('MAX(no_urut) as no_urut'))
            ->where('kd_toko', $kdToko)
            ->whereIn('kd_brg', $skus)
            ->where('ket', 'not like', '%MUTASI%')
            ->where('ket', 'not like', '%RETUR%')
            ->groupBy('kd_brg');

        return DB::table('beli_brg as b')
            ->joinSub($ids, 'last', 'last.no_urut', '=', 'b.no_urut')
            ->leftJoin('kemas as k', 'k.no_urut', '=', 'b.kd_sat')
            ->get(['b.kd_brg', 'b.hrg_beli', 'b.kd_sat', 'k.nm_sat1 as nm_sat'])
            ->keyBy('kd_brg')
            ->all();
    }

    protected function unitName(?string $fromPrice, ?string $fromProduct): string
    {
        foreach ([$fromPrice, $fromProduct] as $name) {
            $text = trim((string) $name);
            if ($text !== '' && ! preg_match('/^\d+$/', $text) && stripos($text, 'NONE') === false) {
                return $text;
            }
        }

        return 'pcs';
    }
}
