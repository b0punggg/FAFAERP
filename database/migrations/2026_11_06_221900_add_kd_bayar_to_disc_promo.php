<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('disc_promo') || Schema::hasColumn('disc_promo', 'kd_bayar')) {
            return;
        }

        Schema::table('disc_promo', function (Blueprint $table) {
            $table->string('kd_bayar', 10)->nullable()->after('disc_persen');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('disc_promo') || ! Schema::hasColumn('disc_promo', 'kd_bayar')) {
            return;
        }

        Schema::table('disc_promo', function (Blueprint $table) {
            $table->dropColumn('kd_bayar');
        });
    }
};
