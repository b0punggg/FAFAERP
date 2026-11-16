<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fact_sales_daily', function (Blueprint $table) {
            $table->id();
            $table->date('fact_date');
            $table->foreignId('company_id')->constrained();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->decimal('revenue', 18, 2)->default(0);
            $table->decimal('cogs', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['fact_date', 'company_id', 'branch_id'], 'fact_sales_daily_unique');
        });

        Schema::create('fact_ar_aging', function (Blueprint $table) {
            $table->id();
            $table->date('as_of_date');
            $table->foreignId('company_id')->constrained();
            $table->decimal('bucket_0_30', 18, 2)->default(0);
            $table->decimal('bucket_31_60', 18, 2)->default(0);
            $table->decimal('bucket_61_plus', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('fact_stock_daily', function (Blueprint $table) {
            $table->id();
            $table->date('fact_date');
            $table->foreignId('warehouse_id')->constrained();
            $table->decimal('qty_total', 18, 2)->default(0);
            $table->decimal('value_total', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('fact_ads_roas', function (Blueprint $table) {
            $table->id();
            $table->date('fact_date');
            $table->foreignId('company_id')->constrained();
            $table->decimal('spend', 18, 2)->default(0);
            $table->decimal('attributed_revenue', 18, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('title');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month')->nullable();
            $table->enum('status', ['draft', 'submitted', 'validated', 'approved'])->default('draft');
            $table->json('plan_5w2h')->nullable();
            $table->timestamps();
        });

        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->string('cost_center', 64);
            $table->decimal('planned_amount', 18, 2);
            $table->decimal('actual_amount', 18, 2)->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('fact_ads_roas');
        Schema::dropIfExists('fact_stock_daily');
        Schema::dropIfExists('fact_ar_aging');
        Schema::dropIfExists('fact_sales_daily');
    }
};
