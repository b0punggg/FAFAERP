<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouse_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64);
            $table->char('token_hash', 64)->unique();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('warehouse_sync_events', function (Blueprint $table) {
            $table->id();
            $table->char('idempotency_key', 64)->unique();
            $table->string('doc_type', 32);
            $table->string('doc_no', 64)->nullable();
            $table->json('payload');
            $table->enum('status', ['applied', 'failed'])->default('applied');
            $table->json('result')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_sync_events');
        Schema::dropIfExists('warehouse_api_tokens');
    }
};
