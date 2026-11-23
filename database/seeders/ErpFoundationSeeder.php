<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ErpFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $holding = Company::firstOrCreate(
            ['code' => 'HOLDING'],
            ['legal_name' => 'Holding FAFA Group', 'type' => 'holding', 'currency' => 'IDR']
        );

        $retail = Company::firstOrCreate(
            ['code' => 'RETAIL'],
            ['legal_name' => 'PT Retail FAFA', 'type' => 'retail', 'currency' => 'IDR']
        );

        Company::firstOrCreate(
            ['code' => 'DIGITAL'],
            ['legal_name' => 'PT Digital Marketing FAFA', 'type' => 'digital', 'currency' => 'IDR']
        );

        Company::firstOrCreate(
            ['code' => 'TO'],
            ['legal_name' => 'PT Sales Taking Order FAFA', 'type' => 'field_sales', 'currency' => 'IDR']
        );

        $centralKd = (string) config('erp.central_kd_toko', 'PUSAT');

        $central = Warehouse::firstOrCreate(
            ['company_id' => $retail->id, 'code' => 'CENTRAL'],
            ['name' => 'Gudang Pusat', 'is_central' => true, 'branch_id' => null, 'kd_toko' => $centralKd]
        );
        $central->kd_toko = $centralKd;
        $central->is_central = true;
        $central->branch_id = null;
        $central->save();

        $to = Company::where('type', 'field_sales')->first();
        $kanvasKd = (string) config('erp.kanvas_kd_toko', 'KANVAS');
        if ($to) {
            $kanvas = Warehouse::firstOrCreate(
                ['company_id' => $to->id, 'code' => 'KANVAS'],
                ['name' => 'Gudang Kanvas', 'is_central' => false, 'branch_id' => null, 'kd_toko' => $kanvasKd]
            );
            $kanvas->kd_toko = $kanvasKd;
            $kanvas->branch_id = null;
            $kanvas->is_central = false;
            $kanvas->save();
            if (! DB::table('toko')->where('kd_toko', $kanvasKd)->exists()) {
                $row = [
                    'kd_toko' => $kanvasKd,
                    'nm_toko' => 'Gudang Kanvas',
                    'al_toko' => '',
                ];
                if (Schema::hasColumn('toko', 'no_telp')) {
                    $row['no_telp'] = '';
                }
                DB::table('toko')->insert($row);
            }
        }

        if (! DB::table('toko')->where('kd_toko', $centralKd)->exists()) {
            $row = [
                'kd_toko' => $centralKd,
                'nm_toko' => 'Gudang Pusat',
                'al_toko' => '',
            ];
            if (Schema::hasColumn('toko', 'no_telp')) {
                $row['no_telp'] = '';
            }
            DB::table('toko')->insert($row);
        }

        $onlineKd = (string) config('erp.online_kd_toko', 'ONLINE');
        $notGerai = [$centralKd, $kanvasKd, $onlineKd];

        $stores = Store::orderBy('no_urut')->get()->reject(function ($store) use ($notGerai) {
            $kd = is_object($store) ? $store->kd_toko : $store['kd_toko'];

            return in_array($kd, $notGerai, true);
        })->values();
        if ($stores->isEmpty()) {
            $stores = collect([
                (object) ['kd_toko' => '001', 'nm_toko' => 'Gerai Utama', 'al_toko' => null, 'no_urut' => 1],
            ]);
        }

        foreach ($stores as $store) {
            $kd = is_object($store) ? $store->kd_toko : $store['kd_toko'];
            $nm = is_object($store) ? $store->nm_toko : $store['nm_toko'];
            $al = is_object($store) ? ($store->al_toko ?? null) : ($store['al_toko'] ?? null);
            $sort = is_object($store) ? ($store->no_urut ?? 0) : ($store['no_urut'] ?? 0);

            $branch = Branch::firstOrCreate(
                ['company_id' => $retail->id, 'kd_toko' => $kd],
                [
                    'name' => $nm,
                    'address' => $al,
                    'sort_order' => $sort,
                    'is_active' => true,
                ]
            );

            $warehouse = Warehouse::firstOrCreate(
                ['company_id' => $retail->id, 'code' => 'WH-'.$kd],
                [
                    'branch_id' => $branch->id,
                    'kd_toko' => $kd,
                    'name' => 'Gudang '.$nm,
                    'is_central' => false,
                ]
            );
            $warehouse->kd_toko = $kd;
            $warehouse->branch_id = $branch->id;
            $warehouse->save();
        }

        $permissions = [
            ['slug' => 'sales.create', 'name' => 'Buat penjualan'],
            ['slug' => 'sales.void', 'name' => 'Void penjualan'],
            ['slug' => 'sales.discount.approve', 'name' => 'Approve diskon'],
            ['slug' => 'context.switch', 'name' => 'Ganti gerai/company'],
            ['slug' => 'finance.view', 'name' => 'Lihat keuangan'],
            ['slug' => 'holding.dashboard', 'name' => 'Dashboard holding'],
        ];

        foreach ($permissions as $p) {
            Permission::firstOrCreate(['slug' => $p['slug']], ['name' => $p['name']]);
        }

        $cashier = Role::firstOrCreate(
            ['slug' => 'cashier', 'company_id' => $retail->id],
            ['name' => 'Kasir Gerai']
        );
        $cashier->permissions()->sync(
            Permission::whereIn('slug', ['sales.create'])->pluck('id')
        );

        $manager = Role::firstOrCreate(
            ['slug' => 'branch_manager', 'company_id' => $retail->id],
            ['name' => 'Kepala Toko']
        );
        $manager->permissions()->sync(
            Permission::whereIn('slug', ['sales.create', 'sales.void', 'sales.discount.approve'])->pluck('id')
        );

        Role::firstOrCreate(
            ['slug' => 'holding_viewer', 'company_id' => $holding->id],
            ['name' => 'Direksi Holding']
        )->permissions()->sync(
            Permission::whereIn('slug', ['context.switch', 'finance.view', 'holding.dashboard'])->pluck('id')
        );

        User::query()->each(function (User $user) use ($retail) {
            if (! $user->kd_toko) {
                return;
            }
            $branch = Branch::where('company_id', $retail->id)->where('kd_toko', $user->kd_toko)->first();
            if ($branch) {
                $user->company_id = $retail->id;
                $user->branch_id = $branch->id;
                $user->save();
            }
        });
    }
}
