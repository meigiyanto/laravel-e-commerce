<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

return new class extends Migration
{
    private array $ownedTables = [
        'categories',
        'sub_categories',
        'products',
        'product_variants',
        'attributes',
        'attribute_values',
        'inventories',
        'inventory_movements',
        'inventory_reservations',
    ];

    public function up(): void
    {
        // Tambahkan ownership ke attribute_values dan pivot.
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
        });

        Schema::table('product_variant_attribute_values', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('product_variant_id');
        });

        // Backfill nilai atribut dari pemilik attribute.
        DB::table('attribute_values')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $tenantId = DB::table('attributes')
                    ->where('id', $row->attribute_id)
                    ->value('tenant_id');

                if (! $tenantId) {
                    throw new RuntimeException(
                        "Attribute value {$row->id} references an attribute without tenant."
                    );
                }

                DB::table('attribute_values')
                    ->where('id', $row->id)
                    ->update(['tenant_id' => $tenantId]);
            }
        });

        DB::table('product_variant_attribute_values')
        ->orderBy('product_variant_id')
        ->orderBy('attribute_value_id')
        ->chunk(200, function ($rows) {
            foreach ($rows as $row) {
                $tenantId = DB::table('product_variants')
                    ->where('id', $row->product_variant_id)
                    ->value('tenant_id');
    
                if (! $tenantId) {
                    throw new RuntimeException(
                        "Variant {$row->product_variant_id} has no tenant."
                    );
                }
    
                DB::table('product_variant_attribute_values')
                    ->where('product_variant_id', $row->product_variant_id)
                    ->where('attribute_value_id', $row->attribute_value_id)
                    ->update(['tenant_id' => $tenantId]);
            }
        });

        // Tolak ownership kosong sebelum constraint dipasang.
        foreach ($this->ownedTables as $table) {
            if (DB::table($table)->whereNull('tenant_id')->exists()) {
                throw new RuntimeException(" Cannot enforce tenancy: {$table} still contains NULL tenant_id. ". "Run tenancy:bootstrap-default first and inspect the remaining rows.");
            }
        }

        // Pastikan setiap relasi katalog berada di tenant yang sama.
        $this->assertNoCrossTenantRows(
            'sub_categories',
            'categories',
            'sub_categories.category_id',
            'sub_categories.tenant_id',
            'categories.id',
            'categories.tenant_id'
        );

        $this->assertNoCrossTenantRows(
            'products',
            'categories',
            'products.category_id',
            'products.tenant_id',
            'categories.id',
            'categories.tenant_id'
        );

        $this->assertNoCrossTenantRows(
            'products',
            'sub_categories',
            'products.sub_category_id',
            'products.tenant_id',
            'sub_categories.id',
            'sub_categories.tenant_id'
        );

        $this->assertNoCrossTenantRows(
            'product_variants',
            'products',
            'product_variants.product_id',
            'product_variants.tenant_id',
            'products.id',
            'products.tenant_id'
        );

        $this->assertNoCrossTenantRows(
            'inventories',
            'product_variants',
            'inventories.product_variant_id',
            'inventories.tenant_id',
            'product_variants.id',
            'product_variants.tenant_id'
        );

        foreach (['inventory_movements', 'inventory_reservations'] as $table) {
            $this->assertNoCrossTenantRows(
                $table,
                'product_variants',
                "{$table}.product_variant_id",
                "{$table}.tenant_id",
                'product_variants.id',
                'product_variants.tenant_id'
            );
        }

        $this->assertNoCrossTenantRows(
            'attribute_values',
            'attributes',
            'attribute_values.attribute_id',
            'attribute_values.tenant_id',
            'attributes.id',
            'attributes.tenant_id'
        );

        $this->assertNoCrossTenantRows(
            'product_variant_attribute_values',
            'attribute_values',
            'product_variant_attribute_values.attribute_value_id',
            'product_variant_attribute_values.tenant_id',
            'attribute_values.id',
            'attribute_values.tenant_id'
        );

        $this->assertNoCrossTenantRows(
            'product_variant_attribute_values',
            'product_variants',
            'product_variant_attribute_values.product_variant_id',
            'product_variant_attribute_values.tenant_id',
            'product_variants.id',
            'product_variants.tenant_id'
        );

        // Parent keys untuk composite foreign keys.
        foreach ($this->ownedTables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table) {
                $blueprint->unique(['tenant_id', 'id'],"{$table}_tenant_id_id_unique");
            });
        }

        // Composite foreign keys menjaga relasi tetap satu tenant.
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->unique(['tenant_id', 'category_id', 'id'],'sub_categories_tenant_category_id_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign(['tenant_id', 'category_id'], 'products_tenant_category_fk')
                ->references(['tenant_id', 'id'])->on('categories');
        
            $table->foreign(['tenant_id', 'category_id', 'sub_category_id'], 'products_tenant_sub_category_fk')->references(['tenant_id', 'category_id', 'id'])
                ->on('sub_categories');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreign(['tenant_id', 'product_id'], 'variants_tenant_product_fk')
                ->references(['tenant_id', 'id'])->on('products');
        });

        foreach (['inventories', 'inventory_movements', 'inventory_reservations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreign(['tenant_id', 'product_variant_id'],"{$tableName}_tenant_variant_fk")->references(['tenant_id', 'id'])->on('product_variants');
            });
        }

        Schema::table('attribute_values', function (Blueprint $table) {
            $table->foreign('tenant_id', 'attribute_values_tenant_fk')
                ->references('id')->on('tenants')->restrictOnDelete();
        });
        
        Schema::table('product_variant_attribute_values', function (Blueprint $table) {
            $table->foreign('tenant_id', 'variant_attribute_values_tenant_fk')
                ->references('id')->on('tenants')->restrictOnDelete();
        });

        // SKU berlaku unik per tenant, bukan global.
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variants_sku_unique');
            $table->unique(['tenant_id', 'sku'], 'variants_tenant_sku_unique');
        });

        // Barcode juga dibuat unik per tenant dalam rancangan ini.
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('product_variants_barcode_unique');
            $table->unique(['tenant_id', 'barcode'], 'variants_tenant_barcode_unique');
        });
    }

    private function assertNoCrossTenantRows(
        string $childTable,
        string $parentTable,
        string $childForeignKey,
        string $childTenant,
        string $parentKey,
        string $parentTenant
    ): void {
        $childColumn = substr($childForeignKey, strlen($childTable) + 1);
        $childTenantColumn = substr($childTenant, strlen($childTable) + 1);
        $parentColumn = substr($parentKey, strlen($parentTable) + 1);
        $parentTenantColumn = substr($parentTenant, strlen($parentTable) + 1);
    
        $invalid = DB::table("{$childTable} as child")
            ->join("{$parentTable} as parent", "child.{$childColumn}", '=', "parent.{$parentColumn}")
            ->whereColumn("child.{$childTenantColumn}", '!=', "parent.{$parentTenantColumn}")
            ->exists();
    
        if ($invalid) {
            throw new RuntimeException(
                "Cross-tenant relation detected between {$childTable} and {$parentTable}."
            );
        }
    }

    public function down(): void
    {
        // Rollback constraint dilakukan eksplisit agar tidak menghapus data.
        Schema::table('product_variant_attribute_values', function (Blueprint $table) {
            $table->dropForeign('variant_attribute_values_tenant_variant_fk');
            $table->dropForeign('variant_attribute_values_tenant_value_fk');
        });

        foreach (['inventories', 'inventory_movements', 'inventory_reservations'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign("{$tableName}_tenant_variant_fk");
            });
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropForeign('variants_tenant_product_fk');
            $table->dropUnique('variants_tenant_sku_unique');
            $table->dropUnique('variants_tenant_barcode_unique');
            $table->unique('sku', 'product_variants_sku_unique');
            $table->unique('barcode', 'product_variants_barcode_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_tenant_category_fk');
            $table->dropForeign('products_tenant_sub_category_fk');
            $table->dropUnique('products_tenant_category_id_unique');
        });

        foreach ($this->ownedTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropUnique("{$tableName}_tenant_id_id_unique");
            });
        }

        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};