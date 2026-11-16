<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('beli')) {
            return;
        }

        $addBayar = ! Schema::hasColumn('beli', 'kd_bayar');
        $addTunai = ! Schema::hasColumn('beli', 'byr_tunai');
        $addHutang = ! Schema::hasColumn('beli', 'byr_hutang');

        if (! $addBayar && ! $addTunai && ! $addHutang) {
            return;
        }

        Schema::table('beli', function (Blueprint $table) use ($addBayar, $addTunai, $addHutang) {
            if ($addBayar) {
                $table->string('kd_bayar', 10)->nullable();
            }
            if ($addTunai) {
                $table->decimal('byr_tunai', 15, 2)->default(0);
            }
            if ($addHutang) {
                $table->decimal('byr_hutang', 15, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('beli')) {
            return;
        }

        Schema::table('beli', function (Blueprint $table) {
            foreach (['kd_bayar', 'byr_tunai', 'byr_hutang'] as $column) {
                if (Schema::hasColumn('beli', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
