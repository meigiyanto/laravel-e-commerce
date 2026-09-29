<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            // Relasi ke order
            $table->foreignId('order_id')
                ->constrained()
                ->cascadeOnDelete();

            // Relasi ke product
            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            // Snapshot data produk ketika order dibuat
            $table->string('product_name');
            $table->decimal('price', 15, 2);

            // Jumlah produk
            $table->unsignedInteger('quantity')->default(1);

            // Total item = price × quantity
            $table->decimal('subtotal', 15, 2);

            $table->timestamps();

            // Mencegah produk yang sama muncul
            // berkali-kali dalam satu order
            $table->unique(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
