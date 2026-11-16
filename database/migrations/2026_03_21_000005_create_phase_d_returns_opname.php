<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_returns')) {
            Schema::create('sales_returns', function (Blueprint $table) {
                $table->id();
                $table->string('no_retur', 100);
                $table->string('no_fak', 100);
                $table->date('tgl_retur');
                $table->string('kd_toko', 50)->index();
                $table->decimal('total', 15, 2)->default(0);
                $table->text('ket')->nullable();
                $table->timestamps();
                $table->unique(['kd_toko', 'no_retur']);
            });
        }

        if (! Schema::hasTable('sales_return_lines')) {
            Schema::create('sales_return_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sales_return_id')->constrained('sales_returns')->cascadeOnDelete();
                $table->string('kd_brg', 50);
                $table->string('nm_brg', 255)->nullable();
                $table->unsignedInteger('kd_sat')->default(1);
                $table->decimal('qty', 15, 4);
                $table->decimal('hrg_jual', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
            });
        }

        if (! Schema::hasTable('purchase_returns')) {
            Schema::create('purchase_returns', function (Blueprint $table) {
                $table->id();
                $table->string('no_retur', 100);
                $table->string('no_fak', 100);
                $table->date('tgl_retur');
                $table->string('kd_toko', 50)->index();
                $table->string('kd_sup', 50)->nullable();
                $table->decimal('total', 15, 2)->default(0);
                $table->text('ket')->nullable();
                $table->timestamps();
                $table->unique(['kd_toko', 'no_retur']);
            });
        }

        if (! Schema::hasTable('purchase_return_lines')) {
            Schema::create('purchase_return_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('purchase_return_id')->constrained('purchase_returns')->cascadeOnDelete();
                $table->string('kd_brg', 50);
                $table->unsignedInteger('kd_sat')->default(1);
                $table->decimal('qty', 15, 4);
                $table->decimal('hrg_beli', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
            });
        }

        if (Schema::hasTable('stok_opname') && ! Schema::hasColumn('stok_opname', 'notes')) {
            Schema::table('stok_opname', function (Blueprint $table) {
                $table->string('notes', 255)->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_return_lines');
        Schema::dropIfExists('purchase_returns');
        Schema::dropIfExists('sales_return_lines');
        Schema::dropIfExists('sales_returns');
    }
};
