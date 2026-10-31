<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_variant_id')
                ->constrained('product_variants')
                ->restrictOnDelete();

            $table->string('idempotency_key', 191)->unique();
            $table->string('reference_type', 100);
            $table->unsignedBigInteger('reference_id');
            $table->unsignedInteger('quantity');

            $table->string('status', 20)->default('reserved');
            $table->timestamp('committed_at')->nullable();
            $table->timestamp('released_at')->nullable();

            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index(['product_variant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_reservations');
    }
};
