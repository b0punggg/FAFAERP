<?php

namespace App\Services\Reports;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PivotReportService
{
    public function catalog(): array
    {
        return [
            'penjualan' => [
                'label' => 'Penjualan',
                'groups' => [
                    'toko' => 'Gerai',
                    'barang' => 'Barang',
                    'pelanggan' => 'Pelanggan',
                    'bulan' => 'Bulan',
                    'pt' => 'PT',
                ],
                'measures' => [
                    'omzet' => 'Omzet',
                    'diskon' => 'Diskon',
                    'qty' => 'Qty',
                    'baris' => 'Jumlah baris',
                ],
                'dated' => true,
            ],
            'pembelian' => [
                'label' => 'Pembelian',
                'groups' => [
                    'toko' => 'Gerai',
                    'supplier' => 'Supplier',
                    'barang' => 'Barang',
                    'bulan' => 'Bulan',
                    'pt' => 'PT',
                ],
                'measures' => [
                    'nilai' => 'Nilai',
                    'qty' => 'Qty',
                    'harga_rata' => 'Harga rata-rata',
                ],
                'dated' => true,
            ],
            'persediaan' => [
                'label' => 'Persediaan',
                'groups' => [
                    'toko' => 'Gudang',
                    'barang' => 'Barang',
                    'bagian' => 'Bagian',
                ],
                'measures' => [
                    'qty' => 'Qty',
                    'sku' => 'Jumlah SKU',
                ],
                'dated' => false,
            ],
            'penagihan' => [
                'label' => 'Penagihan',
                'groups' => [
                    'toko' => 'Gerai',
                    'pelanggan' => 'Pelanggan',
                    'pt' => 'PT',
                ],
                'measures' => [
                    'tagihan' => 'Tagihan',
                    'tertagih' => 'Tertagih',
                    'sisa' => 'Sisa',
                    'faktur' => 'Jumlah faktur',
                ],
                'dated' => true,
            ],
        ];
    }

    public function run(string $dataset, string $group, array $measures, ?string $kdToko, string $from, string $to): array
    {
        $catalog = $this->catalog();
        if (! isset($catalog[$dataset])) {
            return ['rows' => [], 'measures' => [], 'note' => 'Kumpulan data tidak dikenal.'];
        }
        $spec = $catalog[$dataset];
        if (! isset($spec['groups'][$group])) {
            $group = array_key_first($spec['groups']);
        }
        $measures = array_values(array_filter($measures, fn ($m) => isset($spec['measures'][$m])));
        if ($measures === []) {
            $measures = array_keys($spec['measures']);
        }

        $rows = $this->rowsFor($dataset, $group, $kdToko, $from, $to);
        $prior = null;
        if ($spec['dated']) {
            [$priorFrom, $priorTo] = $this->priorWindow($from, $to);
            $priorRows = $this->rowsFor($dataset, $group, $kdToko, $priorFrom, $priorTo);
            $prior = [
                'from' => $priorFrom,
                'to' => $priorTo,
                'totals' => $this->totals($priorRows, $measures),
            ];
        }

        $note = $dataset === 'persediaan'
            ? 'Saldo qty saat ini per gudang. Penilaian rupiah belum dihitung di pivot ini.'
            : '';

        return [
            'rows' => $rows,
            'measures' => $measures,
            'totals' => $this->totals($rows, $measures),
            'prior' => $prior,
            'note' => $note,
        ];
    }

    private function rowsFor(string $dataset, string $group, ?string $kdToko, string $from, string $to): array
    {
        return match ($dataset) {
            'penjualan' => $this->penjualan($group, $kdToko, $from, $to),
            'pembelian' => $this->pembelian($group, $kdToko, $from, $to),
            'persediaan' => $this->persediaan($group, $kdToko),
            'penagihan' => $this->penagihan($group, $kdToko, $from, $to),
            default => [],
        };
    }

    private function priorWindow(string $from, string $to): array
    {
        $start = Carbon::parse($from)->startOfDay();
        $end = Carbon::parse($to)->startOfDay();
        if ($end->lt($start)) {
            $end = $start->copy();
        }
        $days = $start->diffInDays($end) + 1;
        $priorTo = $start->copy()->subDay();
        $priorFrom = $priorTo->copy()->subDays($days - 1);

        return [$priorFrom->toDateString(), $priorTo->toDateString()];
    }

    private function totals(array $rows, array $measures): array
    {
        $totals = [];
        foreach ($measures as $measure) {
            if ($measure === 'harga_rata') {
                $nilai = array_sum(array_map(fn ($row) => (float) ($row['nilai'] ?? 0), $rows));
                $qty = array_sum(array_map(fn ($row) => (float) ($row['qty'] ?? 0), $rows));
                $totals[$measure] = $qty > 0 ? $nilai / $qty : 0;
                continue;
            }
            $totals[$measure] = array_sum(array_map(fn ($row) => (float) ($row[$measure] ?? 0), $rows));
        }

        return $totals;
    }

