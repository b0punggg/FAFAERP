<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('pelanggan')) {
            return;
        }
        
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->string('kd_pel', 50)->primary();
            $table->string('nm_pel', 255);
            $table->text('al_pel')->nullable();
            $table->string('telp_pel', 50)->nullable();
            $table->string('kd_toko', 50)->nullable();
            $table->index('kd_toko');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pelanggan');
    }
};

