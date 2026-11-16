<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('disctetap')) {
            return;
        }

        Schema::create('disctetap', function (Blueprint $table) {
            $table->id('no_urut');
            $table->string('kd_brg', 50);
            $table->unsignedInteger('kd_sat')->default(1);
            $table->decimal('hrg_jual', 15, 2)->default(0);
            $table->decimal('lim_jual', 15, 2)->default(0);
            $table->string('kd_toko', 50)->nullable();
            $table->index(['kd_brg', 'kd_sat', 'kd_toko']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disctetap');
    }
};
