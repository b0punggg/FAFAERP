<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('jual')) {
            return;
        }

        $addBayar = ! Schema::hasColumn('jual', 'kd_bayar');
        $addDiscNota = ! Schema::hasColumn('jual', 'disc_nota');
        $addVoucher = ! Schema::hasColumn('jual', 'voucher');
        $addVoucherMember = ! Schema::hasColumn('jual', 'voucher_member');
        $addDiterima = ! Schema::hasColumn('jual', 'diterima');

        if ($addBayar || $addDiscNota || $addVoucher || $addVoucherMember || $addDiterima) {
            Schema::table('jual', function (Blueprint $table) use ($addBayar, $addDiscNota, $addVoucher, $addVoucherMember, $addDiterima) {
                if ($addBayar) {
                    $table->string('kd_bayar', 10)->nullable();
                }
                if ($addDiscNota) {
                    $table->decimal('disc_nota', 15, 2)->default(0);
                }
                if ($addVoucher) {
                    $table->decimal('voucher', 15, 2)->default(0);
                }
                if ($addVoucherMember) {
                    $table->decimal('voucher_member', 15, 2)->default(0);
                }
                if ($addDiterima) {
                    $table->decimal('diterima', 15, 2)->default(0);
                }
            });
        }

        if (Schema::hasTable('jual_brg') && ! Schema::hasColumn('jual_brg', 'nm_sat')) {
            Schema::table('jual_brg', function (Blueprint $table) {
                $table->string('nm_sat', 50)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('jual_brg') && Schema::hasColumn('jual_brg', 'nm_sat')) {
            Schema::table('jual_brg', function (Blueprint $table) {
                $table->dropColumn('nm_sat');
            });
        }

        if (! Schema::hasTable('jual')) {
            return;
        }

        $drop = array_values(array_filter(
            ['kd_bayar', 'disc_nota', 'voucher', 'voucher_member', 'diterima'],
            fn ($column) => Schema::hasColumn('jual', $column)
        ));

        if ($drop) {
            Schema::table('jual', function (Blueprint $table) use ($drop) {
                $table->dropColumn($drop);
            });
        }
    }
};
