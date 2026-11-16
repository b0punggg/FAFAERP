<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('digital_imports')) {
            Schema::create('digital_imports', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained();
                $table->string('channel', 64)->default('BigSeller');
                $table->date('period_start')->nullable();
                $table->date('period_end')->nullable();
                $table->string('filename', 180);
                $table->unsignedInteger('rows_count')->default(0);
                $table->unsignedInteger('matched_count')->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('digital_import_rows')) {
            Schema::create('digital_import_rows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('digital_import_id')->constrained('digital_imports')->cascadeOnDelete();
                $table->string('sku', 80);
                $table->string('spu', 80)->nullable();
                $table->string('product_name', 255)->nullable();
                $table->string('kd_brg', 50)->nullable();
                $table->decimal('orders_qty', 15, 2)->default(0);
                $table->decimal('orders_net', 15, 2)->default(0);
                $table->decimal('sku_qty', 15, 2)->default(0);
                $table->decimal('sku_qty_wh', 15, 2)->default(0);
                $table->decimal('sku_qty_wh_net', 15, 2)->default(0);
                $table->decimal('dana_produk', 18, 2)->default(0);
                $table->decimal('dana_valid', 18, 2)->default(0);
                $table->decimal('dana_bersih', 18, 2)->default(0);
                $table->decimal('dana_gudang', 18, 2)->default(0);
                $table->decimal('cancel_orders', 15, 2)->default(0);
                $table->decimal('cancel_amount', 18, 2)->default(0);
                $table->decimal('cancel_rate', 8, 2)->default(0);
                $table->decimal('refund_orders', 15, 2)->default(0);
                $table->decimal('refund_amount', 18, 2)->default(0);
                $table->decimal('refund_qty', 15, 2)->default(0);
                $table->decimal('refund_rate', 8, 2)->default(0);
                $table->decimal('subsidi', 18, 2)->default(0);
                $table->decimal('diskon', 18, 2)->default(0);
                $table->decimal('voucher', 18, 2)->default(0);
                $table->decimal('koin', 18, 2)->default(0);
                $table->decimal('subsidi_penjual', 18, 2)->default(0);
                $table->decimal('diskon_penjual', 18, 2)->default(0);
                $table->decimal('voucher_penjual', 18, 2)->default(0);
                $table->decimal('diskon_marketplace', 18, 2)->default(0);
                $table->decimal('voucher_marketplace', 18, 2)->default(0);
                $table->decimal('stock_total', 15, 2)->default(0);
                $table->decimal('stock_available', 15, 2)->default(0);
                $table->decimal('stock_transit', 15, 2)->default(0);
                $table->decimal('stock_allocated', 15, 2)->default(0);
                $table->decimal('stock_promo', 15, 2)->default(0);
                $table->json('report')->nullable();
                $table->timestamps();
                $table->index('sku');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('digital_import_rows');
        Schema::dropIfExists('digital_imports');
    }
};
