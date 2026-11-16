<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('digital_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('channel', 64);
            $table->string('external_order_id', 128)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'shipped', 'completed', 'cancelled'])->default('pending');
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('platform', 32);
            $table->decimal('budget', 15, 2);
            $table->decimal('target_roas', 8, 2)->nullable();
            $table->decimal('spend', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('digital_order_id')->constrained();
            $table->string('affiliate_code', 64);
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['accrued', 'paid'])->default('accrued');
            $table->timestamps();
        });

        Schema::create('crm_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('name');
            $table->json('rfm_rules');
            $table->timestamps();
        });

        Schema::create('sfa_outlets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained();
            $table->string('code', 64);
            $table->string('name');
            $table->decimal('credit_limit', 15, 2)->default(0);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('sfa_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sfa_outlet_id')->constrained();
            $table->unsignedBigInteger('sales_user_id');
            $table->timestamp('checked_in_at');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
        });

        Schema::create('sfa_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sfa_outlet_id')->constrained();
            $table->enum('status', ['draft', 'credit_hold', 'approved', 'delivered'])->default('draft');
            $table->decimal('total', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('ar_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sfa_outlet_id')->constrained();
            $table->date('payment_date');
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });

        Schema::create('sales_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ar_settlement_id')->constrained();
            $table->unsignedBigInteger('sales_user_id');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'released'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_commissions');
        Schema::dropIfExists('ar_settlements');
        Schema::dropIfExists('sfa_orders');
        Schema::dropIfExists('sfa_visits');
        Schema::dropIfExists('sfa_outlets');
        Schema::dropIfExists('crm_segments');
        Schema::dropIfExists('affiliate_commissions');
        Schema::dropIfExists('ad_campaigns');
        Schema::dropIfExists('digital_orders');
    }
};
