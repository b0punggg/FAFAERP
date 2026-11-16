<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('mas_brg')) {
            return;
        }
        
        Schema::create('mas_brg', function (Blueprint $table) {
            $table->string('kd_brg', 50)->primary();
            $table->string('kd_bar', 50)->nullable();
            $table->string('nm_brg', 255);
            $table->string('kd_toko', 50);
            $table->integer('kd_kem1')->default(1);
            $table->string('nm_kem1', 50)->nullable();
            $table->decimal('jum_kem1', 15, 2)->default(1);
            $table->decimal('hrg_jum1', 15, 2)->default(0);
            $table->integer('kd_kem2')->nullable();
            $table->string('nm_kem2', 50)->nullable();
            $table->decimal('jum_kem2', 15, 2)->nullable();
            $table->decimal('hrg_jum2', 15, 2)->default(0);
            $table->integer('kd_kem3')->nullable();
            $table->string('nm_kem3', 50)->nullable();
            $table->decimal('jum_kem3', 15, 2)->nullable();
            $table->decimal('hrg_jum3', 15, 2)->default(0);
            $table->decimal('jml_brg', 15, 2)->default(0);
            $table->decimal('brg_msk', 15, 2)->default(0);
            $table->decimal('brg_klr', 15, 2)->default(0);
            $table->index('kd_toko');
            $table->index('nm_brg');
        });
    }

    public function down()
    {
        Schema::dropIfExists('mas_brg');
    }
};