    private function penjualan(string $group, ?string $kdToko, string $from, string $to): array
    {
        [$key, $label] = match ($group) {
            'barang' => ['jb.kd_brg', 'MAX(jb.nm_brg)'],
            'pelanggan' => ["COALESCE(NULLIF(j.nm_pel, ''), 'Umum')", "COALESCE(NULLIF(j.nm_pel, ''), 'Umum')"],
            'bulan' => ["DATE_FORMAT(jb.tgl_jual, '%Y-%m')", "DATE_FORMAT(jb.tgl_jual, '%Y-%m')"],
            'pt' => ["COALESCE(c.code, 'Tanpa PT')", "COALESCE(c.code, 'Tanpa PT')"],
            default => ['jb.kd_toko', 'jb.kd_toko'],
        };

        $query = DB::table('jual_brg as jb')
            ->leftJoin('jual as j', function ($join) {
                $join->on('j.no_fak', '=', 'jb.no_fak')
                    ->on('j.tgl_jual', '=', 'jb.tgl_jual')
                    ->on('j.kd_toko', '=', 'jb.kd_toko');
            })
            ->when($group === 'pt', function ($q) {
                $q->leftJoin('branches as br', 'br.kd_toko', '=', 'jb.kd_toko')
                    ->leftJoin('companies as c', 'c.id', '=', 'br.company_id');
            })
            ->whereBetween('jb.tgl_jual', [$from, $to])
            ->when($kdToko, fn ($q) => $q->where('jb.kd_toko', $kdToko));

        return $query
            ->selectRaw("{$key} as row_key, {$label} as row_label, SUM(jb.tot_jual - jb.disc) as omzet, SUM(jb.disc) as diskon, SUM(jb.jml_brg) as qty, COUNT(*) as baris")
            ->groupByRaw($key)
            ->orderByDesc('omzet')
            ->limit(200)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function pembelian(string $group, ?string $kdToko, string $from, string $to): array
    {
        [$key, $label] = match ($group) {
            'supplier' => ["COALESCE(NULLIF(b.nm_sup, ''), 'Tanpa supplier')", "COALESCE(NULLIF(b.nm_sup, ''), 'Tanpa supplier')"],
            'barang' => ['bb.kd_brg', 'bb.kd_brg'],
            'bulan' => ["DATE_FORMAT(bb.tgl_fak, '%Y-%m')", "DATE_FORMAT(bb.tgl_fak, '%Y-%m')"],
            'pt' => ["COALESCE(c.code, 'Tanpa PT')", "COALESCE(c.code, 'Tanpa PT')"],
            default => ['bb.kd_toko', 'bb.kd_toko'],
        };

        $rows = DB::table('beli_brg as bb')
            ->leftJoin('beli as b', function ($join) {
                $join->on('b.no_fak', '=', 'bb.no_fak')
                    ->on('b.tgl_fak', '=', 'bb.tgl_fak')
                    ->on('b.kd_toko', '=', 'bb.kd_toko');
            })
            ->when($group === 'pt', function ($q) {
                $q->leftJoin('branches as br', 'br.kd_toko', '=', 'bb.kd_toko')
                    ->leftJoin('companies as c', 'c.id', '=', 'br.company_id');
            })
            ->whereBetween('bb.tgl_fak', [$from, $to])
            ->when($kdToko, fn ($q) => $q->where('bb.kd_toko', $kdToko))
            ->selectRaw("{$key} as row_key, {$label} as row_label, SUM(bb.jml_brg * bb.hrg_beli) as nilai, SUM(bb.jml_brg) as qty")
            ->groupByRaw($key)
            ->orderByDesc('nilai')
            ->limit(200)
            ->get();

        return $rows->map(function ($row) {
            $qty = (float) $row->qty;
            $row->harga_rata = $qty > 0 ? (float) $row->nilai / $qty : 0;

            return (array) $row;
        })->all();
    }

    private function persediaan(string $group, ?string $kdToko): array
    {
        if (! Schema::hasTable('v_stok_lokasi') && ! $this->viewExists('v_stok_lokasi')) {
            return [];
        }

        [$key, $label] = match ($group) {
            'barang' => ['g.kd_brg', 'MAX(g.nm_brg)'],
            'bagian' => ["COALESCE(NULLIF(g.nm_bag, ''), 'Tanpa bagian')", "COALESCE(NULLIF(g.nm_bag, ''), 'Tanpa bagian')"],
            default => ['g.kd_toko', 'MAX(g.nm_toko)'],
        };

        return DB::table('v_stok_lokasi as g')
            ->when($kdToko, fn ($q) => $q->where('g.kd_toko', $kdToko))
            ->selectRaw("{$key} as row_key, {$label} as row_label, SUM(g.qty) as qty, COUNT(DISTINCT g.kd_brg) as sku")
            ->groupByRaw($key)
            ->orderByDesc('qty')
            ->limit(200)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function penagihan(string $group, ?string $kdToko, string $from, string $to): array
    {
        $key = match ($group) {
            'pelanggan' => "COALESCE(NULLIF(j.nm_pel, ''), 'Umum')",
            'pt' => "COALESCE(c.code, 'Tanpa PT')",
            default => 'j.kd_toko',
        };

        $paid = Schema::hasTable('piutang_payments')
            ? '(SELECT COALESCE(SUM(amount), 0) FROM piutang_payments p WHERE p.sale_id = j.no_urut)'
            : '0';

        return DB::table('jual as j')
            ->when($group === 'pt', function ($q) {
                $q->leftJoin('branches as br', 'br.kd_toko', '=', 'j.kd_toko')
                    ->leftJoin('companies as c', 'c.id', '=', 'br.company_id');
            })
            ->whereBetween('j.tgl_jual', [$from, $to])
            ->where('j.byr_hutang', '>', 0)
            ->when($kdToko, fn ($q) => $q->where('j.kd_toko', $kdToko))
            ->selectRaw("{$key} as row_key, {$key} as row_label, SUM(j.byr_hutang) as tagihan, SUM({$paid}) as tertagih, SUM(j.byr_hutang) - SUM({$paid}) as sisa, COUNT(*) as faktur")
            ->groupByRaw($key)
            ->orderByDesc('tagihan')
            ->limit(200)
            ->get()
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function viewExists(string $name): bool
    {
        $row = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.views WHERE table_schema = ? AND table_name = ? LIMIT 1',
            [DB::getDatabaseName(), $name]
        );

        return $row !== null;
    }
}
