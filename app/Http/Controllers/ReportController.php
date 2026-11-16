<?php

namespace App\Http\Controllers;

use App\Services\Inventory\InventoryMovementService;
use App\Services\Reports\ReportService;
use App\Support\BranchContext;
use App\Support\Reports\ReportOutput;
use App\Support\Reports\ReportSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct(
        protected InventoryMovementService $inventory,
        protected ReportService $reports
    ) {
    }

    protected function period(Request $request): array
    {
        return [
            $request->get('from', date('Y-m-01')),
            $request->get('to', date('Y-m-d')),
        ];
    }

    protected function monthYear(Request $request): array
    {
        return [
            (int) $request->get('month', (int) date('n')),
            (int) $request->get('year', (int) date('Y')),
        ];
    }

    protected function place(): string
    {
        return session('nm_toko') ?: 'Gerai aktif';
    }

    protected function span(string $from, string $to): string
    {
        return $this->place().' · '.date('d/m/Y', strtotime($from)).' s/d '.date('d/m/Y', strtotime($to));
    }

    protected function monthLabel(int $month, int $year): string
    {
        $nama = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

        return $this->place().' · '.($nama[$month] ?? $month).' '.$year;
    }

    protected function rupiah(float $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    protected function rows(iterable $items, callable $map): array
    {
        $rows = [];
        foreach ($items as $item) {
            $rows[] = $map($item);
        }

        return $rows;
    }

    protected function finish(Request $request, ReportSheet $sheet, string $view, array $data)
    {
        if (ReportOutput::wants($request)) {
            return ReportOutput::send($request, $sheet);
        }

        return view($view, array_merge($data, ['sheet' => $sheet]));
    }

    public function index()
    {
        return view('admin.reports.index');
    }

    public function sales(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        [$from, $to] = $this->period($request);
        $base = DB::table('jual')->where('kd_toko', $kdToko)->whereBetween('tgl_jual', [$from, $to]);
        $total = (float) (clone $base)->sum('tot_bayar');
        $ordered = (clone $base)->orderByDesc('tgl_jual');
        $map = fn ($r) => [
            'no_fak' => $r->no_fak,
            'tgl_jual' => $r->tgl_jual,
            'nm_pel' => $r->nm_pel ?: 'Umum',
            'tot_bayar' => $r->tot_bayar,
        ];
        $columns = [
            ['key' => 'no_fak', 'label' => 'Faktur'],
            ['key' => 'tgl_jual', 'label' => 'Tanggal', 'date' => true],
            ['key' => 'nm_pel', 'label' => 'Pelanggan'],
            ['key' => 'tot_bayar', 'label' => 'Total', 'money' => true],
        ];

        if (ReportOutput::wants($request)) {
            return ReportOutput::send($request, new ReportSheet('Penjualan', $this->span($from, $to), $columns, $this->rows($ordered->get(), $map), ['Total' => $this->rupiah($total)]));
        }

        $rows = $ordered->paginate(30)->withQueryString();

        return $this->finish($request, new ReportSheet('Penjualan', $this->span($from, $to), $columns, $this->rows($rows, $map), ['Total' => $this->rupiah($total), 'Faktur' => (string) $rows->total()]), 'admin.reports.sales', compact('rows', 'from', 'to'));
    }

    public function salesByProduct(Request $request)
    {
        [$from, $to] = $this->period($request);
        $source = $this->reports->salesByProduct(BranchContext::kdToko(), $from, $to);
        $total = $source->sum(fn ($r) => (float) $r->revenue);
        $sheet = new ReportSheet('Penjualan per barang', $this->span($from, $to), [
            ['key' => 'kd_brg', 'label' => 'SKU'],
            ['key' => 'nm_brg', 'label' => 'Nama'],
            ['key' => 'qty', 'label' => 'Qty', 'qty' => true],
            ['key' => 'revenue', 'label' => 'Omset', 'money' => true],
        ], $this->rows($source, fn ($r) => [
            'kd_brg' => $r->kd_brg,
            'nm_brg' => $r->nm_brg,
            'qty' => $r->qty,
            'revenue' => $r->revenue,
        ]), ['Omset' => $this->rupiah($total)]);

        return $this->finish($request, $sheet, 'admin.reports.sales-by-product', compact('from', 'to'));
    }

    public function salesByCustomer(Request $request)
    {
        [$from, $to] = $this->period($request);
        $source = $this->reports->salesByCustomer(BranchContext::kdToko(), $from, $to);
        $total = $source->sum(fn ($r) => (float) $r->revenue);
        $sheet = new ReportSheet('Penjualan per pelanggan', $this->span($from, $to), [
            ['key' => 'nm_pel', 'label' => 'Pelanggan'],
            ['key' => 'kd_pel', 'label' => 'Kode'],
            ['key' => 'trx_count', 'label' => 'Transaksi', 'qty' => true],
            ['key' => 'revenue', 'label' => 'Omset', 'money' => true],
        ], $this->rows($source, fn ($r) => [
            'nm_pel' => $r->nm_pel,
            'kd_pel' => $r->kd_pel,
            'trx_count' => $r->trx_count,
            'revenue' => $r->revenue,
        ]), ['Omset' => $this->rupiah($total)]);

        return $this->finish($request, $sheet, 'admin.reports.sales-by-customer', compact('from', 'to'));
    }

    public function purchases(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        [$from, $to] = $this->period($request);
        $base = DB::table('beli')->where('kd_toko', $kdToko)->whereBetween('tgl_fak', [$from, $to]);
        $total = (float) (clone $base)->sum('tot_bayar');
        $ordered = (clone $base)->orderByDesc('tgl_fak');
        $map = fn ($r) => [
            'no_fak' => $r->no_fak,
            'tgl_fak' => $r->tgl_fak,
            'nm_sup' => $r->nm_sup ?: 'Tanpa supplier',
            'tot_bayar' => $r->tot_bayar,
        ];
        $columns = [
            ['key' => 'no_fak', 'label' => 'Faktur'],
            ['key' => 'tgl_fak', 'label' => 'Tanggal', 'date' => true],
            ['key' => 'nm_sup', 'label' => 'Supplier'],
            ['key' => 'tot_bayar', 'label' => 'Total', 'money' => true],
        ];

        if (ReportOutput::wants($request)) {
            return ReportOutput::send($request, new ReportSheet('Pembelian', $this->span($from, $to), $columns, $this->rows($ordered->get(), $map), ['Total' => $this->rupiah($total)]));
        }

        $rows = $ordered->paginate(30)->withQueryString();

        return $this->finish($request, new ReportSheet('Pembelian', $this->span($from, $to), $columns, $this->rows($rows, $map), ['Total' => $this->rupiah($total), 'Faktur' => (string) $rows->total()]), 'admin.reports.purchases', compact('rows', 'from', 'to'));
    }

    public function purchasesBySupplier(Request $request)
    {
        [$from, $to] = $this->period($request);
        $source = $this->reports->purchasesBySupplier(BranchContext::kdToko(), $from, $to);
        $total = $source->sum(fn ($r) => (float) $r->total);
        $sheet = new ReportSheet('Pembelian per supplier', $this->span($from, $to), [
            ['key' => 'nm_sup', 'label' => 'Supplier'],
            ['key' => 'trx_count', 'label' => 'Transaksi', 'qty' => true],
            ['key' => 'total', 'label' => 'Total', 'money' => true],
        ], $this->rows($source, fn ($r) => [
            'nm_sup' => $r->nm_sup,
            'trx_count' => $r->trx_count,
            'total' => $r->total,
        ]), ['Total' => $this->rupiah($total)]);

        return $this->finish($request, $sheet, 'admin.reports.purchases-by-supplier', compact('from', 'to'));
    }

    public function stock(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        $warehouseId = BranchContext::warehouseId();
        $filter = $request->get('filter', 'all');
        $labels = ['all' => 'Semua stok', 'available' => 'Ada stok', 'low' => 'Stok menipis', 'empty' => 'Stok habis'];
        $subtitle = $this->place().' · '.($labels[$filter] ?? 'Semua stok');
        $columns = [
            ['key' => 'kd_brg', 'label' => 'SKU'],
            ['key' => 'kd_bar', 'label' => 'Barcode'],
            ['key' => 'nm_brg', 'label' => 'Nama'],
            ['key' => 'stok', 'label' => 'Stok', 'stock' => true],
        ];

        if ($warehouseId) {
            $query = $this->inventory->stockReportQuery($warehouseId, $filter);
            $map = function ($balance) {
                $product = $balance->product;

                return [
                    'kd_brg' => $product?->sku,
                    'kd_bar' => $product?->barcode,
                    'nm_brg' => $product?->name,
                    'stok' => (float) $balance->qty_on_hand,
                ];
            };
            if (ReportOutput::wants($request)) {
                return ReportOutput::send($request, new ReportSheet('Stok barang', $subtitle, $columns, $this->rows($query->get(), $map)));
            }
            $rows = $query->paginate(40)->withQueryString();
            $sheet = new ReportSheet('Stok barang', $subtitle, $columns, $this->rows($rows, $map), ['Barang' => (string) $rows->total()]);

            return view('admin.reports.stock', compact('rows', 'filter', 'sheet'));
        }

        $query = DB::table('beli_brg')
            ->join('mas_brg', function ($j) use ($kdToko) {
                $j->on('beli_brg.kd_brg', '=', 'mas_brg.kd_brg')->where('mas_brg.kd_toko', '=', $kdToko);
            })
            ->where('beli_brg.kd_toko', $kdToko)
            ->select('beli_brg.kd_brg', 'mas_brg.nm_brg', 'mas_brg.kd_bar', DB::raw('SUM(beli_brg.stok_jual) as stok'))
            ->groupBy('beli_brg.kd_brg', 'mas_brg.nm_brg', 'mas_brg.kd_bar');

        if ($filter === 'empty') {
            $query->havingRaw('SUM(beli_brg.stok_jual) = 0');
        } elseif ($filter === 'low') {
            $query->havingRaw('SUM(beli_brg.stok_jual) > 0 AND SUM(beli_brg.stok_jual) <= 5');
        } elseif ($filter === 'available') {
            $query->havingRaw('SUM(beli_brg.stok_jual) > 0');
        }

        $ordered = $query->orderBy('mas_brg.nm_brg');
        $map = fn ($r) => [
            'kd_brg' => $r->kd_brg,
            'kd_bar' => $r->kd_bar,
            'nm_brg' => $r->nm_brg,
            'stok' => $r->stok,
        ];
        if (ReportOutput::wants($request)) {
            return ReportOutput::send($request, new ReportSheet('Stok barang', $subtitle, $columns, $this->rows($ordered->get(), $map)));
        }
        $rows = $ordered->paginate(40)->withQueryString();

        return view('admin.reports.stock', [
            'rows' => $rows,
            'filter' => $filter,
            'sheet' => new ReportSheet('Stok barang', $subtitle, $columns, $this->rows($rows, $map), ['Barang' => (string) $rows->total()]),
        ]);
    }

    public function salesProfit(Request $request)
    {
        [$from, $to] = $this->period($request);
        $data = $this->reports->salesProfitLines(BranchContext::kdToko(), $from, $to);
        $sheet = new ReportSheet(
            'Laba penjualan',
            $this->span($from, $to).($data['useLaba'] ? '' : ' · estimasi harga beli terakhir'),
            [
                ['key' => 'no_fak', 'label' => 'Faktur'],
                ['key' => 'tgl_jual', 'label' => 'Tanggal', 'date' => true],
                ['key' => 'nm_brg', 'label' => 'Barang'],
                ['key' => 'revenue', 'label' => 'Omset', 'money' => true],
                ['key' => 'profit', 'label' => 'Laba', 'money' => true],
            ],
            $this->rows($data['rows'], fn ($r) => [
                'no_fak' => $r->no_fak,
                'tgl_jual' => $r->tgl_jual,
                'nm_brg' => $r->nm_brg ?: $r->kd_brg,
                'revenue' => $r->revenue,
                'profit' => $r->profit,
            ]),
            [
                'Omset' => $this->rupiah((float) $data['totalRevenue']),
                'Laba' => $this->rupiah((float) $data['totalProfit']),
            ]
        );

        return $this->finish($request, $sheet, 'admin.reports.sales-profit', array_merge($data, compact('from', 'to')));
    }

    public function salesProfitPrint(Request $request)
    {
        $request->merge(['cetak' => 1]);

        return $this->salesProfit($request);
    }

    public function receivableProfit(Request $request)
    {
        [$from, $to] = $this->period($request);
        $source = $this->reports->receivableCollections(BranchContext::kdToko(), $from, $to);
        $total = $source->sum(fn ($r) => (float) $r->amount);
        $sheet = new ReportSheet('Pembayaran piutang', $this->span($from, $to), [
            ['key' => 'tgl_bayar', 'label' => 'Tanggal bayar', 'date' => true],
            ['key' => 'no_fak', 'label' => 'Faktur'],
            ['key' => 'tgl_jual', 'label' => 'Tanggal jual', 'date' => true],
            ['key' => 'amount', 'label' => 'Nominal', 'money' => true],
        ], $this->rows($source, fn ($r) => [
            'tgl_bayar' => $r->tgl_bayar,
            'no_fak' => $r->no_fak,
            'tgl_jual' => $r->tgl_jual,
            'amount' => $r->amount,
        ]), ['Terkumpul' => $this->rupiah($total)], [], 'Belum ada pembayaran piutang pada periode ini.');

        return $this->finish($request, $sheet, 'admin.reports.receivable-collections', compact('from', 'to'));
    }

    public function salesBySection(Request $request)
    {
        [$from, $to] = $this->period($request);
        $source = $this->reports->salesByBagian(BranchContext::kdToko(), $from, $to);
        $columns = [
            ['key' => 'nm_bag', 'label' => 'Bagian'],
            ['key' => 'qty', 'label' => 'Qty', 'qty' => true],
            ['key' => 'revenue', 'label' => 'Omset', 'money' => true],
        ];
        if ($source === null) {
            $sheet = new ReportSheet('Penjualan per bagian', $this->span($from, $to), $columns, [], [], 'Bagian barang belum diisi, jadi penjualan belum bisa dikelompokkan.');
        } else {
            $sheet = new ReportSheet('Penjualan per bagian', $this->span($from, $to), $columns, $this->rows($source, fn ($r) => [
                'nm_bag' => $r->nm_bag,
                'qty' => $r->qty,
                'revenue' => $r->revenue,
            ]), ['Omset' => $this->rupiah($source->sum(fn ($r) => (float) $r->revenue))]);
        }

        return $this->finish($request, $sheet, 'admin.reports.sales-by-section', compact('from', 'to', 'source'));
    }

    public function salesByPayment(Request $request)
    {
        [$from, $to] = $this->period($request);
        $source = $this->reports->salesByPayment(BranchContext::kdToko(), $from, $to);
        $labels = ['TUNAI' => 'Tunai', 'TEMPO' => 'Tempo', 'QRIS' => 'QRIS', 'COD' => 'COD', 'BNI' => 'BNI'];
        $sheet = new ReportSheet('Penjualan per pembayaran', $this->span($from, $to), [
            ['key' => 'cara', 'label' => 'Pembayaran'],
            ['key' => 'trx_count', 'label' => 'Faktur', 'qty' => true],
            ['key' => 'revenue', 'label' => 'Omset', 'money' => true],
            ['key' => 'sisa', 'label' => 'Sisa tempo', 'money' => true],
        ], $this->rows($source, fn ($r) => [
            'cara' => $labels[$r->cara] ?? $r->cara,
            'trx_count' => $r->trx_count,
            'revenue' => $r->revenue,
            'sisa' => $r->sisa,
        ]), [
            'Faktur' => number_format($source->sum(fn ($r) => (int) $r->trx_count), 0, ',', '.'),
            'Omset' => $this->rupiah($source->sum(fn ($r) => (float) $r->revenue)),
        ], [], 'Belum ada penjualan pada periode ini.');

        return $this->finish($request, $sheet, 'admin.reports.sales-by-payment', compact('from', 'to'));
    }

    public function salesByBrand(Request $request)
    {
        [$from, $to] = $this->period($request);
        $source = $this->reports->salesByBrand(BranchContext::kdToko(), $from, $to);
        $sheet = new ReportSheet('Penjualan per brand', $this->span($from, $to), [
            ['key' => 'brand', 'label' => 'Brand'],
            ['key' => 'qty', 'label' => 'Qty', 'qty' => true],
            ['key' => 'revenue', 'label' => 'Omset', 'money' => true],
        ], $this->rows($source, fn ($r) => [
            'brand' => $r->brand,
            'qty' => $r->qty,
            'revenue' => $r->revenue,
        ]), [
            'Qty' => number_format($source->sum(fn ($r) => (float) $r->qty), 0, ',', '.'),
            'Omset' => $this->rupiah($source->sum(fn ($r) => (float) $r->revenue)),
        ], [], 'Belum ada penjualan pada periode ini.');

        return $this->finish($request, $sheet, 'admin.reports.sales-by-brand', compact('from', 'to'));
    }

    public function promoSummary(Request $request)
    {
        [$from, $to] = $this->period($request);
        $source = $this->reports->promoSummary(BranchContext::kdToko(), $from, $to);
        $sheet = new ReportSheet('Diskon dan promo', $this->span($from, $to), [
            ['key' => 'label', 'label' => 'Keterangan'],
            ['key' => 'trx_count', 'label' => 'Transaksi', 'qty' => true],
            ['key' => 'total_disc', 'label' => 'Total diskon', 'money' => true],
        ], $this->rows($source, fn ($r) => [
            'label' => $r->label,
            'trx_count' => $r->trx_count,
            'total_disc' => $r->total_disc ?? 0,
        ]), ['Diskon' => $this->rupiah($source->sum(fn ($r) => (float) ($r->total_disc ?? 0)))]);

        return $this->finish($request, $sheet, 'admin.reports.promo-summary', compact('from', 'to'));
    }

    public function arAp(Request $request)
    {
        $snapshot = $this->reports->arApSnapshot(BranchContext::kdToko());
        $sheet = new ReportSheet('Hutang dan piutang', $this->place().' · saldo yang masih terbuka', [], [], [
            'Piutang' => $this->rupiah((float) $snapshot['ar_total']).' · '.$snapshot['ar_count'].' faktur',
            'Hutang' => $this->rupiah((float) $snapshot['ap_total']),
        ], [
            [
                'title' => 'Piutang',
                'columns' => [
                    ['key' => 'no_fak', 'label' => 'Faktur'],
                    ['key' => 'tgl_jual', 'label' => 'Tanggal', 'date' => true],
                    ['key' => 'nm_pel', 'label' => 'Pelanggan'],
                    ['key' => 'byr_hutang', 'label' => 'Sisa', 'money' => true],
                ],
                'rows' => $this->rows($snapshot['ar_rows'], fn ($r) => [
                    'no_fak' => $r->no_fak,
                    'tgl_jual' => $r->tgl_jual,
                    'nm_pel' => $r->nm_pel ?: 'Umum',
                    'byr_hutang' => $r->byr_hutang,
                ]),
            ],
            [
                'title' => 'Hutang',
                'columns' => [
                    ['key' => 'no_fak', 'label' => 'Faktur'],
                    ['key' => 'nm_sup', 'label' => 'Supplier'],
                    ['key' => 'tgl_jt', 'label' => 'Jatuh tempo', 'date' => true],
                    ['key' => 'byr_hutang', 'label' => 'Sisa', 'money' => true],
                ],
                'rows' => $this->rows($snapshot['ap_rows'], fn ($r) => [
                    'no_fak' => $r->no_fak,
                    'nm_sup' => $r->nm_sup,
                    'tgl_jt' => $r->tgl_jt,
                    'byr_hutang' => $r->byr_hutang,
                ]),
            ],
        ]);

        return $this->finish($request, $sheet, 'admin.reports.ar-ap', []);
    }

    public function arApPrint()
    {
        return $this->arAp(request()->merge(['cetak' => 1]));
    }

    public function activity(Request $request)
    {
        [$from, $to] = $this->period($request);
        $source = $this->reports->activityLog(BranchContext::kdToko(), $from, $to);
        $sheet = new ReportSheet('Mutasi, opname, dan retur', $this->span($from, $to), [
            ['key' => 'tgl', 'label' => 'Tanggal', 'date' => true],
            ['key' => 'type', 'label' => 'Jenis'],
            ['key' => 'ref', 'label' => 'Referensi'],
            ['key' => 'qty', 'label' => 'Nilai / qty', 'qty' => true],
        ], $this->rows($source, fn ($r) => [
            'tgl' => $r->tgl,
            'type' => $r->type,
            'ref' => $r->ref,
            'qty' => $r->qty,
        ]));

        return $this->finish($request, $sheet, 'admin.reports.activity', compact('from', 'to'));
    }

    public function activityPrint(Request $request)
    {
        $request->merge(['cetak' => 1]);

        return $this->activity($request);
    }

    public function recap(Request $request)
    {
        [$month, $year] = $this->monthYear($request);
        $recap = $this->reports->monthlyRecap(BranchContext::kdToko(), $month, $year);
        $sheet = new ReportSheet('Rekapitulasi bulanan', $this->monthLabel($month, $year), [
            ['key' => 'nama', 'label' => 'Keterangan'],
            ['key' => 'nilai', 'label' => 'Nilai', 'money' => true],
        ], [
            ['nama' => 'Omset penjualan', 'nilai' => $recap['omset']],
            ['nama' => 'Pembelian', 'nilai' => $recap['beli']],
            ['nama' => 'Piutang penjualan kredit', 'nilai' => $recap['piutang_period']],
            ['nama' => 'Biaya operasional', 'nilai' => $recap['biaya']],
            ['nama' => 'Laba kotor estimasi', 'nilai' => $recap['laba_kotor_est']],
        ], ['Laba kotor estimasi' => $this->rupiah((float) $recap['laba_kotor_est'])]);

        return $this->finish($request, $sheet, 'admin.reports.recap', array_merge($recap, compact('month', 'year')));
    }

    public function recapPrint(Request $request)
    {
        $request->merge(['cetak' => 1]);

        return $this->recap($request);
    }

    public function inventoryMonthly(Request $request)
    {
        [$month, $year] = $this->monthYear($request);
        $source = $this->reports->monthlyPurchasesByProduct(BranchContext::kdToko(), $month, $year);
        $total = $source->sum(fn ($r) => (float) $r->value_in);
        $sheet = new ReportSheet('Barang masuk', $this->monthLabel($month, $year), [
            ['key' => 'kd_brg', 'label' => 'SKU'],
            ['key' => 'qty_in', 'label' => 'Qty masuk', 'qty' => true],
            ['key' => 'value_in', 'label' => 'Nilai', 'money' => true],
        ], $this->rows($source, fn ($r) => [
            'kd_brg' => $r->kd_brg,
            'qty_in' => $r->qty_in,
            'value_in' => $r->value_in,
        ]), ['Nilai masuk' => $this->rupiah($total)]);

        return $this->finish($request, $sheet, 'admin.reports.inventory-monthly', compact('month', 'year'));
    }

    public function memberSales(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        [$from, $to] = $this->period($request);
        $base = DB::table('jual')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->whereBetween('tgl_jual', [$from, $to])
            ->whereNotNull('kd_member')
            ->where('kd_member', '!=', '');
        $total = (float) (clone $base)->sum('tot_bayar');
        $ordered = (clone $base)->orderByDesc('tgl_jual');
        $map = fn ($r) => [
            'no_fak' => $r->no_fak,
            'tgl_jual' => $r->tgl_jual,
            'member' => $r->nm_member ?: $r->kd_member,
            'poin_earned' => $r->poin_earned ?? 0,
            'poin_redeem' => $r->poin_redeem ?? 0,
            'tot_bayar' => $r->tot_bayar,
        ];
        $columns = [
            ['key' => 'no_fak', 'label' => 'Faktur'],
            ['key' => 'tgl_jual', 'label' => 'Tanggal', 'date' => true],
            ['key' => 'member', 'label' => 'Member'],
            ['key' => 'poin_earned', 'label' => 'Poin masuk', 'qty' => true],
            ['key' => 'poin_redeem', 'label' => 'Poin keluar', 'qty' => true],
            ['key' => 'tot_bayar', 'label' => 'Total', 'money' => true],
        ];

        if (ReportOutput::wants($request)) {
            return ReportOutput::send($request, new ReportSheet('Transaksi member', $this->span($from, $to), $columns, $this->rows($ordered->get(), $map), ['Total' => $this->rupiah($total)]));
        }

        $rows = $ordered->paginate(30)->withQueryString();

        return $this->finish($request, new ReportSheet('Transaksi member', $this->span($from, $to), $columns, $this->rows($rows, $map), ['Total' => $this->rupiah($total)]), 'admin.reports.member-sales', compact('rows', 'from', 'to'));
    }

    public function crewSales(Request $request)
    {
        $kdToko = BranchContext::kdToko();
        [$from, $to] = $this->period($request);
        $base = DB::table('jual')
            ->when($kdToko, fn ($q) => $q->where('kd_toko', $kdToko))
            ->whereBetween('tgl_jual', [$from, $to])
            ->whereNotNull('kd_crew')
            ->where('kd_crew', '!=', '');
        $total = (float) (clone $base)->sum('tot_bayar');
        $ordered = (clone $base)->orderByDesc('tgl_jual');
        $map = fn ($r) => [
            'no_fak' => $r->no_fak,
            'tgl_jual' => $r->tgl_jual,
            'crew' => $r->nm_crew ?: $r->kd_crew,
            'tot_bayar' => $r->tot_bayar,
        ];
        $columns = [
            ['key' => 'no_fak', 'label' => 'Faktur'],
            ['key' => 'tgl_jual', 'label' => 'Tanggal', 'date' => true],
            ['key' => 'crew', 'label' => 'Crew'],
            ['key' => 'tot_bayar', 'label' => 'Total', 'money' => true],
        ];

        if (ReportOutput::wants($request)) {
            return ReportOutput::send($request, new ReportSheet('Transaksi crew', $this->span($from, $to), $columns, $this->rows($ordered->get(), $map), ['Total' => $this->rupiah($total)]));
        }

        $rows = $ordered->paginate(30)->withQueryString();

        return $this->finish($request, new ReportSheet('Transaksi crew', $this->span($from, $to), $columns, $this->rows($rows, $map), ['Total' => $this->rupiah($total)]), 'admin.reports.crew-sales', compact('rows', 'from', 'to'));
    }
}
