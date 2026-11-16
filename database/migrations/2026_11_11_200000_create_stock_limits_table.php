<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('stock_limits')) {
            return;
        }

        Schema::create('stock_limits', function (Blueprint $table) {
            $table->id();
            $table->string('kd_toko', 50);
            $table->string('kd_brg', 50);
            $table->decimal('qty_min', 15, 2)->nullable();
            $table->decimal('qty_max', 15, 2)->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->unique(['kd_toko', 'kd_brg'], 'uq_stock_limit');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_limits');
    }
};
