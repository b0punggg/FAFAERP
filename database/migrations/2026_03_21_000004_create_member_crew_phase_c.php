<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('member')) {
            Schema::create('member', function (Blueprint $table) {
                $table->id('no_urut');
                $table->string('kd_member', 50);
                $table->string('nm_member', 255);
                $table->string('nm_toko', 255)->nullable();
                $table->text('al_member')->nullable();
                $table->string('no_telp', 50)->nullable();
                $table->decimal('poin', 15, 2)->default(0);
                $table->date('tgl_daftar')->nullable();
                $table->string('kd_toko', 50)->nullable();
                $table->index(['kd_member', 'kd_toko']);
            });
        }

        if (! Schema::hasTable('crew')) {
            Schema::create('crew', function (Blueprint $table) {
                $table->id('no_urut');
                $table->string('kd_crew', 50);
                $table->string('nm_crew', 255);
                $table->string('nm_toko', 255)->nullable();
                $table->string('al_crew', 255)->nullable();
                $table->string('no_telp', 50)->nullable();
                $table->boolean('aktif')->default(true);
                $table->string('kd_toko', 50)->nullable();
                $table->index(['kd_crew', 'kd_toko']);
            });
        }

        if (! Schema::hasTable('member_poin_history')) {
            Schema::create('member_poin_history', function (Blueprint $table) {
                $table->id('no_urut');
                $table->string('kd_member', 50);
                $table->string('no_fakjual', 50);
                $table->date('tgl_transaksi');
                $table->decimal('poin_masuk', 15, 2)->default(0);
                $table->decimal('poin_keluar', 15, 2)->default(0);
                $table->decimal('poin_saldo', 15, 2)->default(0);
                $table->text('keterangan')->nullable();
                $table->string('kd_toko', 50);
                $table->timestamp('created_at')->useCurrent();
                $table->index(['kd_member', 'kd_toko']);
            });
        }

        if (Schema::hasTable('jual')) {
            Schema::table('jual', function (Blueprint $table) {
                if (! Schema::hasColumn('jual', 'kd_member')) {
                    $table->string('kd_member', 50)->nullable();
                }
                if (! Schema::hasColumn('jual', 'nm_member')) {
                    $table->string('nm_member', 255)->nullable();
                }
                if (! Schema::hasColumn('jual', 'kd_crew')) {
                    $table->string('kd_crew', 50)->nullable();
                }
                if (! Schema::hasColumn('jual', 'nm_crew')) {
                    $table->string('nm_crew', 255)->nullable();
                }
                if (! Schema::hasColumn('jual', 'poin_earned')) {
                    $table->decimal('poin_earned', 15, 2)->default(0);
                }
                if (! Schema::hasColumn('jual', 'poin_redeem')) {
                    $table->decimal('poin_redeem', 15, 2)->default(0);
                }
            });
        }
    }

    public function down(): void
    {
        // keep legacy-compatible data
    }
};
