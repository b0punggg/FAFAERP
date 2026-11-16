<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('operating_expenses')) {
            Schema::create('operating_expenses', function (Blueprint $table) {
                $table->id();
                $table->string('kd_toko', 50)->index();
                $table->date('tgl_biaya');
                $table->decimal('nominal', 15, 2);
                $table->string('ket_biaya')->nullable();
                $table->unsignedSmallInteger('id_jenis')->default(11);
                $table->string('nm_jenis', 100)->nullable();
                $table->unsignedBigInteger('journal_entry_id')->nullable();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('kas_entries') && ! Schema::hasColumn('kas_entries', 'post_to_gl')) {
            Schema::table('kas_entries', function (Blueprint $table) {
                $table->boolean('post_to_gl')->default(false)->after('description');
                $table->unsignedBigInteger('journal_entry_id')->nullable()->after('post_to_gl');
            });
        }

        if (! Schema::hasTable('store_orders')) {
            Schema::create('store_orders', function (Blueprint $table) {
                $table->id();
                $table->string('no_po', 50);
                $table->string('kd_toko', 50)->index();
                $table->string('kd_sup', 50)->default('');
                $table->date('tgl_po');
                $table->string('ket')->nullable();
                $table->decimal('tot_pesan', 15, 2)->default(0);
                $table->string('status', 20)->default('draft');
                $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
                $table->timestamps();
                $table->unique(['kd_toko', 'no_po']);
            });
        }

        if (! Schema::hasTable('store_order_lines')) {
            Schema::create('store_order_lines', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_order_id')->constrained()->cascadeOnDelete();
                $table->string('kd_brg', 50);
                $table->string('nm_brg')->nullable();
                $table->unsignedInteger('kd_sat')->default(1);
                $table->decimal('qty_pesan', 15, 2);
                $table->decimal('hrg_beli', 15, 2)->default(0);
                $table->decimal('jumlah', 15, 2)->default(0);
            });
        }

        if (Schema::hasTable('piutang_payments') && ! Schema::hasColumn('piutang_payments', 'journal_entry_id')) {
            Schema::table('piutang_payments', function (Blueprint $table) {
                $table->unsignedBigInteger('journal_entry_id')->nullable()->after('amount');
            });
        }

        if (Schema::hasTable('hutang_payments') && ! Schema::hasColumn('hutang_payments', 'journal_entry_id')) {
            Schema::table('hutang_payments', function (Blueprint $table) {
                $table->unsignedBigInteger('journal_entry_id')->nullable()->after('amount');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_order_lines');
        Schema::dropIfExists('store_orders');
        Schema::dropIfExists('operating_expenses');
    }
};
