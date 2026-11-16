<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemakai', function (Blueprint $table) {
            if (! Schema::hasColumn('pemakai', 'company_id')) {
                $table->foreignId('company_id')->nullable()->after('kd_toko')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('pemakai', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('pemakai', function (Blueprint $table) {
            if (Schema::hasColumn('pemakai', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
            if (Schema::hasColumn('pemakai', 'company_id')) {
                $table->dropConstrainedForeignId('company_id');
            }
        });
    }
};
