<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Paket;
use App\Models\PaketLine;
use App\Models\SaleDraft;
use App\Services\Catalog\BranchPricingService;
use App\Services\Finance\PostingService;
use App\Services\Inventory\InventoryMovementService;
use App\Models\Crew;
use App\Models\Member;
use App\Services\Sales\DiscountCalculatorService;
use App\Services\Retail\DocumentNumberService;
use App\Services\Sales\MemberPointService;
use App\Services\Sales\ThermalReceiptService;
use App\Support\BranchContext;
use App\Support\StaffAccess;
use Illuminate\Support\Facades\DB;

class SaleController extends Controller
{
    public function __construct(
        protected BranchPricingService $pricing,
        protected PostingService $posting,
        protected InventoryMovementService $inventory,
        protected DiscountCalculatorService $discounts,
        protected MemberPointService $memberPoints,
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
        $bayarOptions = ['TUNAI', 'TEMPO', 'COD', 'QRIS', 'BNI', 'PIUTANG'];
        if (! in_array($bayar, $bayarOptions, true)) {
            $bayar = '';
        }
        $sorts = [
            'terbaru' => ['tgl_jual', 'desc'],
            'terlama' => ['tgl_jual', 'asc'],
            'total_besar' => ['tot_bayar', 'desc'],
            'total_kecil' => ['tot_bayar', 'asc'],
            'pelanggan' => ['nm_pel', 'asc'],
            'faktur' => ['no_fak', 'desc'],
        ];
        if (! isset($sorts[$sort])) {
            $sort = 'terbaru';
        }

        $query = Sale::query()->where('kd_toko', $kd_toko);
        if ($q !== '') {
            $query->where(function ($inner) use ($q) {
                $inner->where('no_fak', 'like', "%{$q}%")
                    ->orWhere('nm_pel', 'like', "%{$q}%")
                    ->orWhere('kd_pel', 'like', "%{$q}%")
                    ->orWhere('nm_member', 'like', "%{$q}%")
                    ->orWhere('kd_member', 'like', "%{$q}%");
            });
        }
        if ($from) {
            $query->whereDate('tgl_jual', '>=', $from);
        }
        if ($to) {
            $query->whereDate('tgl_jual', '<=', $to);
        }
        if ($bayar === 'PIUTANG') {
            $query->where('byr_hutang', '>', 0);
        } elseif ($bayar !== '') {
            $query->where('kd_bayar', $bayar);
        }

        $summary = (clone $query)
            ->selectRaw('COUNT(*) as jumlah, COALESCE(SUM(tot_bayar), 0) as total, COALESCE(SUM(byr_hutang), 0) as piutang')
            ->first();

        [$column, $direction] = $sorts[$sort];
        $sales = $query
            ->orderBy($column, $direction)
            ->orderBy('no_urut', 'desc')
            ->paginate(20)
            ->withQueryString();

        $settlementToday = app(\App\Services\Retail\DailySettlementService::class)
            ->today(\App\Support\BranchContext::branchId(), date('Y-m-d'));

        return view('admin.sales.index', compact('sales', 'q', 'from', 'to', 'bayar', 'sort', 'summary', 'settlementToday'));
    }

