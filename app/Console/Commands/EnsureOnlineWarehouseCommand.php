<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureOnlineWarehouseCommand extends Command
{
    protected $signature = 'erp:ensure-online-warehouse';

    protected $description = 'Tahap 1 marketplace: gudang ONLINE tanpa cabang jual dan tanpa mengubah qty gerai';

    public function handle(): int
    {
        $kd = (string) config('erp.online_kd_toko', 'ONLINE');
        $retail = Company::where('type', 'retail')->first();
        if (! $retail) {
            $this->error('Company retail belum ada.');

            return self::FAILURE;
        }

        $now = now();
        DB::transaction(function () use ($kd, $retail, $now) {
            if (! DB::table('toko')->where('kd_toko', $kd)->exists()) {
                $row = [
                    'kd_toko' => $kd,
                    'nm_toko' => 'Gudang Marketplace',
                    'al_toko' => '',
                ];
                if (Schema::hasColumn('toko', 'no_telp')) {
                    $row['no_telp'] = '';
                }
                DB::table('toko')->insert($row);
                $this->info("Toko {$kd} ditambahkan (bukan gerai jual).");
            }

            $warehouse = DB::table('warehouses')
                ->where('company_id', $retail->id)
                ->where('code', 'ONLINE')
                ->first();
            if (! $warehouse) {
                DB::table('warehouses')->insert([
                    'company_id' => $retail->id,
                    'branch_id' => null,
                    'code' => 'ONLINE',
                    'kd_toko' => $kd,
                    'name' => 'Gudang Marketplace',
                    'is_central' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $this->info('Warehouse ONLINE dibuat tanpa branch_id.');
            } else {
                DB::table('warehouses')->where('id', $warehouse->id)->update([
                    'branch_id' => null,
                    'kd_toko' => $kd,
                    'is_central' => 0,
                    'updated_at' => $now,
                ]);
                $this->info('Warehouse ONLINE dipastikan tanpa cabang jual.');
            }
        });

        $wh = DB::table('warehouses')->where('code', 'ONLINE')->first();
        $qty = 0;
        if ($wh && Schema::hasTable('inventory_balances')) {
            $qty = (float) DB::table('inventory_balances')->where('warehouse_id', $wh->id)->sum('qty_on_hand');
        }
        $this->info("Gudang {$kd} siap. Qty ONLINE saat ini: {$qty} (belum diisi).");

        return self::SUCCESS;
    }
}
