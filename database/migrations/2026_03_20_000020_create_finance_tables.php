<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 32);
            $table->string('name');
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->boolean('is_postable')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        Schema::create('coa_group_maps', function (Blueprint $table) {
            $table->id();
            $table->string('group_code', 32);
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['group_code', 'company_id']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
            $table->date('entry_date');
            $table->string('reference_type', 64)->nullable();
            $table->string('reference_id', 64)->nullable();
            $table->text('memo')->nullable();
            $table->enum('status', ['draft', 'posted', 'void'])->default('posted');
            $table->timestamps();

            $table->index(['company_id', 'entry_date']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chart_of_account_id')->constrained();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->string('memo')->nullable();
        });

        Schema::create('inter_company_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_company_id')->constrained('companies');
            $table->foreignId('buyer_company_id')->constrained('companies');
            $table->string('invoice_no', 64);
            $table->date('invoice_date');
            $table->decimal('amount', 18, 2);
            $table->enum('status', ['draft', 'posted', 'paid'])->default('draft');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inter_company_invoices');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('coa_group_maps');
        Schema::dropIfExists('chart_of_accounts');
    }
};
