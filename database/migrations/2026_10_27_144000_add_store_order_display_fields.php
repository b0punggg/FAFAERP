<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('store_orders') && ! Schema::hasColumn('store_orders', 'nm_sup')) {
            Schema::table('store_orders', function (Blueprint $table) {
                $table->string('nm_sup')->nullable();
            });
        }

        if (Schema::hasTable('store_order_lines') && ! Schema::hasColumn('store_order_lines', 'nm_sat')) {
            Schema::table('store_order_lines', function (Blueprint $table) {
                $table->string('nm_sat', 50)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('store_orders') && Schema::hasColumn('store_orders', 'nm_sup')) {
            Schema::table('store_orders', function (Blueprint $table) {
                $table->dropColumn('nm_sup');
            });
        }
        if (Schema::hasTable('store_order_lines') && Schema::hasColumn('store_order_lines', 'nm_sat')) {
            Schema::table('store_order_lines', function (Blueprint $table) {
                $table->dropColumn('nm_sat');
            });
        }
    }
};
