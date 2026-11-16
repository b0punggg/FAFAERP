<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stok_opname_lines') && ! Schema::hasColumn('stok_opname_lines', 'ket')) {
            Schema::table('stok_opname_lines', function (Blueprint $table) {
                $table->string('ket', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stok_opname_lines') && Schema::hasColumn('stok_opname_lines', 'ket')) {
            Schema::table('stok_opname_lines', function (Blueprint $table) {
                $table->dropColumn('ket');
            });
        }
    }
};
