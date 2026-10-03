<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('payment_id')
                ->constrained('payments')
                ->cascadeOnDelete();

            $table->decimal('amount', 15, 2);
            $table->string('currency')->default('IDR');

            $table->string('reason')->nullable();
            $table->string('status')->default('requested');

            $table->string('provider')->nullable();
            $table->string('reference_id')->nullable();

            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
