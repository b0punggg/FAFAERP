<?php

namespace App\Http\Controllers\Utility;

use App\Http\Controllers\Controller;
use App\Services\Utility\DataIntegrityService;
use App\Services\Utility\DeadStockService;
use App\Support\BranchContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MaintenanceController extends Controller
{
    public function __construct(
        protected DeadStockService $deadStock,
        protected DataIntegrityService $integrity
    ) {
    }

    public function index()
    {
        return view('admin.utility.index', [
            'businessDate' => BranchContext::businessDate(),
        ]);
    }

    public function updateBusinessDate(Request $request)
    {
        $data = $request->validate(['business_date' => 'required|date']);
        BranchContext::setBusinessDate($data['business_date']);

        return back()->with('success', 'Tanggal kerja: '.$data['business_date']);
    }

    public function backup(): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        $exit = Artisan::call('erp:backup-database');
        if ($exit !== 0) {
            return back()->with('error', 'Backup gagal — pastikan mysqldump tersedia (Laragon MySQL bin).');
        }

        $files = collect(Storage::disk('local')->files('backups'))
            ->sortDesc()
            ->first();

        if (! $files) {
            return back()->with('error', 'File backup tidak ditemukan.');
        }

        return Storage::disk('local')->download($files);
    }

    public function barcode(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $branchId = BranchContext::branchId();
        $warehouseId = BranchContext::warehouseId();
        $picks = $this->barcodePicks();
        $products = null;

        if ($branchId) {
            $query = DB::table('products as p')
                ->join('branch_product_prices as bp', function ($join) use ($branchId) {
                    $join->on('bp.product_id', '=', 'p.id')->where('bp.branch_id', '=', $branchId);
                })
                ->when($q !== '', function ($query) use ($q) {
                    $query->where(function ($inner) use ($q) {
                        $inner->where('p.sku', 'like', "%{$q}%")
                            ->orWhere('p.barcode', 'like', "%{$q}%")
                            ->orWhere('p.name', 'like', "%{$q}%");
                    });
                })
                ->orderBy('p.name');

            if ($warehouseId) {
                $query->leftJoin('inventory_balances as ib', function ($join) use ($warehouseId) {
                    $join->on('ib.product_id', '=', 'p.id')->where('ib.warehouse_id', '=', $warehouseId);
                });
            }

            $products = $query->select([
                'p.sku',
                'p.barcode',
                'p.name',
                'p.jum_kem1',
                'p.nm_kem1',
                'p.jum_kem2',
                'p.nm_kem2',
                'p.jum_kem3',
                'p.nm_kem3',
                'bp.hrg_jum1',
                'bp.hrg_jum2',
                'bp.hrg_jum3',
                DB::raw($warehouseId ? 'COALESCE(ib.qty_on_hand, 0) as qty' : '0 as qty'),
            ])->paginate(10)->withQueryString();
        }

        $settings = $this->branchSettings();

        return view('admin.utility.barcode', compact('products', 'q', 'settings', 'picks', 'branchId'));
    }

    public function barcodePick(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.sku' => 'required|string|max:50',
            'items.*.copies' => 'nullable|integer|min:0|max:50',
            'items.*.selected' => 'required|boolean',
        ]);

        $picks = $this->barcodePicks();
        foreach ($data['items'] as $item) {
            $copies = (int) ($item['copies'] ?? 0);
            if ($item['selected'] && $copies > 0) {
                $picks[$item['sku']] = $copies;
            } else {
                unset($picks[$item['sku']]);
            }
        }
        session([$this->barcodePickKey() => $picks]);

        return response()->json(['count' => count($picks)]);
    }

    public function barcodeReset()
    {
        session()->forget($this->barcodePickKey());

        return back()->with('success', 'Pilihan cetak dikosongkan.');
    }

    public function barcodePrint(Request $request)
    {
        $data = $request->validate([
            'paper' => 'required|in:A4,58,80,70',
            'mode' => 'required|in:barcode,qr',
        ]);

        $picks = $this->barcodePicks();
        if ($picks === []) {
            return back()->with('error', 'Belum ada barang yang dipilih.');
        }

        $branchId = BranchContext::branchId();
        $rows = DB::table('products as p')
            ->leftJoin('branch_product_prices as bp', function ($join) use ($branchId) {
                $join->on('bp.product_id', '=', 'p.id');
                if ($branchId) {
                    $join->where('bp.branch_id', '=', $branchId);
                }
            })
            ->whereIn('p.sku', array_keys($picks))
            ->get(['p.sku', 'p.barcode', 'p.name', 'bp.hrg_jum1']);

        $labels = [];
        foreach ($rows as $row) {
            $copies = (int) ($picks[$row->sku] ?? 0);
            $code = trim((string) ($row->barcode ?: $row->sku));
            if ($code === '' || $copies < 1) {
                continue;
            }
            for ($i = 0; $i < $copies; $i++) {
                $labels[] = [
                    'code' => $code,
                    'name' => (string) $row->name,
                    'price' => (float) ($row->hrg_jum1 ?? 0),
                ];
            }
        }

        if ($labels === []) {
            return back()->with('error', 'Barang yang dipilih tidak punya barcode.');
        }

        if ($data['paper'] === '70' && $data['mode'] === 'barcode') {
            $printer = trim((string) ($this->branchSettings()->printer_name ?? ''));

            return view('admin.utility.barcode-tspl', [
                'tspl' => $this->buildBarcodeTspl($labels),
                'printer' => $printer !== '' ? $printer : 'XP-360B',
                'count' => count($labels),
            ]);
        }

        return view('admin.utility.barcode-print', [
            'labels' => $labels,
            'paper' => $data['paper'],
            'mode' => $data['mode'],
        ]);
    }

    public function expired(Request $request)
    {
        abort_unless(Schema::hasColumn('beli_brg', 'tgl_exp'), 501, 'Jalankan migrasi phase G untuk kolom tgl_exp.');

        $kdToko = BranchContext::kdToko();
        $days = (int) $request->get('days', 30);

        $rows = DB::table('beli_brg as bb')
            ->leftJoin('mas_brg as mb', function ($j) use ($kdToko) {
                $j->on('bb.kd_brg', '=', 'mb.kd_brg')->where('mb.kd_toko', '=', $kdToko);
            })
            ->where('bb.kd_toko', $kdToko)
            ->where('bb.stok_jual', '>', 0)
            ->whereNotNull('bb.tgl_exp')
            ->where('bb.tgl_exp', '<=', now()->addDays($days)->toDateString())
            ->orderBy('bb.tgl_exp')
            ->select('bb.kd_brg', 'mb.nm_brg', 'bb.stok_jual', 'bb.tgl_exp', 'bb.no_fak')
            ->limit(100)
            ->get();

        return view('admin.utility.expired', compact('rows', 'days'));
    }

    public function updateExpiry(Request $request)
    {
        abort_unless(Schema::hasColumn('beli_brg', 'tgl_exp'), 501);

        $data = $request->validate([
            'no_urut' => 'required|integer',
            'tgl_exp' => 'nullable|date',
        ]);

        DB::table('beli_brg')
            ->where('no_urut', $data['no_urut'])
            ->where('kd_toko', BranchContext::kdToko())
            ->update(['tgl_exp' => $data['tgl_exp']]);

        return back()->with('success', 'Tanggal expired diperbarui.');
    }

    public function deadStock(Request $request)
    {
        $month = (int) $request->get('month', (int) date('n', strtotime(BranchContext::businessDate())));
        $year = (int) $request->get('year', (int) date('Y', strtotime(BranchContext::businessDate())));
        $rows = $this->deadStock->topDeadStock(BranchContext::kdToko(), $month, $year, 50);

        return view('admin.utility.dead-stock', compact('rows', 'month', 'year'));
    }

    public function deadStockPrint(Request $request)
    {
        $month = (int) $request->get('month', (int) date('n'));
        $year = (int) $request->get('year', (int) date('Y'));
        $rows = $this->deadStock->topDeadStock(BranchContext::kdToko(), $month, $year, 100);

        return view('admin.utility.dead-stock-print', compact('rows', 'month', 'year'));
    }

    public function dataCheck()
    {
        $kdToko = BranchContext::kdToko();

        return view('admin.utility.data-check', [
            'dupBarcodes' => $this->integrity->duplicateBarcodes($kdToko),
            'negativeStock' => $this->integrity->negativeStock($kdToko),
            'emptySales' => $this->integrity->salesWithoutLines($kdToko),
        ]);
    }

    public function setBagian(Request $request)
    {
        $bagian = DB::table('bag_brg')->orderBy('nm_bag')->get();
        $hasColumn = Schema::hasTable('mas_brg') && Schema::hasColumn('mas_brg', 'id_bag');

        return view('admin.utility.set-bagian', compact('bagian', 'hasColumn'));
    }

    public function applyBagian(Request $request)
    {
        abort_unless(Schema::hasTable('mas_brg') && Schema::hasColumn('mas_brg', 'id_bag'), 501);

        $data = $request->validate([
            'id_bag' => 'required|integer',
            'skus' => 'required|string',
        ]);

        $skus = preg_split('/[\s,;]+/', $data['skus'], -1, PREG_SPLIT_NO_EMPTY);
        $kdToko = BranchContext::kdToko();

        $updated = DB::table('mas_brg')
            ->where('kd_toko', $kdToko)
            ->whereIn('kd_brg', $skus)
            ->update(['id_bag' => $data['id_bag']]);

        return back()->with('success', $updated.' barang di-update ke bagian #'.$data['id_bag'].'.');
    }

    public function settings()
    {
        return view('admin.utility.settings', ['settings' => $this->branchSettings()]);
    }

    public function saveSettings(Request $request)
    {
        $data = $request->validate([
            'printer_name' => 'nullable|string|max:100',
            'barcode_copies' => 'nullable|integer|min:1|max:20',
            'barcode_printer_type' => 'nullable|in:browser,tspl',
        ]);

        $kdToko = BranchContext::kdToko();
        abort_unless($kdToko, 403);

        $payload = array_merge($data, ['updated_at' => now()]);
        if (DB::table('branch_utility_settings')->where('kd_toko', $kdToko)->exists()) {
            DB::table('branch_utility_settings')->where('kd_toko', $kdToko)->update($payload);
        } else {
            DB::table('branch_utility_settings')->insert(array_merge($payload, [
                'kd_toko' => $kdToko,
                'created_at' => now(),
            ]));
        }

        return back()->with('success', 'Pengaturan gerai disimpan.');
    }

    public function priceCompare()
    {
        $rows = collect();
        if (Schema::hasTable('branch_product_prices') && Schema::hasTable('products')) {
            $rows = DB::table('branch_product_prices as bpp')
                ->join('products as p', 'p.id', '=', 'bpp.product_id')
                ->join('branches as b', 'b.id', '=', 'bpp.branch_id')
                ->selectRaw('p.sku, p.name, b.name as branch_name, bpp.sell_price, bpp.updated_at')
                ->orderBy('p.sku')
                ->limit(200)
                ->get()
                ->groupBy('sku');
        }

        return view('admin.utility.price-compare', compact('rows'));
    }

    protected function barcodePickKey(): string
    {
        return 'barcode_picks.'.(BranchContext::kdToko() ?: 'none');
    }

    protected function barcodePicks(): array
    {
        $picks = session($this->barcodePickKey(), []);

        return is_array($picks) ? $picks : [];
    }

    protected function buildBarcodeTspl(array $labels): string
    {
        $tspl = "SIZE 70 mm,15 mm\r\nGAP 3 mm,0\r\nDIRECTION 1\r\nREFERENCE 0,0\r\nDENSITY 10\r\nSPEED 3\r\nAUTODETECT\r\n";
        $codes = array_map(fn ($label) => str_replace(['"', "\r", "\n"], '', $label['code']), $labels);
        for ($i = 0; $i < count($codes); $i += 2) {
            $tspl .= "CLS\r\n";
            $tspl .= 'BARCODE 30,4,"128",55,1,0,2,2,"'.$codes[$i]."\"\r\n";
            if (isset($codes[$i + 1])) {
                $tspl .= 'BARCODE 318,4,"128",55,1,0,2,2,"'.$codes[$i + 1]."\"\r\n";
            }
            $tspl .= "PRINT 1,1\r\n";
        }

        return $tspl;
    }

    protected function branchSettings(): object
    {
        $kdToko = BranchContext::kdToko();
        $defaults = (object) [
            'printer_name' => null,
            'barcode_copies' => 1,
            'barcode_printer_type' => 'browser',
        ];

        if (! $kdToko || ! Schema::hasTable('branch_utility_settings')) {
            return $defaults;
        }

        $row = DB::table('branch_utility_settings')->where('kd_toko', $kdToko)->first();

        return $row ? (object) array_merge((array) $defaults, (array) $row) : $defaults;
    }
}
