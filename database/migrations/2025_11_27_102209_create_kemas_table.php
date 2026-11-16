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
        if (Schema::hasTable('kemas')) {
            return;
        }
        
        Schema::create('kemas', function (Blueprint $table) {
            $table->id('no_urut');
            $table->string('nm_sat1', 50);
            $table->string('nm_sat2', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kemas');
    }
};
