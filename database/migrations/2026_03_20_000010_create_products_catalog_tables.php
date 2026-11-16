<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('barcode', 50)->nullable()->index();
            $table->string('name');
            $table->integer('kd_kem1')->default(1);
            $table->string('nm_kem1', 50)->nullable();
            $table->decimal('jum_kem1', 15, 2)->default(1);
            $table->integer('kd_kem2')->nullable();
            $table->string('nm_kem2', 50)->nullable();
            $table->decimal('jum_kem2', 15, 2)->nullable();
            $table->integer('kd_kem3')->nullable();
            $table->string('nm_kem3', 50)->nullable();
            $table->decimal('jum_kem3', 15, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('branch_product_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('hrg_jum1', 15, 2)->default(0);
            $table->decimal('hrg_jum2', 15, 2)->default(0);
            $table->decimal('hrg_jum3', 15, 2)->default(0);
            $table->decimal('hrg_beli', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'product_id']);
        });

        Schema::create('inventory_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('qty_on_hand', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_balances');
        Schema::dropIfExists('branch_product_prices');
        Schema::dropIfExists('products');
    }
};
