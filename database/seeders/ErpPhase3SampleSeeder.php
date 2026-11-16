<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\SfaOutlet;
use Illuminate\Database\Seeder;

class ErpPhase3SampleSeeder extends Seeder
{
    public function run(): void
    {
        $to = Company::where('type', 'field_sales')->first();
        if ($to) {
            SfaOutlet::firstOrCreate(
                ['company_id' => $to->id, 'code' => 'OUT-001'],
                ['name' => 'Outlet Pilot TO', 'credit_limit' => 5000000]
            );
        }
    }
}
