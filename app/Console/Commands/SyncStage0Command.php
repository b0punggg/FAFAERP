<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\CatalogProduct;
use App\Models\Company;
use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncStage0Command extends Command
{
    protected $signature = 'erp:sync-stage0';

    protected $description = 'Tahap 0: samakan kode lokasi, SKU, dan supplier antara gerai dan gudang pusat';

    public function handle(): int
    {
        if (! Schema::hasColumn('warehouses', 'kd_toko') || ! $this->viewExists()) {
            $this->error('Kolom kamus belum ada. Jalankan php artisan migrate dulu.');

            return self::FAILURE;
        }

        $centralKd = (string) config('erp.central_kd_toko', 'PUSAT');
        $retail = Company::where('type', 'retail')->first();
        if (! $retail) {
            $this->error('Company retail belum ada.');

            return self::FAILURE;
        }

        DB::transaction(function () use ($centralKd, $retail) {
            $this->ensureCentralStore($centralKd);
            $this->bindWarehouses($centralKd, $retail->id);
            $this->ensureCatalogSkus();
        });

        return $this->report($centralKd);
    }

    private function ensureCentralStore(string $centralKd): void
    {
        $exists = DB::table('toko')->where('kd_toko', $centralKd)->exists();
        if ($exists) {
            return;
        }

        DB::table('toko')->insert($this->tokoRow($centralKd, 'Gudang Pusat', ''));
        $this->info("Toko {$centralKd} ditambahkan sebagai gudang pusat (bukan gerai jual).");
    }

    private function bindWarehouses(string $centralKd, int $retailId): void
    {
        $central = Warehouse::where('company_id', $retailId)->where('is_central', true)->first();
        if (! $central) {
            $central = Warehouse::create([
                'company_id' => $retailId,
                'branch_id' => null,
                'code' => 'CENTRAL',
                'kd_toko' => $centralKd,
                'name' => 'Gudang Pusat',
                'is_central' => true,
            ]);
        } else {
            $central->kd_toko = $centralKd;
            $central->branch_id = null;
            $central->save();
        }

        Branch::where('company_id', $retailId)
            ->where('kd_toko', '!=', $centralKd)
            ->whereNotNull('kd_toko')
            ->each(function (Branch $branch) use ($retailId) {
                $toko = DB::table('toko')->where('kd_toko', $branch->kd_toko)->first();
                if (! $toko) {
                    DB::table('toko')->insert($this->tokoRow(
                        $branch->kd_toko,
                        mb_substr($branch->name, 0, 30),
                        mb_substr((string) $branch->address, 0, 60)
                    ));
                }

                $warehouse = Warehouse::where('branch_id', $branch->id)->first();
                if (! $warehouse) {
                    Warehouse::create([
                        'company_id' => $retailId,
                        'branch_id' => $branch->id,
                        'code' => 'WH-'.$branch->kd_toko,
                        'kd_toko' => $branch->kd_toko,
                        'name' => 'Gudang '.$branch->name,
                        'is_central' => false,
                    ]);

                    return;
                }

                $warehouse->kd_toko = $branch->kd_toko;
                $warehouse->save();
            });
    }

    private function ensureCatalogSkus(): void
    {
        $missing = DB::table('mas_brg as b')
            ->leftJoin('products as p', 'p.sku', '=', 'b.kd_brg')
            ->whereNull('p.id')
            ->select('b.*')
            ->get();

        foreach ($missing as $row) {
            CatalogProduct::firstOrCreate(
                ['sku' => $row->kd_brg],
                [
                    'barcode' => $row->kd_bar !== '' ? $row->kd_bar : null,
                    'name' => $row->nm_brg,
                    'kd_kem1' => (int) $row->kd_kem1 ?: 1,
                    'nm_kem1' => $row->nm_kem1,
                    'jum_kem1' => $row->jum_kem1,
                    'kd_kem2' => $row->kd_kem2 !== '' ? (int) $row->kd_kem2 : null,
                    'nm_kem2' => $row->nm_kem2 !== '' ? $row->nm_kem2 : null,
                    'jum_kem2' => $row->jum_kem2,
                    'kd_kem3' => $row->kd_kem3 !== '' ? (int) $row->kd_kem3 : null,
                    'nm_kem3' => $row->nm_kem3 !== '' ? $row->nm_kem3 : null,
                    'jum_kem3' => $row->jum_kem3,
                ]
            );
            $this->line("SKU {$row->kd_brg} ditambahkan ke products.");
        }
    }

    private function report(string $centralKd): int
    {
        $lokasi = DB::table('v_lokasi')->orderBy('is_central', 'desc')->orderBy('kd_toko')->get();
        $this->table(
            ['kd_toko', 'nama', 'gudang', 'pusat', 'branch_id'],
            $lokasi->map(fn ($row) => [
                $row->kd_toko,
                $row->nm_toko,
                $row->warehouse_code,
                $row->is_central ? 'ya' : '',
                $row->branch_id,
            ])
        );

        $missingSku = DB::table('mas_brg as b')
            ->leftJoin('products as p', 'p.sku', '=', 'b.kd_brg')
            ->whereNull('p.id')
            ->count();
        $orphanSku = DB::table('products as p')
            ->leftJoin('mas_brg as b', 'b.kd_brg', '=', 'p.sku')
            ->whereNull('b.kd_brg')
            ->count();
        $dupBrg = DB::table('mas_brg')->select('kd_brg')->groupBy('kd_brg')->havingRaw('COUNT(*) > 1')->get()->count();
        $dupToko = DB::table('toko')->select('kd_toko')->groupBy('kd_toko')->havingRaw('COUNT(*) > 1')->get()->count();
        $dupSup = DB::table('supplier')->select('kd_sup')->groupBy('kd_sup')->havingRaw('COUNT(*) > 1')->get()->count();
        $central = $lokasi->firstWhere('kd_toko', $centralKd);
        $geraiTanpaGudang = DB::table('branches as b')
            ->leftJoin('v_lokasi as v', 'v.kd_toko', '=', 'b.kd_toko')
            ->where('b.kd_toko', '!=', $centralKd)
            ->whereNull('v.warehouse_id')
            ->count();

        $dupBar = DB::select(
            "SELECT COUNT(*) AS n FROM (
                SELECT kd_bar FROM mas_brg WHERE kd_bar <> '' GROUP BY kd_bar HAVING COUNT(*) > 1
            ) d"
        );
        $barCount = (int) ($dupBar[0]->n ?? 0);
        if ($barCount > 0) {
            $this->warn("{$barCount} barcode dipakai lebih dari satu kd_brg. Kode barang tetap unik; barcode tidak digabung.");
        }

        if (! $central || ! $central->is_central || $central->branch_id) {
            $this->error("Gudang pusat {$centralKd} belum terpetakan ke warehouse CENTRAL tanpa cabang.");

            return self::FAILURE;
        }
        if ($missingSku || $orphanSku || $dupBrg || $dupToko || $dupSup || $geraiTanpaGudang) {
            $this->error("Kamus belum rapat. SKU hilang={$missingSku}, SKU tanpa mas_brg={$orphanSku}, duplikat barang={$dupBrg}, toko={$dupToko}, supplier={$dupSup}, gerai tanpa gudang={$geraiTanpaGudang}.");

            return self::FAILURE;
        }

        $this->info('Tahap 0 selesai. Satu kd_toko, satu kd_brg, satu kd_sup. Gudang pusat = '.$centralKd.'.');

        return self::SUCCESS;
    }

    private function tokoRow(string $kd, string $name, string $address): array
    {
        $row = [
            'kd_toko' => $kd,
            'nm_toko' => $name,
            'al_toko' => $address,
        ];
        if (Schema::hasColumn('toko', 'no_telp')) {
            $row['no_telp'] = '';
        }

        return $row;
    }

    private function viewExists(): bool
    {
        $row = DB::selectOne(
            'SELECT 1 AS ok FROM information_schema.views WHERE table_schema = ? AND table_name = ? LIMIT 1',
            [DB::getDatabaseName(), 'v_lokasi']
        );

        return $row !== null;
    }
};
