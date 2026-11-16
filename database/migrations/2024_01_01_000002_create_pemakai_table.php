<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('pemakai')) {
            return;
        }
        
        Schema::create('pemakai', function (Blueprint $table) {
            $table->id('id_user');
            $table->string('nm_user', 100)->unique();
            $table->string('pass', 255);
            $table->string('otoritas', 50)->default('user');
            $table->string('foto', 255)->nullable();
            $table->string('kd_toko', 50)->nullable();
            $table->index('kd_toko');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pemakai');
    }
};

