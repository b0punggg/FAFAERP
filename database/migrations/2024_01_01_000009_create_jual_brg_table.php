<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('jual_brg')) {
            return;
        }
        
        Schema::create('jual_brg', function (Blueprint $table) {
            $table->id('no_urut');
            $table->string('no_fak', 100);
            $table->date('tgl_jual');
            $table->string('kd_brg', 50);
            $table->string('nm_brg', 255)->nullable();
            $table->integer('kd_sat')->default(1);
            $table->decimal('jml_brg', 15, 2)->default(0);
            $table->decimal('hrg_jual', 15, 2)->default(0);
            $table->decimal('disc', 15, 2)->default(0);
            $table->decimal('ppn', 15, 2)->default(0);
            $table->decimal('tot_jual', 15, 2)->default(0);
            $table->string('kd_toko', 50);
            $table->index(['kd_toko', 'kd_brg']);
            $table->index(['no_fak', 'tgl_jual']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('jual_brg');
    }
};

