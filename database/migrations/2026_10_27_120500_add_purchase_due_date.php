<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('beli') || Schema::hasColumn('beli', 'tgl_jt')) {
            return;
        }

        Schema::table('beli', function (Blueprint $table) {
            $table->date('tgl_jt')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('beli') || ! Schema::hasColumn('beli', 'tgl_jt')) {
            return;
        }

        Schema::table('beli', function (Blueprint $table) {
            $table->dropColumn('tgl_jt');
        });
    }
};
