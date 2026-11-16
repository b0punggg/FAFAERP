<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_order_id')->constrained()->cascadeOnDelete();
            $table->string('kd_brg', 50);
            $table->decimal('qty', 16, 4);
            $table->timestamps();
        });

        Schema::create('sfa_order_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sfa_order_id')->constrained()->cascadeOnDelete();
            $table->string('kd_brg', 50);
            $table->decimal('qty', 16, 4);
            $table->timestamps();
        });

        $this->ensureKanvas();
    }

    public function down(): void
    {
        Schema::dropIfExists('sfa_order_lines');
        Schema::dropIfExists('digital_order_lines');
    }

    private function ensureKanvas(): void
    {
        $company = DB::table('companies')->where('type', 'field_sales')->first();
        if (! $company) {
            return;
        }

        $kd = (string) config('erp.kanvas_kd_toko', 'KANVAS');
        $now = now();

        $warehouse = DB::table('warehouses')->where('company_id', $company->id)->where('code', 'KANVAS')->first();
        if (! $warehouse) {
            DB::table('warehouses')->insert([
                'company_id' => $company->id,
                'branch_id' => null,
                'code' => 'KANVAS',
                'kd_toko' => $kd,
                'name' => 'Gudang Kanvas',
                'is_central' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('warehouses')->where('id', $warehouse->id)->update([
                'branch_id' => null,
                'kd_toko' => $kd,
                'is_central' => 0,
                'updated_at' => $now,
            ]);
        }

        if (! DB::table('toko')->where('kd_toko', $kd)->exists()) {
            $row = [
                'kd_toko' => $kd,
                'nm_toko' => 'Gudang Kanvas',
                'al_toko' => '',
            ];
            if (Schema::hasColumn('toko', 'no_telp')) {
                $row['no_telp'] = '';
            }
            DB::table('toko')->insert($row);
        }
    }
};
