<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $ownedTables = [
        'categories',
        'sub_categories',
        'products',
        'product_variants',
        'attributes',
        'inventories',
        'inventory_movements',
        'inventory_reservations',
        'attribute_values',
        'product_variant_attribute_values',
    ];

    /**
     * Nama FK berdasarkan migration tenancy dan integrity asli.
     */
    private array $tenantForeignKeys = [
        'categories' => [
            'categories_tenant_id_foreign',
        ],
        'sub_categories' => [
            'sub_categories_tenant_id_foreign',
            'sub_categories_tenant_category_fk',
        ],
        'products' => [
            'products_tenant_id_foreign',
            'products_tenant_category_fk',
            'products_tenant_sub_category_fk',
        ],
        'product_variants' => [
            'product_variants_tenant_id_foreign',
            'variants_tenant_product_fk',
        ],
        'attributes' => [
            'attributes_tenant_id_foreign',
        ],
        'inventories' => [
            'inventories_tenant_id_foreign',
            'inventories_tenant_variant_fk',
        ],
        'inventory_movements' => [
            'inventory_movements_tenant_id_foreign',
            'inventory_movements_tenant_variant_fk',
        ],
        'inventory_reservations' => [
            'inventory_reservations_tenant_id_foreign',
            'inventory_reservations_tenant_variant_fk',
        ],
        'attribute_values' => [
            'attribute_values_tenant_fk',
            'attribute_values_tenant_attribute_fk',
        ],
        'product_variant_attribute_values' => [
            'variant_attribute_values_tenant_fk',
            'variant_attribute_values_tenant_variant_fk',
            'variant_attribute_values_tenant_value_fk',
        ],
    ];

    public function up(): void
    {
        $this->assertMySql();
        $this->assertNoGlobalDuplicates();

        $this->dropTenantForeignKeys();
        $this->makeTenantColumnsNullable();
        $this->restoreTenantForeignKeys();
        $this->restoreGlobalUniqueIndexes();
    }

    private function assertMySql(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException('This migration only supports MySQL.');
        }
    }

    private function assertNoGlobalDuplicates(): void
    {
        $checks = [
            'categories' => ['slug'],
            'sub_categories' => ['category_id', 'slug'],
            'products' => ['slug'],
            'attributes' => ['slug'],
            'product_variants' => ['sku'],
        ];

        foreach ($checks as $table => $columns) {
            $this->assertUniqueValues($table, $columns);
        }

        $this->assertUniqueValues('product_variants', ['barcode']);
    }

    private function assertUniqueValues(string $table, array $columns): void
    {
        $query = DB::table($table)
            ->select($columns)
            ->selectRaw('COUNT(*) AS aggregate');

        foreach ($columns as $column) {
            $query->whereNotNull($column);
        }

        if ($query->groupBy($columns)->havingRaw('COUNT(*) > 1')->exists()) {
            throw new RuntimeException(
                "Duplicate values prevent restoring global unique index on {$table}."
            );
        }
    }

    private function dropTenantForeignKeys(): void
    {
        foreach ($this->tenantForeignKeys as $tableName => $constraints) {
            Schema::table($tableName, function (Blueprint $table) use ($constraints) {
                foreach ($constraints as $constraint) {
                    $table->dropForeign($constraint);
                }
            });
        }
    }

    private function makeTenantColumnsNullable(): void
    {
        foreach ($this->ownedTables as $tableName) {
            DB::statement(
                "ALTER TABLE `{$tableName}` MODIFY COLUMN `tenant_id` BIGINT UNSIGNED NULL"
            );
        }
    }

    private function restoreTenantForeignKeys(): void
    {
        $foreignKeys = [
            ['categories', 'categories_tenant_id_foreign',
                ['tenant_id'], 'tenants', ['id']],

            ['sub_categories', 'sub_categories_tenant_id_foreign',
                ['tenant_id'], 'tenants', ['id']],
            ['sub_categories', 'sub_categories_tenant_category_fk',
                ['tenant_id', 'category_id'], 'categories', ['tenant_id', 'id']],

            ['products', 'products_tenant_id_foreign',
                ['tenant_id'], 'tenants', ['id']],
            ['products', 'products_tenant_category_fk',
                ['tenant_id', 'category_id'], 'categories', ['tenant_id', 'id']],
            ['products', 'products_tenant_sub_category_fk',
                ['tenant_id', 'category_id', 'sub_category_id'],
                'sub_categories', ['tenant_id', 'category_id', 'id']],

            ['product_variants', 'product_variants_tenant_id_foreign',
                ['tenant_id'], 'tenants', ['id']],
            ['product_variants', 'variants_tenant_product_fk',
                ['tenant_id', 'product_id'], 'products', ['tenant_id', 'id']],

            ['attributes', 'attributes_tenant_id_foreign',
                ['tenant_id'], 'tenants', ['id']],

            ['inventories', 'inventories_tenant_id_foreign',
                ['tenant_id'], 'tenants', ['id']],
            ['inventories', 'inventories_tenant_variant_fk',
                ['tenant_id', 'product_variant_id'],
                'product_variants', ['tenant_id', 'id']],

            ['inventory_movements', 'inventory_movements_tenant_id_foreign',
                ['tenant_id'], 'tenants', ['id']],
            ['inventory_movements', 'inventory_movements_tenant_variant_fk',
                ['tenant_id', 'product_variant_id'],
                'product_variants', ['tenant_id', 'id']],

            ['inventory_reservations', 'inventory_reservations_tenant_id_foreign',
                ['tenant_id'], 'tenants', ['id']],
            ['inventory_reservations', 'inventory_reservations_tenant_variant_fk',
                ['tenant_id', 'product_variant_id'],
                'product_variants', ['tenant_id', 'id']],

            ['attribute_values', 'attribute_values_tenant_fk',
                ['tenant_id'], 'tenants', ['id']],
            ['attribute_values', 'attribute_values_tenant_attribute_fk',
                ['tenant_id', 'attribute_id'], 'attributes', ['tenant_id', 'id']],

            ['product_variant_attribute_values', 'variant_attribute_values_tenant_fk',
                ['tenant_id'], 'tenants', ['id']],
            ['product_variant_attribute_values', 'variant_attribute_values_tenant_variant_fk',
                ['tenant_id', 'product_variant_id'],
                'product_variants', ['tenant_id', 'id']],
            ['product_variant_attribute_values', 'variant_attribute_values_tenant_value_fk',
                ['tenant_id', 'attribute_value_id'],
                'attribute_values', ['tenant_id', 'id']],
        ];

        foreach ($foreignKeys as [
            $tableName,
            $constraint,
            $columns,
            $parentTable,
            $parentColumns,
        ]) {
            Schema::table($tableName, function (Blueprint $table) use (
                $constraint,
                $columns,
                $parentTable,
                $parentColumns
            ) {
                $table->foreign($columns, $constraint)
                    ->references($parentColumns)
                    ->on($parentTable)
                    ->restrictOnDelete();
            });
        }
    }

    private function restoreGlobalUniqueIndexes(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_tenant_slug_unique');
            $table->unique('slug', 'categories_slug_unique');
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropUnique('sub_categories_tenant_category_slug_unique');
            $table->unique(
                ['category_id', 'slug'],
                'sub_categories_category_id_slug_unique'
            );
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_tenant_slug_unique');
            $table->unique('slug', 'products_slug_unique');
        });

        Schema::table('attributes', function (Blueprint $table) {
            $table->dropUnique('attributes_tenant_slug_unique');
            $table->unique('slug', 'attributes_slug_unique');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('variants_tenant_sku_unique');
            $table->dropUnique('variants_tenant_barcode_unique');

            $table->unique('sku', 'product_variants_sku_unique');
            $table->unique('barcode', 'product_variants_barcode_unique');
        });
    }

    public function down(): void
    {
        $this->assertMySql();
        $this->assertNoNullTenantIds();

        $this->dropTenantForeignKeys();
        $this->makeTenantColumnsNotNullable();
        $this->restoreTenantForeignKeys();
        $this->restoreTenantUniqueIndexes();
    }

    private function assertNoNullTenantIds(): void
    {
        foreach ($this->ownedTables as $tableName) {
            if (DB::table($tableName)->whereNull('tenant_id')->exists()) {
                throw new RuntimeException(
                    "Cannot rollback: {$tableName} contains NULL tenant_id."
                );
            }
        }
    }

    private function makeTenantColumnsNotNullable(): void
    {
        foreach ($this->ownedTables as $tableName) {
            DB::statement(
                "ALTER TABLE `{$tableName}` MODIFY COLUMN `tenant_id` BIGINT UNSIGNED NOT NULL"
            );
        }
    }

    private function restoreTenantUniqueIndexes(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique('categories_slug_unique');
            $table->unique(
                ['tenant_id', 'slug'],
                'categories_tenant_slug_unique'
            );
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropUnique('sub_categories_category_id_slug_unique');
            $table->unique(
                ['tenant_id', 'category_id', 'slug'],
                'sub_categories_tenant_category_slug_unique'
            );
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_slug_unique');
            $table->unique(
                ['tenant_id', 'slug'],
                'products_tenant_slug_unique'
            );
        });

        Schema::table('attributes', function (Blueprint $table) {
            $table->dropUnique('attributes_slug_unique');
            $table->unique(
                ['tenant_id', 'slug'],
                'attributes_tenant_slug_unique'
            );
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variants_sku_unique');
            $table->dropUnique('product_variants_barcode_unique');

            $table->unique(
                ['tenant_id', 'sku'],
                'variants_tenant_sku_unique'
            );

            $table->unique(
                ['tenant_id', 'barcode'],
                'variants_tenant_barcode_unique'
            );
        });
    }
};
