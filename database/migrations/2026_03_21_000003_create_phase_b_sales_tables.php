<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('disc_promo')) {
            Schema::create('disc_promo', function (Blueprint $table) {
                $table->id('no_urut');
                $table->string('no_promo', 50);
                $table->string('nama_promo', 255);
                $table->date('tgl_awal');
                $table->date('tgl_akhir');
                $table->decimal('disc_rupiah', 15, 2)->default(0);
                $table->decimal('disc_persen', 5, 2)->default(0);
                $table->string('by_nama', 255)->nullable();
                $table->string('kd_toko', 50);
                $table->timestamps();
                $table->index(['no_promo', 'kd_toko']);
                $table->index(['tgl_awal', 'tgl_akhir']);
            });
        }

        if (! Schema::hasTable('disc_promo_detail')) {
            Schema::create('disc_promo_detail', function (Blueprint $table) {
                $table->id('no_urut');
                $table->string('no_promo', 50);
                $table->string('kd_brg', 50);
                $table->decimal('disc_rupiah', 15, 2)->default(0);
                $table->decimal('disc_persen', 5, 2)->default(0);
                $table->string('kd_toko', 50);
                $table->index(['no_promo', 'kd_brg', 'kd_toko']);
            });
        }

        if (! Schema::hasTable('sale_drafts')) {
            Schema::create('sale_drafts', function (Blueprint $table) {
                $table->id();
                $table->string('kd_toko', 50);
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('no_fak', 100);
                $table->date('tgl_jual');
                $table->json('payload');
                $table->timestamps();
                $table->unique(['kd_toko', 'no_fak']);
                $table->index(['kd_toko', 'user_id']);
            });
        }

        if (! Schema::hasTable('sale_deletion_history')) {
            Schema::create('sale_deletion_history', function (Blueprint $table) {
                $table->id();
                $table->string('kd_toko', 50)->nullable();
                $table->string('no_fak', 100);
                $table->date('tgl_jual')->nullable();
                $table->decimal('tot_bayar', 15, 2)->default(0);
                $table->json('snapshot')->nullable();
                $table->string('reason', 500)->nullable();
                $table->unsignedBigInteger('voided_by')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sale_deletion_history');
        Schema::dropIfExists('sale_drafts');
        Schema::dropIfExists('disc_promo_detail');
        Schema::dropIfExists('disc_promo');
    }
};
