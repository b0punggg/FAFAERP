<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('jual')) {
            return;
        }
        
        Schema::create('jual', function (Blueprint $table) {
            $table->id('no_urut');
            $table->string('no_fak', 100);
            $table->date('tgl_jual');
            $table->string('kd_pel', 50)->nullable();
            $table->string('nm_pel', 255)->nullable();
            $table->string('kd_toko', 50);
            $table->decimal('tot_jual', 15, 2)->default(0);
            $table->decimal('disc', 15, 2)->default(0);
            $table->decimal('ppn', 15, 2)->default(0);
            $table->decimal('tot_bayar', 15, 2)->default(0);
            $table->decimal('byr_tunai', 15, 2)->default(0);
            $table->decimal('byr_hutang', 15, 2)->default(0);
            $table->text('ket')->nullable();
            $table->index(['kd_toko', 'tgl_jual']);
            $table->index('no_fak');
        });
    }

    public function down()
    {
        Schema::dropIfExists('jual');
    }
};

