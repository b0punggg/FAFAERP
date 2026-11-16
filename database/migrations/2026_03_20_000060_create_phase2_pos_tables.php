<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained();
            $table->date('settlement_date');
            $table->decimal('pos_cash_total', 15, 2)->default(0);
            $table->decimal('physical_cash', 15, 2)->default(0);
            $table->decimal('qris_total', 15, 2)->default(0);
            $table->decimal('edc_total', 15, 2)->default(0);
            $table->enum('status', ['draft', 'verified', 'approved'])->default('draft');
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'settlement_date']);
        });

        Schema::create('pos_sync_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained();
            $table->uuid('idempotency_key')->unique();
            $table->string('event_type', 64);
            $table->json('payload');
            $table->enum('status', ['pending', 'applied', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sync_events');
        Schema::dropIfExists('daily_settlements');
    }
};
