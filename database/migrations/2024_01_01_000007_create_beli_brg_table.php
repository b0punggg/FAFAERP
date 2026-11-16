<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('beli_brg')) {
            return;
        }
        
        Schema::create('beli_brg', function (Blueprint $table) {
            $table->id('no_urut');
            $table->string('no_fak', 100);
            $table->date('tgl_fak');
            $table->string('kd_brg', 50);
            $table->integer('kd_sat')->default(1);
            $table->decimal('jml_brg', 15, 2)->default(0);
            $table->decimal('hrg_beli', 15, 2)->default(0);
            $table->decimal('disc1', 5, 2)->default(0);
            $table->decimal('disc2', 15, 2)->default(0);
            $table->decimal('ppn', 5, 2)->default(0);
            $table->decimal('stok_jual', 15, 2)->default(0);
            $table->string('kd_toko', 50);
            $table->text('ket')->nullable();
            $table->index(['kd_toko', 'kd_brg']);
            $table->index(['no_fak', 'tgl_fak']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('beli_brg');
    }
};

