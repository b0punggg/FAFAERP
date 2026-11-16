<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('toko')) {
            return;
        }
        
        Schema::create('toko', function (Blueprint $table) {
            $table->string('kd_toko', 50)->primary();
            $table->string('nm_toko', 255);
            $table->text('al_toko')->nullable();
            $table->integer('no_urut')->default(0);
        });
    }

    public function down()
    {
        Schema::dropIfExists('toko');
    }
};

