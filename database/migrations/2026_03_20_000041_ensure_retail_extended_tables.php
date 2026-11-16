<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kas_entries')) {
            Schema::create('kas_entries', function (Blueprint $table) {
                $table->id();
                $table->string('kd_toko', 50)->index();
                $table->date('tgl');
                $table->enum('direction', ['in', 'out']);
                $table->decimal('amount', 15, 2);
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('piutang_payments')) {
            Schema::create('piutang_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sale_id');
                $table->date('tgl_bayar');
                $table->decimal('amount', 15, 2);
                $table->string('kd_toko', 50);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('hutang_payments')) {
            Schema::create('hutang_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('purchase_id');
                $table->date('tgl_bayar');
                $table->decimal('amount', 15, 2);
                $table->string('kd_toko', 50);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stok_opname')) {
            Schema::create('stok_opname', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained();
                $table->date('tgl_opname');
                $table->enum('status', ['draft', 'posted'])->default('draft');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('stok_opname_lines')) {
            Schema::create('stok_opname_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stok_opname_id')->constrained('stok_opname')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained();
                $table->decimal('qty_system', 15, 2);
                $table->decimal('qty_physical', 15, 2);
            });
        }

        if (! Schema::hasTable('mutasi_gudang')) {
            Schema::create('mutasi_gudang', function (Blueprint $table) {
                $table->id();
                $table->foreignId('warehouse_id')->constrained();
                $table->date('tgl');
                $table->enum('direction', ['in', 'out']);
                $table->foreignId('product_id')->constrained();
                $table->decimal('qty', 15, 2);
                $table->string('reference')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        //
    }
};
