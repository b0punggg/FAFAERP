<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('supplier')) {
            return;
        }
        
        Schema::create('supplier', function (Blueprint $table) {
            $table->string('kd_sup', 50)->primary();
            $table->string('nm_sup', 255);
            $table->text('al_sup')->nullable();
            $table->string('telp_sup', 50)->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('supplier');
    }
};

