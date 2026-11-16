<?php

namespace App\Http\Controllers;

use App\Models\ReportFavorite;
use App\Services\Reports\PivotReportService;
use App\Support\Reports\ReportOutput;
use App\Support\Reports\ReportSheet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PivotReportController extends Controller
{
    public function __construct(protected PivotReportService $pivot)
    {
    }

    public function index(Request $request)
    {
        $catalog = $this->pivot->catalog();
        $dataset = $request->get('dataset', 'penjualan');
        if (! isset($catalog[$dataset])) {
            $dataset = 'penjualan';
        }
        $spec = $catalog[$dataset];
        $group = $request->get('group', array_key_first($spec['groups']));
        if (! isset($spec['groups'][$group])) {
            $group = array_key_first($spec['groups']);
        }
        $measures = array_values(array_filter(
            (array) $request->get('measures', array_keys($spec['measures'])),
            fn ($measure) => isset($spec['measures'][$measure])
        ));
        if ($measures === []) {
            $measures = array_keys($spec['measures']);
        }
        $from = $request->get('from', date('Y-m-01'));
        $to = $request->get('to', date('Y-m-d'));
        $kdToko = $request->get('kd_toko') ?: null;
        $result = $this->pivot->run($dataset, $group, $measures, $kdToko, $from, $to);
        $favorites = ReportFavorite::query()
            ->where('user_id', auth()->id())
            ->latest()
            ->limit(20)
            ->get();
        $stores = DB::table('toko')->orderBy('nm_toko')->get(['kd_toko', 'nm_toko']);
        $foundStore = $kdToko ? $stores->firstWhere('kd_toko', $kdToko) : null;
        $storeName = $foundStore->nm_toko ?? ($kdToko ?: 'Semua gerai');
        $money = ['omzet', 'diskon', 'nilai', 'harga_rata', 'tagihan', 'tertagih', 'sisa'];
        $columns = [['key' => 'row_label', 'label' => $spec['groups'][$group]]];
        foreach ($measures as $measure) {
            $column = [
                'key' => $measure,
                'label' => $spec['measures'][$measure],
            ];
            $column[in_array($measure, $money, true) ? 'money' : 'qty'] = true;
            $columns[] = $column;
        }
        $sheetRows = [];
        foreach ($result['rows'] as $row) {
            $line = ['row_label' => $row['row_label'] ?: $row['row_key']];
            foreach ($measures as $measure) {
                $line[$measure] = $row[$measure] ?? 0;
            }
            $sheetRows[] = $line;
        }
        $summary = [];
        foreach ($measures as $measure) {
            $value = (float) ($result['totals'][$measure] ?? 0);
            $summary[$spec['measures'][$measure]] = in_array($measure, $money, true)
                ? 'Rp '.number_format($value, 0, ',', '.')
                : number_format($value, 2, ',', '.');
        }
        $period = $spec['dated'] ? date('d/m/Y', strtotime($from)).' s/d '.date('d/m/Y', strtotime($to)) : 'Tanpa periode';
        $sheet = new ReportSheet(
            'Analisis '.$spec['label'],
            $storeName.' · '.$period,
            $columns,
            $sheetRows,
            $summary,
            [],
            'Tidak ada baris pada filter ini.'
        );

        if (ReportOutput::wants($request)) {
            return ReportOutput::send($request, $sheet);
        }

        return view('admin.reports.pivot', compact(
            'catalog',
            'dataset',
            'spec',
            'group',
            'measures',
            'from',
            'to',
            'kdToko',
            'result',
            'favorites',
            'stores'
        ));
    }

    public function storeFavorite(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
            'dataset' => 'required|string|max:32',
            'group' => 'required|string|max:32',
            'measures' => 'nullable|array',
            'measures.*' => 'string|max:32',
            'kd_toko' => 'nullable|string|max:50',
            'from' => 'nullable|date',
            'to' => 'nullable|date',
        ]);

        ReportFavorite::create([
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'dataset' => $data['dataset'],
            'filters' => [
                'dataset' => $data['dataset'],
                'group' => $data['group'],
                'measures' => $data['measures'] ?? [],
                'kd_toko' => $data['kd_toko'] ?? '',
                'from' => $data['from'] ?? '',
                'to' => $data['to'] ?? '',
            ],
        ]);

        return redirect()->route('reports.pivot', $request->only(['dataset', 'group', 'measures', 'kd_toko', 'from', 'to']));
    }

    public function destroyFavorite(ReportFavorite $favorite)
    {
        abort_unless((int) $favorite->user_id === (int) auth()->id(), 403);
        $favorite->delete();

        return back();
    }
}
