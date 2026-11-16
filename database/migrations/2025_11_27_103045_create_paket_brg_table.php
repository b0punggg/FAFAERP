<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('paket_brg')) {
            return;
        }
        
        Schema::create('paket_brg', function (Blueprint $table) {
            $table->id('no_urut');
            $table->unsignedBigInteger('kd_paket');
            $table->string('kd_brg', 50);
            $table->decimal('qty_brg', 15, 2)->default(0);
            $table->integer('kd_sat')->default(1);
            $table->string('kd_toko', 50);
            $table->index(['kd_paket', 'kd_brg', 'kd_toko']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paket_brg');
    }
};
