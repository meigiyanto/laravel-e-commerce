<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_cart_id_product_id_unique');

            $table->unique(
                ['cart_id', 'product_variant_id'],
                'cart_items_cart_id_variant_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_cart_id_variant_unique');

            $table->unique(
                ['cart_id', 'product_id'],
                'cart_items_cart_id_product_id_unique'
            );
        });
    }
};
