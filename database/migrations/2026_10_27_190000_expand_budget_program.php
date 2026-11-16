<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('budgets')) {
            $missing = [];
            foreach (['period_start', 'period_end', 'tempat'] as $column) {
                if (! Schema::hasColumn('budgets', $column)) {
                    $missing[] = $column;
                }
            }
            if ($missing !== []) {
                Schema::table('budgets', function (Blueprint $table) use ($missing) {
                    if (in_array('period_start', $missing, true)) {
                        $table->date('period_start')->nullable();
                    }
                    if (in_array('period_end', $missing, true)) {
                        $table->date('period_end')->nullable();
                    }
                    if (in_array('tempat', $missing, true)) {
                        $table->string('tempat', 100)->nullable();
                    }
                });
            }
        }

        if (Schema::hasTable('budget_lines')) {
            $missing = [];
            foreach (['kegiatan', 'uraian', 'qty', 'unit_price'] as $column) {
                if (! Schema::hasColumn('budget_lines', $column)) {
                    $missing[] = $column;
                }
            }
            if ($missing !== []) {
                Schema::table('budget_lines', function (Blueprint $table) use ($missing) {
                    if (in_array('kegiatan', $missing, true)) {
                        $table->string('kegiatan', 100)->nullable();
                    }
                    if (in_array('uraian', $missing, true)) {
                        $table->string('uraian', 255)->nullable();
                    }
                    if (in_array('qty', $missing, true)) {
                        $table->decimal('qty', 15, 2)->default(0);
                    }
                    if (in_array('unit_price', $missing, true)) {
                        $table->decimal('unit_price', 18, 2)->default(0);
                    }
                });
            }
        }

        if (! Schema::hasTable('budget_notes')) {
            Schema::create('budget_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('budget_id')->constrained('budgets')->cascadeOnDelete();
                $table->string('jenis', 20);
                $table->date('tgl');
                $table->string('pelanggan', 150)->nullable();
                $table->text('hasil');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_notes');
    }
};
