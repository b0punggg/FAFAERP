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
        if (Schema::hasTable('paket_mas')) {
            return;
        }
        
        Schema::create('paket_mas', function (Blueprint $table) {
            $table->id('no_urut');
            $table->string('nm_paket', 255);
            $table->string('kd_toko', 50);
            $table->index('kd_toko');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('paket_mas');
    }
};
