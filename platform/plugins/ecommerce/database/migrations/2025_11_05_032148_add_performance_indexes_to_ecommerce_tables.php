<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('ec_orders', function (Blueprint $table): void {
            if (! Schema::hasIndex('ec_orders', 'ec_orders_status_created_at_index')) {
                $table->index(['status', 'created_at'], 'ec_orders_status_created_at_index');
            }

            if (! Schema::hasIndex('ec_orders', 'ec_orders_user_id_is_finished_index')) {
                $table->index(['user_id', 'is_finished'], 'ec_orders_user_id_is_finished_index');
            }
        });

        Schema::table('ec_order_product', function (Blueprint $table): void {
            if (! Schema::hasIndex('ec_order_product', 'ec_order_product_order_id_product_id_index')) {
                $table->index(['order_id', 'product_id'], 'ec_order_product_order_id_product_id_index');
            }
        });

        Schema::table('ec_product_variations', function (Blueprint $table): void {
            if (! Schema::hasIndex('ec_product_variations', 'ec_product_variations_product_id_index')) {
                $table->index('product_id', 'ec_product_variations_product_id_index');
            }

            if (! Schema::hasIndex('ec_product_variations', 'ec_product_variations_configurable_product_id_index')) {
                $table->index('configurable_product_id', 'ec_product_variations_configurable_product_id_index');
            }
        });

        Schema::table('ec_product_variation_items', function (Blueprint $table): void {
            if (! Schema::hasIndex('ec_product_variation_items', 'ec_product_variation_items_variation_id_attribute_id_index')) {
                $table->index(['variation_id', 'attribute_id'], 'ec_product_variation_items_variation_id_attribute_id_index');
            }
        });

        Schema::table('ec_flash_sale_products', function (Blueprint $table): void {
            if (! Schema::hasIndex('ec_flash_sale_products', 'ec_flash_sale_products_product_id_flash_sale_id_index')) {
                $table->index(['product_id', 'flash_sale_id'], 'ec_flash_sale_products_product_id_flash_sale_id_index');
            }
        });

        Schema::table('ec_products', function (Blueprint $table): void {
            if (! Schema::hasIndex('ec_products', 'ec_products_status_is_variation_index')) {
                $table->index(['status', 'is_variation'], 'ec_products_status_is_variation_index');
            }

            if (! Schema::hasIndex('ec_products', 'ec_products_storehouse_quantity_index')) {
                $table->index(['with_storehouse_management', 'quantity'], 'ec_products_storehouse_quantity_index');
            }
        });

        Schema::table('ec_invoices', function (Blueprint $table): void {
            if (! Schema::hasIndex('ec_invoices', 'ec_invoices_reference_id_reference_type_index')) {
                $table->index(['reference_id', 'reference_type'], 'ec_invoices_reference_id_reference_type_index');
            }
        });

        Schema::table('ec_reviews', function (Blueprint $table): void {
            if (! Schema::hasIndex('ec_reviews', 'ec_reviews_product_id_status_index')) {
                $table->index(['product_id', 'status'], 'ec_reviews_product_id_status_index');
            }

            if (! Schema::hasIndex('ec_reviews', 'ec_reviews_customer_id_status_index')) {
                $table->index(['customer_id', 'status'], 'ec_reviews_customer_id_status_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ec_reviews', function (Blueprint $table): void {
            if (Schema::hasIndex('ec_reviews', 'ec_reviews_product_id_status_index')) {
                $table->dropIndex('ec_reviews_product_id_status_index');
            }

            if (Schema::hasIndex('ec_reviews', 'ec_reviews_customer_id_status_index')) {
                $table->dropIndex('ec_reviews_customer_id_status_index');
            }
        });

        Schema::table('ec_invoices', function (Blueprint $table): void {
            if (Schema::hasIndex('ec_invoices', 'ec_invoices_reference_id_reference_type_index')) {
                $table->dropIndex('ec_invoices_reference_id_reference_type_index');
            }
        });

        Schema::table('ec_products', function (Blueprint $table): void {
            if (Schema::hasIndex('ec_products', 'ec_products_status_is_variation_index')) {
                $table->dropIndex('ec_products_status_is_variation_index');
            }

            if (Schema::hasIndex('ec_products', 'ec_products_storehouse_quantity_index')) {
                $table->dropIndex('ec_products_storehouse_quantity_index');
            }
        });

        Schema::table('ec_flash_sale_products', function (Blueprint $table): void {
            if (Schema::hasIndex('ec_flash_sale_products', 'ec_flash_sale_products_product_id_flash_sale_id_index')) {
                $table->dropIndex('ec_flash_sale_products_product_id_flash_sale_id_index');
            }
        });

        Schema::table('ec_product_variation_items', function (Blueprint $table): void {
            if (Schema::hasIndex('ec_product_variation_items', 'ec_product_variation_items_variation_id_attribute_id_index')) {
                $table->dropIndex('ec_product_variation_items_variation_id_attribute_id_index');
            }
        });

        Schema::table('ec_product_variations', function (Blueprint $table): void {
            if (Schema::hasIndex('ec_product_variations', 'ec_product_variations_product_id_index')) {
                $table->dropIndex('ec_product_variations_product_id_index');
            }

            if (Schema::hasIndex('ec_product_variations', 'ec_product_variations_configurable_product_id_index')) {
                $table->dropIndex('ec_product_variations_configurable_product_id_index');
            }
        });

        Schema::table('ec_order_product', function (Blueprint $table): void {
            if (Schema::hasIndex('ec_order_product', 'ec_order_product_order_id_product_id_index')) {
                $table->dropIndex('ec_order_product_order_id_product_id_index');
            }
        });

        Schema::table('ec_orders', function (Blueprint $table): void {
            if (Schema::hasIndex('ec_orders', 'ec_orders_status_created_at_index')) {
                $table->dropIndex('ec_orders_status_created_at_index');
            }

            if (Schema::hasIndex('ec_orders', 'ec_orders_user_id_is_finished_index')) {
                $table->dropIndex('ec_orders_user_id_is_finished_index');
            }
        });
    }
};
