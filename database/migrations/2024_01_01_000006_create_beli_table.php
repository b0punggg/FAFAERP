<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('beli')) {
            return;
        }
        
        Schema::create('beli', function (Blueprint $table) {
            $table->id('no_urut');
            $table->string('no_fak', 100);
            $table->date('tgl_fak');
            $table->string('kd_sup', 50)->nullable();
            $table->string('nm_sup', 255)->nullable();
            $table->string('kd_toko', 50);
            $table->decimal('tot_beli', 15, 2)->default(0);
            $table->decimal('disc1', 5, 2)->default(0);
            $table->decimal('disc2', 15, 2)->default(0);
            $table->decimal('ppn', 5, 2)->default(0);
            $table->decimal('tot_bayar', 15, 2)->default(0);
            $table->text('ket')->nullable();
            $table->index(['kd_toko', 'tgl_fak']);
            $table->index('no_fak');
        });
    }

    public function down()
    {
        Schema::dropIfExists('beli');
    }
};

