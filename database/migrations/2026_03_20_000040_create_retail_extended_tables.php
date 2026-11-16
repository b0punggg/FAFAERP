<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Legacy DB may already have retur_* tables; extended tables ensured in 000041.
    }

    public function down(): void
    {
        Schema::dropIfExists('hutang_payments');
        Schema::dropIfExists('piutang_payments');
        Schema::dropIfExists('mutasi_gudang');
        Schema::dropIfExists('stok_opname_lines');
        Schema::dropIfExists('stok_opname');
        Schema::dropIfExists('retur_beli');
        Schema::dropIfExists('retur_jual');
        Schema::dropIfExists('kas_entries');
    }
};
