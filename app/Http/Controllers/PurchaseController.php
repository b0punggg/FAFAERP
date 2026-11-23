<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Services\Finance\PostingService;
use App\Services\Inventory\InventoryMovementService;
use App\Models\Supplier;
use App\Services\Catalog\BranchPricingService;
use App\Services\Retail\DocumentNumberService;
use App\Support\BranchContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PurchaseController extends Controller
{
    public function __construct(
        protected PostingService $posting,
        protected InventoryMovementService $inventory,
        protected BranchPricingService $pricing,
        protected DocumentNumberService $numbers
    ) {
    }
    public function index(Request $request)
    {
        $kd_toko = BranchContext::kdToko();
        $q = trim((string) $request->get('q', ''));
        $from = $request->get('from');
        $to = $request->get('to');
        $bayar = (string) $request->get('bayar', '');
        $sort = (string) $request->get('sort', 'terbaru');
        if (! in_array($bayar, ['TUNAI', 'TEMPO', 'HUTANG'], true)) {
            $bayar = '';
        }
        $sorts = [
            'terbaru' => ['tgl_fak', 'desc'],
            'terlama' => ['tgl_fak', 'asc'],
            'total_besar' => ['tot_bayar', 'desc'],
            'total_kecil' => ['tot_bayar', 'asc'],
            'supplier' => ['nm_sup', 'asc'],
            'faktur' => ['no_fak', 'desc'],
            'jatuh_tempo' => ['tgl_jt', 'asc'],
        ];
        if (! isset($sorts[$sort])) {
            $sort = 'terbaru';
        }

        $query = Purchase::query()->where('kd_toko', $kd_toko);
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('no_fak', 'like', "%{$q}%")
                    ->orWhere('nm_sup', 'like', "%{$q}%")
                    ->orWhere('kd_sup', 'like', "%{$q}%");
            });
        }
        if ($from) {
            $query->whereDate('tgl_fak', '>=', $from);
        }
        if ($to) {
            $query->whereDate('tgl_fak', '<=', $to);
        }
        if ($bayar === 'HUTANG') {
            $query->where('byr_hutang', '>', 0);
        } elseif ($bayar !== '') {
            $query->where('kd_bayar', $bayar);
        }

        $summary = (clone $query)
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(tot_bayar), 0) as total, COALESCE(SUM(byr_hutang), 0) as hutang')
            ->first();

        [$column, $direction] = $sorts[$sort];
        $purchases = $query
            ->orderBy($column, $direction)
            ->orderBy('no_urut', 'desc')
            ->paginate(20)
            ->withQueryString();

        return view('admin.purchases.index', compact('purchases', 'q', 'from', 'to', 'bayar', 'sort', 'summary'));
    }

    public function create()
    {
        return view('admin.purchases.create', $this->screenData());
    }

    public function edit($id)
    {
        return view('admin.purchases.create', $this->screenData($this->purchaseForBranch($id)));
    }

    public function store(Request $request)
    {
        $request->validate([
            'no_fak' => 'required|string',
            'tgl_fak' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.kd_brg' => 'required|string',
            'items.*.jml_brg' => 'required|numeric|min:0.01',
            'items.*.hrg_beli' => 'required|numeric|min:0',
            'tgl_jt' => 'nullable|date|required_if:kd_bayar,TEMPO',
        ], [
            'tgl_jt.required_if' => 'Isi tanggal jatuh tempo.',
        ]);

        $sqlMode = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        DB::statement("SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', '')");

        DB::beginTransaction();
        try {
            $kd_toko = BranchContext::kdToko();
            $tot_beli = 0;
            $tot_disc1 = 0;
            $tot_disc2 = 0;
            $tot_ppn = 0;

            // Calculate totals
            foreach ($request->items as $item) {
                $subtotal = $item['jml_brg'] * $item['hrg_beli'];
                $disc1 = ($subtotal * ($item['disc1'] ?? 0)) / 100;
                $disc2 = $item['disc2'] ?? 0;
                $ppn = (($subtotal - $disc1 - $disc2) * ($item['ppn'] ?? 0)) / 100;
                
                $tot_beli += $subtotal;
                $tot_disc1 += $disc1;
                $tot_disc2 += $disc2;
                $tot_ppn += $ppn;
            }

            $discNota = max(0, (float) $request->input('disc_nota', 0));
            $tot_disc2 += $discNota;
            $tot_bayar = max(0, $tot_beli - $tot_disc1 - $tot_disc2 + $tot_ppn);
            $caraBayar = $request->input('kd_bayar') === 'TEMPO' ? 'TEMPO' : 'TUNAI';
            if ($caraBayar === 'TEMPO') {
                $byrTunai = min($tot_bayar, max(0, (float) $request->input('byr_tunai', 0)));
                $byrHutang = round($tot_bayar - $byrTunai, 2);
            } else {
                $byrTunai = $tot_bayar;
                $byrHutang = 0;
            }

            // Create purchase
            $purchase = Purchase::create([
                'no_fak' => $request->no_fak,
                'tgl_fak' => $request->tgl_fak,
                'kd_sup' => $request->kd_sup ?? '',
                'nm_sup' => $request->nm_sup ?? '',
                'kd_toko' => $kd_toko,
                'tot_beli' => $tot_beli,
                'disc1' => $tot_disc1,
                'disc2' => $tot_disc2,
                'ppn' => $tot_ppn,
                'tot_bayar' => $tot_bayar,
                'kd_bayar' => $caraBayar,
                'byr_tunai' => $byrTunai,
                'byr_hutang' => $byrHutang,
                'tgl_jt' => $caraBayar === 'TEMPO' ? $request->tgl_jt : null,
                'ket' => $request->ket ?? '',
            ]);

            // Create purchase items and update stock
            $lineNo = 0;
            foreach ($request->items as $item) {
                $lineNo++;
                $note = trim((string) ($item['ket'] ?? $request->ket ?? ''));
                if ($note === '') {
                    $note = 'PEMBELIAN BARANG';
                }

                PurchaseItem::create([
                    'no_fak' => $request->no_fak,
                    'tgl_fak' => $request->tgl_fak,
                    'kd_brg' => $item['kd_brg'],
                    'kd_bar' => $this->barcodeForItem($item, $kd_toko),
                    'kd_sup' => mb_substr((string) ($request->kd_sup ?? ''), 0, 30),
                    'kd_sat' => $item['kd_sat'] ?? 1,
                    'jml_brg' => $item['jml_brg'],
                    'hrg_beli' => $item['hrg_beli'],
                    'disc1' => $item['disc1'] ?? 0,
                    'disc2' => $item['disc2'] ?? 0,
                    'ppn' => $item['ppn'] ?? 0,
                    'stok_jual' => $item['jml_brg'],
                    'kd_toko' => $kd_toko,
                    'ket' => mb_substr($note, 0, 50),
                    'no_item' => $lineNo,
                    'no_item_del' => 0,
                    'ket_mut' => '',
                    'id_bag' => (int) ($item['id_bag'] ?? 0),
                    'expdate' => '0000-00-00',
                ]);

                $this->inventory->receivePurchaseLine(
                    $item['kd_brg'],
                    (float) $item['jml_brg'],
                    (int) ($item['kd_sat'] ?? 1),
                    $kd_toko,
                    $request->no_fak
                );

                $this->pricing->saveSellPrices(
                    $item['kd_brg'],
                    (float) $item['hrg_beli'],
                    isset($item['hrg_jum1']) ? (float) $item['hrg_jum1'] : null,
                    isset($item['hrg_jum2']) ? (float) $item['hrg_jum2'] : null,
                    isset($item['hrg_jum3']) ? (float) $item['hrg_jum3'] : null
                );
            }

            $this->posting->postPurchase($purchase);
            $this->recordPurchasePayment($purchase, $byrTunai, $request->tgl_fak, $kd_toko);

            DB::commit();
            $message = $byrHutang > 0
                ? 'Pembelian tersimpan. Sisa nota masuk daftar belum bayar.'
                : 'Pembelian berhasil disimpan';

            return redirect()->route('purchases.index')->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        } finally {
            DB::statement('SET SESSION sql_mode = '.DB::getPdo()->quote((string) $sqlMode));
        }
    }

    public function update(Request $request, $id)
    {
        $purchase = $this->purchaseForBranch($id);
        $request->validate([
            'no_fak' => 'required|string',
            'tgl_fak' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.kd_brg' => 'required|string',
            'items.*.jml_brg' => 'required|numeric|min:0.01',
            'items.*.hrg_beli' => 'required|numeric|min:0',
            'tgl_jt' => 'nullable|date|required_if:kd_bayar,TEMPO',
        ], [
            'tgl_jt.required_if' => 'Isi tanggal jatuh tempo.',
        ]);

        $sqlMode = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;
        DB::statement("SET SESSION sql_mode = REPLACE(REPLACE(@@SESSION.sql_mode, 'NO_ZERO_DATE', ''), 'NO_ZERO_IN_DATE', '')");
        DB::beginTransaction();
        try {
            $kd_toko = $purchase->kd_toko;
            [$tot_beli, $tot_disc1, $tot_disc2, $tot_ppn, $tot_bayar, $caraBayar, $byrTunai, $byrHutang] = $this->purchaseTotals($request);
            $paid = (float) DB::table('hutang_payments')->where('purchase_id', $purchase->no_urut)->sum('amount');
            $targetPaid = $caraBayar === 'TEMPO'
                ? min($tot_bayar, max($paid, $byrTunai))
                : $tot_bayar;
            $byrTunai = $targetPaid;
            $byrHutang = max(0, round($tot_bayar - $targetPaid, 2));

            $oldNo = $purchase->no_fak;
            $oldDate = $purchase->tgl_fak;
            $purchase->update([
                'no_fak' => $request->no_fak,
                'tgl_fak' => $request->tgl_fak,
                'kd_sup' => $request->kd_sup ?? '',
                'nm_sup' => $request->nm_sup ?? '',
                'tot_beli' => $tot_beli,
                'disc1' => $tot_disc1,
                'disc2' => $tot_disc2,
                'ppn' => $tot_ppn,
                'tot_bayar' => $tot_bayar,
                'kd_bayar' => $caraBayar,
                'byr_tunai' => $byrTunai,
                'byr_hutang' => $byrHutang,
                'tgl_jt' => $caraBayar === 'TEMPO' ? $request->tgl_jt : null,
                'ket' => $request->ket ?? '',
            ]);

            $existing = PurchaseItem::query()
                ->where('no_fak', $oldNo)
                ->where('kd_toko', $kd_toko)
                ->whereDate('tgl_fak', $oldDate)
                ->get()
                ->keyBy('no_urut');
            $kept = [];

            foreach ($request->items as $item) {
                $lineId = isset($item['no_urut']) && $item['no_urut'] !== '' ? (int) $item['no_urut'] : null;
                $note = trim((string) ($item['ket'] ?? $request->ket ?? ''));
                if ($note === '') {
                    $note = 'PEMBELIAN BARANG';
                }
                $fields = [
                    'no_fak' => $request->no_fak,
                    'tgl_fak' => $request->tgl_fak,
                    'kd_brg' => $item['kd_brg'],
                    'kd_bar' => $this->barcodeForItem($item, $kd_toko),
                    'kd_sup' => mb_substr((string) ($request->kd_sup ?? ''), 0, 30),
                    'kd_sat' => $item['kd_sat'] ?? 1,
                    'jml_brg' => $item['jml_brg'],
                    'hrg_beli' => $item['hrg_beli'],
                    'disc1' => $item['disc1'] ?? 0,
                    'disc2' => $item['disc2'] ?? 0,
                    'ppn' => $item['ppn'] ?? 0,
                    'kd_toko' => $kd_toko,
                    'ket' => mb_substr($note, 0, 50),
                ];

                if ($lineId && $existing->has($lineId)) {
                    $old = $existing[$lineId];
                    $sold = max(0, (float) $old->jml_brg - (float) $old->stok_jual);
                    $newQty = (float) $item['jml_brg'];
                    if ($newQty + 0.0001 < $sold) {
                        throw new \RuntimeException('Qty '.$old->kd_brg.' tidak boleh di bawah jumlah yang sudah terjual.');
                    }
                    if ($old->kd_brg !== $item['kd_brg']) {
                        if ($sold > 0.001) {
                            throw new \RuntimeException('Barang '.$old->kd_brg.' sudah terjual, kode barang tidak bisa diganti.');
                        }
                        $this->reversePurchaseLine($old, $kd_toko, $oldNo);
                        $fields['stok_jual'] = $newQty;
                        $old->update($fields);
                        $this->inventory->receivePurchaseLine($item['kd_brg'], $newQty, (int) ($item['kd_sat'] ?? 1), $kd_toko, $request->no_fak);
                    } else {
                        $oldBase = $this->inventory->toBaseQuantity($old->kd_brg, (float) $old->jml_brg, (int) $old->kd_sat, $kd_toko);
                        $newBase = $this->inventory->toBaseQuantity($item['kd_brg'], $newQty, (int) ($item['kd_sat'] ?? 1), $kd_toko);
                        $this->inventory->applyPurchaseDelta(
                            $item['kd_brg'],
                            $newQty - (float) $old->jml_brg,
                            $newBase - $oldBase,
                            $kd_toko,
                            $request->no_fak
                        );
                        $fields['stok_jual'] = $newQty - $sold;
                        $old->update($fields);
                    }
                    $kept[] = $lineId;
                } else {
                    $fields['stok_jual'] = $item['jml_brg'];
                    $fields['no_item'] = count($kept) + 1;
                    $fields['no_item_del'] = 0;
                    $fields['ket_mut'] = '';
                    $fields['id_bag'] = (int) ($item['id_bag'] ?? 0);
                    $fields['expdate'] = '0000-00-00';
                    PurchaseItem::create($fields);
                    $this->inventory->receivePurchaseLine(
                        $item['kd_brg'],
                        (float) $item['jml_brg'],
                        (int) ($item['kd_sat'] ?? 1),
                        $kd_toko,
                        $request->no_fak
                    );
                }

                $this->pricing->saveSellPrices(
                    $item['kd_brg'],
                    (float) $item['hrg_beli'],
                    isset($item['hrg_jum1']) ? (float) $item['hrg_jum1'] : null,
                    isset($item['hrg_jum2']) ? (float) $item['hrg_jum2'] : null,
                    isset($item['hrg_jum3']) ? (float) $item['hrg_jum3'] : null
                );
            }

            foreach ($existing as $old) {
                if (in_array($old->no_urut, $kept, true)) {
                    continue;
                }
                $sold = max(0, (float) $old->jml_brg - (float) $old->stok_jual);
                if ($sold > 0.001) {
                    throw new \RuntimeException('Barang '.$old->kd_brg.' sudah terjual, baris tidak bisa dihapus.');
                }
                $this->reversePurchaseLine($old, $kd_toko, $oldNo);
                $old->delete();
            }

            if ($targetPaid > $paid + 0.009) {
                $this->recordPurchasePayment($purchase, $targetPaid - $paid, $request->tgl_fak, $kd_toko);
            }

            $this->posting->postPurchase($purchase->fresh());

            DB::commit();

            return redirect()->route('purchases.index')->with('success', 'Pembelian diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Error: '.$e->getMessage());
        } finally {
            DB::statement('SET SESSION sql_mode = '.DB::getPdo()->quote((string) $sqlMode));
        }
    }

    protected function barcodeForItem(array $item, ?string $kdToko): string
    {
        $barcode = trim((string) ($item['kd_bar'] ?? ''));
        if ($barcode === '') {
            $barcode = (string) (Product::query()
                ->where('kd_brg', $item['kd_brg'])
                ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
                ->value('kd_bar') ?? '');
        }
        if ($barcode === '') {
            $barcode = (string) $item['kd_brg'];
        }

        return mb_substr($barcode, 0, 40);
    }

    protected function purchaseTotals(Request $request): array
    {
        $totBeli = 0;
        $totDisc1 = 0;
        $totDisc2 = 0;
        $totPpn = 0;
        foreach ($request->items as $item) {
            $subtotal = $item['jml_brg'] * $item['hrg_beli'];
            $disc1 = ($subtotal * ($item['disc1'] ?? 0)) / 100;
            $disc2 = $item['disc2'] ?? 0;
            $ppn = (($subtotal - $disc1 - $disc2) * ($item['ppn'] ?? 0)) / 100;
            $totBeli += $subtotal;
            $totDisc1 += $disc1;
            $totDisc2 += $disc2;
            $totPpn += $ppn;
        }
        $totDisc2 += max(0, (float) $request->input('disc_nota', 0));
        $totBayar = max(0, $totBeli - $totDisc1 - $totDisc2 + $totPpn);
        $caraBayar = $request->input('kd_bayar') === 'TEMPO' ? 'TEMPO' : 'TUNAI';
        if ($caraBayar === 'TEMPO') {
            $byrTunai = min($totBayar, max(0, (float) $request->input('byr_tunai', 0)));
            $byrHutang = round($totBayar - $byrTunai, 2);
        } else {
            $byrTunai = $totBayar;
            $byrHutang = 0;
        }

        return [$totBeli, $totDisc1, $totDisc2, $totPpn, $totBayar, $caraBayar, $byrTunai, $byrHutang];
    }

    protected function recordPurchasePayment(Purchase $purchase, float $amount, $date, ?string $kdToko): void
    {
        if ($amount <= 0) {
            return;
        }
        $paymentId = DB::table('hutang_payments')->insertGetId([
            'purchase_id' => $purchase->no_urut,
            'tgl_bayar' => $date,
            'amount' => $amount,
            'kd_toko' => $kdToko,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $entry = $this->posting->postPayablePayment($amount, $date, (string) $purchase->no_fak, (string) $paymentId);
        if ($entry && Schema::hasColumn('hutang_payments', 'journal_entry_id')) {
            DB::table('hutang_payments')->where('id', $paymentId)->update(['journal_entry_id' => $entry->id]);
        }
    }

    protected function reversePurchaseLine(PurchaseItem $line, string $kdToko, string $reference): void
    {
        $base = $this->inventory->toBaseQuantity($line->kd_brg, (float) $line->jml_brg, (int) $line->kd_sat, $kdToko);
        $this->inventory->applyPurchaseDelta($line->kd_brg, -1 * (float) $line->jml_brg, -1 * $base, $kdToko, $reference);
    }

    protected function purchaseForBranch($id): Purchase
    {
        $purchase = Purchase::findOrFail($id);
        abort_unless($purchase->kd_toko === BranchContext::kdToko(), 403);

        return $purchase;
    }

    protected function screenData(?Purchase $purchase = null): array
    {
        $kdToko = BranchContext::kdToko();
        $resume = $purchase ? null : $this->numbers->unfinishedPurchase($kdToko);
        $suggestedNo = $purchase?->no_fak ?? ($resume->no_fak ?? '');
        $businessDate = BranchContext::businessDate();
        $recent = Purchase::query()
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->orderByDesc('tgl_fak')
            ->orderByDesc('no_urut')
            ->limit(12)
            ->get(['no_urut', 'no_fak', 'tgl_fak', 'nm_sup', 'tot_bayar']);
        $unpaid = Purchase::query()
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->where('byr_hutang', '>', 0)
            ->orderByRaw('tgl_jt IS NULL')
            ->orderBy('tgl_jt')
            ->limit(30)
            ->get(['no_urut', 'no_fak', 'tgl_fak', 'nm_sup', 'tot_bayar', 'byr_tunai', 'byr_hutang', 'tgl_jt']);
        $editLines = $purchase ? $this->editorLines($purchase) : [];
        $discNota = $purchase
            ? max(0, (float) $purchase->disc2 - collect($editLines)->sum('disc2'))
            : 0;

        return compact('suggestedNo', 'recent', 'unpaid', 'purchase', 'editLines', 'discNota', 'resume', 'businessDate');
    }

    protected function editorLines(Purchase $purchase): array
    {
        $items = PurchaseItem::query()
            ->where('no_fak', $purchase->no_fak)
            ->where('kd_toko', $purchase->kd_toko)
            ->whereDate('tgl_fak', $purchase->tgl_fak)
            ->orderBy('no_urut')
            ->get();
        $codes = $items->pluck('kd_brg')->filter()->unique();
        $products = $codes->isEmpty()
            ? collect()
            : Product::query()
                ->where('kd_toko', $purchase->kd_toko)
                ->whereIn('kd_brg', $codes)
                ->get()
                ->keyBy('kd_brg');

        return $items->map(function (PurchaseItem $item) use ($products) {
            $product = $products->get($item->kd_brg);

            return [
                'no_urut' => $item->no_urut,
                'kd_brg' => $item->kd_brg,
                'nm_brg' => $product->nm_brg ?? $item->kd_brg,
                'kd_bar' => $item->kd_bar,
                'kd_sat' => $item->kd_sat,
                'nm_sat' => $this->unitLabel($product, (int) $item->kd_sat),
                'jml_brg' => (float) $item->jml_brg,
                'hrg_beli' => (float) $item->hrg_beli,
                'disc1' => (float) $item->disc1,
                'disc2' => (float) $item->disc2,
                'hrg_jum1' => (float) ($product->hrg_jum1 ?? 0),
                'hrg_jum2' => (float) ($product->hrg_jum2 ?? 0),
                'hrg_jum3' => (float) ($product->hrg_jum3 ?? 0),
            ];
        })->all();
    }

    protected function unitLabel(?Product $product, int $kdSat): string
    {
        if (! $product) {
            return 'pcs';
        }
        if ((int) $product->kd_kem3 === $kdSat && trim((string) $product->nm_kem3) !== '') {
            return $product->nm_kem3;
        }
        if ((int) $product->kd_kem2 === $kdSat && trim((string) $product->nm_kem2) !== '') {
            return $product->nm_kem2;
        }
        $name = trim((string) $product->nm_kem1);

        return $name !== '' && ! ctype_digit($name) ? $name : 'pcs';
    }

    public function show($id)
    {
        $purchase = Purchase::with('items.product')->findOrFail($id);

        return view('admin.purchases.show', compact('purchase'));
    }

    public function print($id)
    {
        $purchase = Purchase::with('items')->findOrFail($id);

        return view('admin.purchases.print', compact('purchase'));
    }

    public function searchSupplier(Request $request)
    {
        $keyword = $request->get('keyword', '');

        $rows = Supplier::query()
            ->when($keyword, fn ($q) => $q->where(function ($q) use ($keyword) {
                $q->where('kd_sup', 'like', "%{$keyword}%")
                    ->orWhere('nm_sup', 'like', "%{$keyword}%");
            }))
            ->orderBy('nm_sup')
            ->limit(15)
            ->get(['kd_sup', 'nm_sup', 'telp_sup']);

        return response()->json($rows);
    }

    public function searchProduct(Request $request)
    {
        return response()->json(
            $this->pricing->searchForBranch($request->get('keyword') ?: null)
        );
    }
}