    public function create(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $pakets = Paket::when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->orderBy('nm_paket')
            ->get(['no_urut', 'nm_paket']);

        $draft = null;
        if ($request->filled('draft_id')) {
            $draft = SaleDraft::find($request->draft_id);
            if ($draft && $kdToko && $draft->kd_toko !== $kdToko) {
                $draft = null;
            }
        }

        $businessDate = BranchContext::businessDate();
        $suggestedNo = $draft?->no_fak
            ?? $this->numbers->suggestSale($kdToko, (int) auth()->id());
        $pendingDrafts = SaleDraft::query()
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get();

        return view('admin.sales.create', compact('pakets', 'draft', 'businessDate', 'suggestedNo', 'pendingDrafts'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'no_fak' => 'required|string',
            'tgl_jual' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.kd_brg' => 'required|string',
            'items.*.jml_brg' => 'required|numeric|min:0.01',
            'items.*.hrg_jual' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $kd_toko = BranchContext::kdToko();
            if (Sale::query()->where('kd_toko', $kd_toko)->where('no_fak', $request->no_fak)->exists()) {
                throw new \InvalidArgumentException('Nomor nota sudah dipakai di gerai ini.');
            }
            $this->inventory->assertSaleQuantities($request->items, $kd_toko);
            $tot_jual = 0;
            $lineDisc = 0;
            $tot_ppn = 0;
            $caraBayar = in_array($request->input('kd_bayar'), ['TUNAI', 'TEMPO', 'COD', 'QRIS', 'BNI'], true)
                ? $request->input('kd_bayar')
                : 'TUNAI';
            $processedItems = [];

            foreach ($request->items as $item) {
                $qty = (float) $item['jml_brg'];
                $price = (float) $item['hrg_jual'];
                $subtotal = $qty * $price;
                $calc = $this->discounts->forLine(
                    (string) $item['kd_brg'],
                    (int) ($item['kd_sat'] ?? 1),
                    $qty,
                    $price,
                    $request->tgl_jual,
                    $caraBayar
                );
                $disc = max(0, (float) $calc['discount']);
                $ppn = $item['ppn'] ?? 0;

                $tot_jual += $subtotal;
                $lineDisc += $disc;
                $tot_ppn += ($subtotal - $disc) * ($ppn / 100);
                $item['disc'] = $disc;
                $processedItems[] = $item;
            }

            $belanja = max(0, $tot_jual - $lineDisc);
            $discNota = max(0, (float) $request->input('disc_nota', 0));
            $voucher = max(0, (float) $request->input('voucher', 0));
            $voucherMember = ($request->filled('kd_member') && $belanja >= 300000)
                ? (float) floor($belanja * 0.01)
                : 0;

            $poinRedeem = (float) ($request->poin_redeem ?? 0);
            if ($poinRedeem > 0 && $request->filled('kd_member')) {
                $member = Member::where('kd_member', $request->kd_member)
                    ->when($kd_toko, fn ($q) => $q->where('kd_toko', $kd_toko))
                    ->first();
                if (! $member || (float) $member->poin < $poinRedeem) {
                    throw new \InvalidArgumentException('Poin member tidak cukup untuk ditukar.');
                }
            }
            $redeemDisc = $this->memberPoints->redeemValueRupiah($poinRedeem);
            $extraDisc = $discNota + $voucher + $voucherMember + $redeemDisc;
            if ($extraDisc - $belanja > 0.01) {
                throw new \InvalidArgumentException('Diskon dan voucher melebihi total belanja.');
            }
            $tot_disc = $lineDisc + $extraDisc;

            $tot_bayar = max(0, $tot_jual - $tot_disc + $tot_ppn);
            $diterima = max(0, (float) $request->input('diterima', 0));
            if ($caraBayar === 'TEMPO') {
                $byrTunai = min($diterima, $tot_bayar);
                $byrHutang = $tot_bayar - $byrTunai;
            } else {
                if ($diterima + 0.009 < $tot_bayar) {
                    throw new \InvalidArgumentException('Pembayaran kurang dari total nota.');
                }
                $byrTunai = $tot_bayar;
                $byrHutang = 0;
            }
            $poinEarned = $request->filled('kd_member')
                ? $this->memberPoints->pointsEarnedForTotal($tot_bayar)
                : 0;

            // Create sale
            $sale = Sale::create([
                'no_fak' => $request->no_fak,
                'tgl_jual' => $request->tgl_jual,
                'kd_pel' => $request->kd_pel ?? '',
                'nm_pel' => $request->nm_pel ?? '',
                'kd_member' => $request->kd_member ?? '',
                'nm_member' => $request->nm_member ?? '',
                'kd_crew' => $request->kd_crew ?? '',
                'nm_crew' => $request->nm_crew ?? '',
                'poin_earned' => $poinEarned,
                'poin_redeem' => $poinRedeem,
                'kd_toko' => $kd_toko,
                'tot_jual' => $tot_jual,
                'disc' => $tot_disc,
                'disc_nota' => $discNota,
                'voucher' => $voucher,
                'voucher_member' => $voucherMember,
                'ppn' => $tot_ppn,
                'tot_bayar' => $tot_bayar,
                'kd_bayar' => $caraBayar,
                'diterima' => $diterima,
                'byr_tunai' => $byrTunai,
                'byr_hutang' => $byrHutang,
                'ket' => $request->ket ?? '',
            ]);

            // Create sale items and update stock
            foreach ($processedItems as $item) {
                SaleItem::create([
                    'no_fak' => $request->no_fak,
                    'tgl_jual' => $request->tgl_jual,
                    'kd_brg' => $item['kd_brg'],
                    'nm_brg' => $item['nm_brg'] ?? '',
                    'kd_sat' => $item['kd_sat'] ?? 1,
                    'nm_sat' => $item['nm_sat'] ?? 'pcs',
                    'jml_brg' => $item['jml_brg'],
                    'hrg_jual' => $item['hrg_jual'],
                    'disc' => $item['disc'] ?? 0,
                    'ppn' => $item['ppn'] ?? 0,
                    'tot_jual' => ($item['jml_brg'] * $item['hrg_jual']) - ($item['disc'] ?? 0),
                    'kd_toko' => $kd_toko,
                ]);

                $this->inventory->issueForSaleLine(
                    $item['kd_brg'],
                    (float) $item['jml_brg'],
                    (int) ($item['kd_sat'] ?? 1),
                    $kd_toko,
                    $request->no_fak
                );
            }

            $this->posting->postSale($sale);

            if ($request->filled('kd_member')) {
                $this->memberPoints->applySalePoints(
                    $request->kd_member,
                    $request->no_fak,
                    $request->tgl_jual,
                    $poinEarned,
                    $poinRedeem,
                    $kd_toko
                );
            }

            SaleDraft::where('kd_toko', $kd_toko)->where('no_fak', $request->no_fak)->delete();
            $this->numbers->consumeSale($kd_toko, (string) $request->no_fak, (int) auth()->id());

            DB::commit();
            if ($request->boolean('cetak')) {
                return redirect()->route('sales.print', $sale->no_urut);
            }

            return redirect()->route('sales.index')->with('success', 'Penjualan berhasil disimpan');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $sale = Sale::with('items')->findOrFail($id);

        return view('admin.sales.show', compact('sale'));
    }

    public function print(Request $request, $id)
    {
        $sale = Sale::with('items')->findOrFail($id);
        $format = $request->get('format', 'default');

        return view('admin.sales.print', compact('sale', 'format'));
    }

    public function thermal($id, ThermalReceiptService $thermal)
    {
        $sale = Sale::with('items')->findOrFail($id);
        $kdToko = BranchContext::kdToko();
        if ($kdToko && $sale->kd_toko !== $kdToko) {
            abort(403);
        }

        return response()->json(['ok' => $thermal->printSale($sale)]);
    }

    public function searchMember(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $keyword = $request->get('keyword', '');

        $rows = Member::query()
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->where(function ($q) use ($keyword) {
                $q->where('kd_member', 'like', "%{$keyword}%")
                    ->orWhere('nm_member', 'like', "%{$keyword}%");
            })
            ->limit(15)
            ->get(['kd_member', 'nm_member', 'poin']);

        return response()->json($rows);
    }

    public function searchCrew(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $keyword = $request->get('keyword', '');

        $rows = Crew::query()
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->where('aktif', true)
            ->where(function ($q) use ($keyword) {
                $q->where('kd_crew', 'like', "%{$keyword}%")
                    ->orWhere('nm_crew', 'like', "%{$keyword}%");
            })
            ->limit(15)
            ->get(['kd_crew', 'nm_crew']);

        return response()->json($rows);
    }

    public function searchCustomer(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $keyword = $request->get('keyword', '');

        $customersQuery = DB::table('pelanggan');
        if ($kdToko && \App\Support\LegacyMasterSchema::hasColumn('pelanggan', 'kd_toko')) {
            $customersQuery->where(function ($q) use ($kdToko) {
                $q->where('kd_toko', $kdToko)->orWhereNull('kd_toko');
            });
        }
        $customers = $customersQuery->where(function ($q) use ($keyword) {
                $q->where('kd_pel', 'like', "%{$keyword}%")
                    ->orWhere('nm_pel', 'like', "%{$keyword}%");
            })
            ->limit(15)
            ->get();

        return response()->json($customers);
    }

    public function searchProduct(Request $request)
    {
        $keyword = $request->get('keyword', '');

        $rows = $this->pricing->searchForBranch($keyword ?: null, null, 20, ! $request->boolean('any_stock'));
        if (! StaffAccess::canSeeCost($request->user())) {
            $rows = collect($rows)->map(function ($row) {
                if (is_array($row)) {
                    unset($row['hrg_beli']);
                } elseif (is_object($row)) {
                    unset($row->hrg_beli);
                }

                return $row;
            })->all();
        }

        return response()->json($rows);
    }

    public function calculateDiscount(Request $request)
    {
        $result = $this->discounts->forLine(
            (string) $request->get('kd_brg', ''),
            (int) $request->get('kd_sat', 1),
            (float) $request->get('qty', 1),
            (float) $request->get('price', 0),
            $request->get('tgl_jual'),
            $request->get('kd_bayar')
        );

        return response()->json($result);
    }

    public function loadPaket(Paket $paket)
    {
        $kdToko = BranchContext::kdToko();
        if ($kdToko && $paket->kd_toko !== $kdToko) {
            abort(403);
        }

        $lines = PaketLine::where('kd_paket', $paket->no_urut)->get();
        $items = $lines->map(function (PaketLine $line) {
            $unit = (int) $line->kd_sat;
            $price = $this->pricing->resolveSellPrice($line->kd_brg, $unit);
            $found = $this->pricing->searchForBranch($line->kd_brg, null, 1);

            $product = $found[0] ?? [];
            $unitName = 'pcs';
            if ($unit === (int) ($product['kd_kem2'] ?? 0) && ! empty($product['nm_kem2'])) {
                $unitName = $product['nm_kem2'];
            } elseif ($unit === (int) ($product['kd_kem3'] ?? 0) && ! empty($product['nm_kem3'])) {
                $unitName = $product['nm_kem3'];
            } elseif (! empty($product['nm_kem1'])) {
                $unitName = $product['nm_kem1'];
            }

            return [
                'kd_brg' => $line->kd_brg,
                'nm_brg' => $product['nm_brg'] ?? $line->kd_brg,
                'jml_brg' => (float) $line->qty_brg,
                'kd_sat' => $unit,
                'nm_sat' => $unitName,
                'hrg_jual' => $price,
                'kd_kem1' => $product['kd_kem1'] ?? $unit,
                'nm_kem1' => $product['nm_kem1'] ?? $unitName,
                'kd_kem2' => $product['kd_kem2'] ?? null,
                'nm_kem2' => $product['nm_kem2'] ?? null,
                'kd_kem3' => $product['kd_kem3'] ?? null,
                'nm_kem3' => $product['nm_kem3'] ?? null,
                'hrg_jum1' => $product['hrg_jum1'] ?? $price,
                'hrg_jum2' => $product['hrg_jum2'] ?? 0,
                'hrg_jum3' => $product['hrg_jum3'] ?? 0,
            ];
        });

        return response()->json($items);
    }
}

