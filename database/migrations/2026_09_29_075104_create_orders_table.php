<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            // User yang melakukan pemesanan
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Nomor order yang dapat dilihat pelanggan
            $table->string('order_number')->unique();

            // Status pesanan
            $table->enum('status', [
                'pending',
                'processing',
                'shipped',
                'completed',
                'cancelled',
            ])->default('pending');

            // Status pembayaran
            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'refunded',
            ])->default('pending');

            // Metode pembayaran
            $table->string('payment_method')->nullable();

            // Informasi pengiriman
            $table->string('shipping_name');
            $table->string('shipping_phone');
            $table->text('shipping_address');
            $table->string('shipping_city');
            $table->string('shipping_province');
            $table->string('shipping_postal_code');

            // Ringkasan harga
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            // Catatan pelanggan
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
