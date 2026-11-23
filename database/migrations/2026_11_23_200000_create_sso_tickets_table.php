<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sso_tickets')) {
            return;
        }

        Schema::create('sso_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();
            $table->string('username', 100);
            $table->string('direction', 16);
            $table->string('next_path', 255)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sso_tickets');
    }
};
