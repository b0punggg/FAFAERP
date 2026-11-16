<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('beli_brg') && ! Schema::hasColumn('beli_brg', 'tgl_exp')) {
            Schema::table('beli_brg', function (Blueprint $table) {
                $table->date('tgl_exp')->nullable()->after('tgl_fak');
            });
        }

        if (! Schema::hasTable('branch_utility_settings')) {
            Schema::create('branch_utility_settings', function (Blueprint $table) {
                $table->id();
                $table->string('kd_toko', 50)->unique();
                $table->string('printer_name', 100)->nullable();
                $table->unsignedTinyInteger('barcode_copies')->default(1);
                $table->string('barcode_printer_type', 20)->default('browser');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_utility_settings');
    }
};
