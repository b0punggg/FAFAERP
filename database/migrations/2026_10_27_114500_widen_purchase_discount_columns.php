<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE beli MODIFY disc1 DECIMAL(15,2) NOT NULL DEFAULT 0, MODIFY ppn DECIMAL(15,2) NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE beli MODIFY disc1 DECIMAL(5,2) NOT NULL DEFAULT 0, MODIFY ppn DECIMAL(5,2) NOT NULL DEFAULT 0');
    }
};
