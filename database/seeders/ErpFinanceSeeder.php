<?php

namespace Database\Seeders;

use App\Models\ChartOfAccount;
use App\Models\CoaGroupMap;
use App\Models\Company;
use Illuminate\Database\Seeder;

class ErpFinanceSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['code' => '1101', 'name' => 'Kas', 'type' => 'asset', 'group' => 'cash'],
            ['code' => '1201', 'name' => 'Piutang Usaha', 'type' => 'asset', 'group' => 'ar'],
            ['code' => '1301', 'name' => 'Persediaan', 'type' => 'asset', 'group' => 'inventory'],
            ['code' => '2101', 'name' => 'Hutang Usaha', 'type' => 'liability', 'group' => 'ap'],
            ['code' => '4101', 'name' => 'Penjualan', 'type' => 'revenue', 'group' => 'revenue'],
            ['code' => '2201', 'name' => 'PPN Keluaran', 'type' => 'liability', 'group' => 'ppn_out'],
            ['code' => '1401', 'name' => 'PPN Masukan', 'type' => 'asset', 'group' => 'ppn_in'],
            ['code' => '5101', 'name' => 'Beban Operasional', 'type' => 'expense', 'group' => 'expense'],
            ['code' => '4201', 'name' => 'Pendapatan Lain-lain', 'type' => 'revenue', 'group' => 'other_income'],
        ];

        Company::whereIn('type', ['retail', 'digital', 'field_sales', 'holding'])->each(function (Company $company) use ($templates) {
            foreach ($templates as $row) {
                $account = ChartOfAccount::firstOrCreate(
                    ['company_id' => $company->id, 'code' => $row['code']],
                    ['name' => $row['name'], 'type' => $row['type'], 'is_postable' => true]
                );

                if (! empty($row['group'])) {
                    CoaGroupMap::firstOrCreate(
                        ['group_code' => $row['group'], 'company_id' => $company->id],
                        ['chart_of_account_id' => $account->id]
                    );
                }
            }
        });
    }
}
