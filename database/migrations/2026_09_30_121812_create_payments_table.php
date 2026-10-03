<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('provider')->nullable();
            $table->string('reference_id')->nullable();
            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->text('midtrans_snap_token')->nullable();
            $table->string('currency')->default('IDR');
            $table->string('status')->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable()->unique();
            $table->string('payment_type')->nullable();
            $table->string('transaction_status')->default('pending');
            $table->string('fraud_status')->nullable();
            $table->string('status_code')->nullable();
            $table->decimal('gross_amount', 15, 2);
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
