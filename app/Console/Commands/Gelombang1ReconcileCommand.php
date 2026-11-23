<?php

namespace App\Console\Commands;

use App\Models\Warehouse;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Gelombang1ReconcileCommand extends Command
{
    protected $signature = 'erp:gelombang1-reconcile
                            {--limit=30 : Jumlah selisih yang ditampil}
                            {--sync-cache : Salin qty ERP ke mas_brg.jml_brg (cache tampilan)}';

    protected $description = 'Gelombang 1: bandingkan inventory_balances vs cache mas_brg';

    public function handle(): int
    {
        if (! Schema::hasTable('inventory_balances') || ! Schema::hasTable('mas_brg')) {
            $this->error('Tabel stok belum lengkap.');

            return self::FAILURE;
        }

        $limit = (int) $this->option('limit');
        $sync = (bool) $this->option('sync-cache');
        $updated = 0;
        $diff = [];

        foreach (Warehouse::query()->whereNotNull('kd_toko')->orderBy('kd_toko')->get() as $w) {
            $balances = DB::table('inventory_balances as ib')
                ->join('products as p', 'p.id', '=', 'ib.product_id')
                ->where('ib.warehouse_id', $w->id)
                ->get(['p.sku', 'ib.qty_on_hand']);
            $cache = DB::table('mas_brg')->where('kd_toko', $w->kd_toko)->pluck('jml_brg', 'kd_brg');

            foreach ($balances as $row) {
                $erp = (float) $row->qty_on_hand;
                $has = $cache->has($row->sku);
                $cached = $has ? (float) $cache->get($row->sku) : 0;
                if ($sync && $has && abs($erp - $cached) > 0.0001) {
                    DB::table('mas_brg')
                        ->where('kd_toko', $w->kd_toko)
                        ->where('kd_brg', $row->sku)
                        ->update(['jml_brg' => $erp]);
                    $updated++;
                    $cached = $erp;
                }
                if (abs($erp - $cached) > 0.01 && count($diff) < $limit) {
                    $diff[] = [$w->kd_toko, $row->sku, $erp, $has ? $cached : '(tidak ada baris)'];
                }
            }
        }

        if ($sync) {
            $this->info("Cache mas_brg.jml_brg disalin dari ERP: {$updated} baris.");
        }

        if ($diff === []) {
            $this->info('Cache mas_brg selaras dengan ERP (atau selisih hanya SKU tanpa baris mas_brg di lokasi itu).');

            return self::SUCCESS;
        }

        $this->warn('Selisih tercatat. Angka resmi = kolom erp. FIFO beli_brg untuk HPP, bukan qty rak.');
        $this->table(['kd_toko', 'sku', 'erp', 'cache_mas_brg'], $diff);

        return self::SUCCESS;
    }
}
