<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * PHASE 1: OWNER INVITATIONS
         */
        if (!Schema::hasTable('tenant_invitations')) {
            Schema::create('tenant_invitations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('invited_by')->constrained('users')->restrictOnDelete();
                $table->string('email');
                $table->string('role', 40)->default('owner');
                $table->char('token_hash', 64)->unique();
                $table->string('status', 30)->default('pending');
                $table->timestamp('expires_at');
                $table->timestamp('accepted_at')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'email', 'status']);
                $table->index(['status', 'expires_at']);
            });
        }

        /*
         * PHASE 1: DOMAIN VERIFICATION
         */
        if (!Schema::hasTable('domain_verification_challenges')) {
            Schema::create('domain_verification_challenges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('store_domain_id')->unique()->constrained('store_domains')->cascadeOnDelete();
                $table->string('method', 30)->default('dns_txt');
                $table->string('record_name');
                $table->string('record_value', 255);
                $table->timestamp('expires_at');
                $table->timestamp('verified_at')->nullable();
                $table->timestamps();

                $table->index(['method', 'expires_at']);
            });
        }

        /*
         * PHASE 2: SUBSCRIPTION PLANS
         */
            if (!Schema::hasTable('subscription_plans')) {
                Schema::create('subscription_plans', function (Blueprint $table) {
                    $table->id();
                    $table->string('name');
                    $table->string('slug')->unique();
                    $table->text('description')->nullable();
                    $table->unsignedBigInteger('price_monthly')->default(0);
                    $table->unsignedBigInteger('price_yearly')->default(0);
                    $table->char('currency', 3)->default('IDR');
                    $table->unsignedInteger('trial_days')->default(0);
                    $table->boolean('is_active')->default(true);
                    $table->unsignedInteger('sort_order')->default(0);
                    $table->timestamps();

                    $table->index(['is_active', 'sort_order']);
                });
            }

        /*
         * PHASE 2: PLAN FEATURES / ENTITLEMENTS
         */
            if (!Schema::hasTable('plan_features')) {
                Schema::create('plan_features', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('subscription_plan_id')
                    ->constrained('subscription_plans')
                    ->cascadeOnDelete();
                    $table->string('feature_key', 100);
                    $table->string('value_type', 20)->default('integer');
                    $table->string('value')->nullable();
                    $table->timestamps();

                    $table->unique(['subscription_plan_id', 'feature_key']);
                });
            }

        /*
         * PHASE 2: TENANT SUBSCRIPTIONS
         */
            if (!Schema::hasTable('subscriptions')) {
                Schema::create('subscriptions', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
                    $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->restrictOnDelete();
                    $table->string('status', 30)->default('trialing');
                    $table->string('billing_cycle', 20)->default('monthly');
                    $table->char('currency', 3)->default('IDR');
                    $table->unsignedBigInteger('amount')->default(0);
                    $table->timestamp('trial_ends_at')->nullable();
                    $table->timestamp('starts_at');
                    $table->timestamp('current_period_starts_at')->nullable();
                    $table->timestamp('current_period_ends_at')->nullable();
                    $table->timestamp('cancelled_at')->nullable();
                    $table->timestamp('ends_at')->nullable();
                    $table->timestamps();

                    $table->index(['tenant_id', 'status']);
                    $table->index(['status', 'current_period_ends_at']);
                });
            }

        /*
         * PHASE 2: INVOICES
         */
            if (!Schema::hasTable('invoices')) {
                Schema::create('invoices', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
                    $table->foreignId('subscription_id')->constrained()->restrictOnDelete();
                    $table->string('invoice_number')->unique();
                    $table->string('status', 30)->default('draft');
                    $table->char('currency', 3)->default('IDR');
                    $table->unsignedBigInteger('subtotal')->default(0);
                    $table->unsignedBigInteger('tax_amount')->default(0);
                    $table->unsignedBigInteger('total')->default(0);
                    $table->timestamp('issued_at')->nullable();
                    $table->timestamp('due_at')->nullable();
                    $table->timestamp('paid_at')->nullable();
                    $table->timestamps();

                    $table->index(['tenant_id', 'status']);
                    $table->index(['status', 'due_at']);
                });
            }

        /*
         * PHASE 2: INVOICE ITEMS
         */
            if (!Schema::hasTable('invoice_items')) {

                Schema::create('invoice_items', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
                    $table->string('description');
                    $table->unsignedInteger('quantity')->default(1);
                    $table->unsignedBigInteger('unit_price')->default(0);
                    $table->unsignedBigInteger('total')->default(0);
                    $table->timestamps();
                });
            }

            /*
         * PHASE 2: SUBSCRIPTION PAYMENTS
         */
            if (!Schema::hasTable('subscription_payments')) {
                Schema::create('subscription_payments', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
                    $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
                    $table->string('provider', 40);
                    $table->string('provider_reference')->nullable();
                    $table->string('status', 30)->default('pending');
                    $table->char('currency', 3)->default('IDR');
                    $table->unsignedBigInteger('amount');
                    $table->timestamp('paid_at')->nullable();
                    $table->json('metadata')->nullable();
                    $table->timestamps();

                    $table->unique(['provider', 'provider_reference']);
                    $table->index(['tenant_id', 'status']);
                });
            }

        /*
         * PHASE 2: USAGE METERING
         */
            if (!Schema::hasTable('tenant_usages')) {
                Schema::create('tenant_usages', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                    $table->string('feature_key', 100);
                    $table->unsignedBigInteger('used')->default(0);
                    $table->date('period_start');
                    $table->date('period_end');
                    $table->timestamps();

                    $table->unique([
                        'tenant_id',
                        'feature_key',
                        'period_start',
                        'period_end',
                    ], 'tenant_usages_period_unique');

                });
            }

        /*
         * PHASE 3: PLATFORM DAILY ANALYTICS
         */
        if (!Schema::hasTable('platform_daily_metrics')) {
            Schema::create('platform_daily_metrics', function (Blueprint $table) {
                $table->id();
                $table->date('metric_date')->unique();
                $table->unsignedBigInteger('total_tenants')->default(0);
                $table->unsignedBigInteger('active_tenants')->default(0);
                $table->unsignedBigInteger('new_tenants')->default(0);
                $table->unsignedBigInteger('total_subscriptions')->default(0);
                $table->unsignedBigInteger('active_subscriptions')->default(0);
                $table->unsignedBigInteger('revenue')->default(0);
                $table->char('currency', 3)->default('IDR');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_daily_metrics');
        Schema::dropIfExists('tenant_usages');
        Schema::dropIfExists('subscription_payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('domain_verification_challenges');
        Schema::dropIfExists('tenant_invitations');
    }
};