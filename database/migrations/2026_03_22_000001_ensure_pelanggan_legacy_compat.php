<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('pelanggan')) {
            return;
        }

        if (! Schema::hasColumn('pelanggan', 'kd_toko')) {
            Schema::table('pelanggan', function (Blueprint $table) {
                $table->string('kd_toko', 50)->nullable();
                $table->index('kd_toko');
            });
        }

        if (! Schema::hasColumn('pelanggan', 'telp_pel')) {
            Schema::table('pelanggan', function (Blueprint $table) {
                $table->string('telp_pel', 50)->nullable();
            });
        }

        if (Schema::hasColumn('pelanggan', 'telp_pel') && Schema::hasColumn('pelanggan', 'no_telp')) {
            DB::statement("
                UPDATE pelanggan
                SET telp_pel = NULLIF(TRIM(no_telp), '')
                WHERE (telp_pel IS NULL OR TRIM(telp_pel) = '')
                  AND no_telp IS NOT NULL AND TRIM(no_telp) <> ''
            ");
        }
    }

    public function down(): void
    {
        // keep columns — data may depend on them
    }
};
