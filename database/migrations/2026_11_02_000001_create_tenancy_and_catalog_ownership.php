<?php
// database/migrations/2026_11_02_000001_create_tenancy_and_catalog_ownership.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status', 30)->default('active');
            $table->timestamps();
        });

        Schema::create('tenant_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 40);
            $table->string('status', 30)->default('active');
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id']);
            $table->index(['user_id', 'status']);
        });

        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('status', 30)->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('store_domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('domain')->unique();
            $table->string('type', 30)->default('custom');
            $table->boolean('is_primary')->default(false);
            $table->string('verification_status', 30)->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['store_id', 'is_primary']);
        });

        $ownedTables = [
            'categories',
            'sub_categories',
            'products',
            'product_variants',
            'attributes',
            'inventories',
            'inventory_movements',
            'inventory_reservations',
        ];

        foreach ($ownedTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('tenant_id')->nullable()
                    ->constrained('tenants')->restrictOnDelete();
            });
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_slug_unique');
            $table->unique(['tenant_id', 'slug'], 'categories_tenant_slug_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_slug_unique');
            $table->unique(['tenant_id', 'slug'], 'products_tenant_slug_unique');
        });

        Schema::table('attributes', function (Blueprint $table) {
            $table->dropUnique('attributes_slug_unique');
            $table->unique(['tenant_id', 'slug'], 'attributes_tenant_slug_unique');
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropUnique('sub_categories_category_id_slug_unique');
            $table->unique(
                ['tenant_id', 'category_id', 'slug'],
                'sub_categories_tenant_category_slug_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropUnique('sub_categories_tenant_category_slug_unique');
            $table->unique(['category_id', 'slug'], 'sub_categories_category_id_slug_unique');
        });

        Schema::table('attributes', function (Blueprint $table) {
            $table->dropUnique('attributes_tenant_slug_unique');
            $table->unique('slug', 'attributes_slug_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_tenant_slug_unique');
            $table->unique('slug', 'products_slug_unique');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_tenant_slug_unique');
            $table->unique('slug', 'categories_slug_unique');
        });

        foreach ([
            'inventory_reservations',
            'inventory_movements',
            'inventories',
            'attributes',
            'product_variants',
            'products',
            'sub_categories',
            'categories',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('tenant_id');
            });
        }

        Schema::dropIfExists('store_domains');
        Schema::dropIfExists('stores');
        Schema::dropIfExists('tenant_memberships');
        Schema::dropIfExists('tenants');
    }
};