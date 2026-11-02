<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel yang sudah memiliki tenant_id dari migration tenancy.
     */
    private array $ownedTables = [
        'categories',
        'sub_categories',
        'products',
        'product_variants',
        'attributes',
        'inventories',
        'inventory_movements',
        'inventory_reservations',
    ];

    /**
     * Jalankan migration.
     */
    public function up(): void
    {
        $this->addMissingTenantColumns();
        $this->backfillAttributeValueTenants();
        $this->backfillPivotTenants();
        $this->assertOwnershipIsComplete();
        $this->assertExistingRelationsAreValid();
        $this->makeTenantColumnsNotNullable();
        $this->addParentUniqueConstraints();
        $this->addCompositeForeignKeys();
        $this->replaceGlobalVariantUniqueConstraints();
        $this->restoreInventoryIntegrityTriggers();
    }

    /**
     * Tambahkan tenant_id pada tabel yang sebelumnya belum memilikinya.
     */
    private function addMissingTenantColumns(): void
    {
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable();
        });

        Schema::table('product_variant_attribute_values', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable();
        });
    }

    /**
     * Backfill tenant_id attribute_values berdasarkan attributes.
     */
    private function backfillAttributeValueTenants(): void
    {
        DB::table('attribute_values')
            ->select(['id', 'attribute_id'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $tenantId = DB::table('attributes')
                        ->where('id', $row->attribute_id)
                        ->value('tenant_id');

                    if ($tenantId === null) {
                        throw new \RuntimeException(
                            "Attribute value {$row->id} references an attribute without tenant_id."
                        );
                    }

                    DB::table('attribute_values')
                        ->where('id', $row->id)
                        ->update(['tenant_id' => $tenantId]);
                }
            });
    }

    /**
     * Backfill tenant_id pivot berdasarkan product_variants.
     */
    private function backfillPivotTenants(): void
    {
        DB::table('product_variant_attribute_values')
            ->select(['product_variant_id', 'attribute_value_id'])
            ->orderBy('product_variant_id')
            ->orderBy('attribute_value_id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    $tenantId = DB::table('product_variants')
                        ->where('id', $row->product_variant_id)
                        ->value('tenant_id');

                    if ($tenantId === null) {
                        throw new \RuntimeException(
                            "Variant {$row->product_variant_id} has no tenant_id."
                        );
                    }

                    DB::table('product_variant_attribute_values')
                        ->where('product_variant_id', $row->product_variant_id)
                        ->where('attribute_value_id', $row->attribute_value_id)
                        ->update(['tenant_id' => $tenantId]);
                }
            });
    }

    /**
     * Pastikan semua data sudah mempunyai tenant_id.
     */
    private function assertOwnershipIsComplete(): void
    {
        $tables = array_merge(
            $this->ownedTables,
            ['attribute_values', 'product_variant_attribute_values']
        );

        foreach ($tables as $table) {
            if (DB::table($table)->whereNull('tenant_id')->exists()) {
                throw new \RuntimeException(
                    "Cannot enforce tenant integrity: {$table} still contains NULL tenant_id. "
                    . "Run tenancy:bootstrap-default and inspect the remaining records."
                );
            }
        }
    }

    /**
     * Pastikan relasi yang sudah ada tidak melintasi tenant.
     */
    private function assertExistingRelationsAreValid(): void
    {
        $this->assertSameTenant(
            'sub_categories',
            'categories',
            'category_id'
        );

        $this->assertSameTenant(
            'products',
            'categories',
            'category_id'
        );

        $this->assertSameTenant(
            'products',
            'sub_categories',
            'sub_category_id'
        );

        $invalidProductCategory = DB::table('products as p')
            ->join('sub_categories as sc', 'sc.id', '=', 'p.sub_category_id')
            ->whereColumn('p.category_id', '!=', 'sc.category_id')
            ->exists();

        if ($invalidProductCategory) {
            throw new \RuntimeException(
                'Invalid product relation: subcategory does not belong to the selected category.'
            );
        }

        $this->assertSameTenant(
            'product_variants',
            'products',
            'product_id'
        );

        foreach ([
            'inventories',
            'inventory_movements',
            'inventory_reservations',
        ] as $table) {
            $this->assertSameTenant(
                $table,
                'product_variants',
                'product_variant_id'
            );
        }

        $this->assertSameTenant(
            'attribute_values',
            'attributes',
            'attribute_id'
        );

        $this->assertSameTenant(
            'product_variant_attribute_values',
            'product_variants',
            'product_variant_id'
        );

        $this->assertSameTenant(
            'product_variant_attribute_values',
            'attribute_values',
            'attribute_value_id'
        );
    }

    /**
     * Memeriksa apakah relasi child dan parent memiliki tenant yang sama.
     */
    private function assertSameTenant(
        string $childTable,
        string $parentTable,
        string $foreignKey
    ): void {
        $invalid = DB::table("{$childTable} as child")
            ->join(
                "{$parentTable} as parent",
                "child.{$foreignKey}",
                '=',
                'parent.id'
            )
            ->whereColumn('child.tenant_id', '!=', 'parent.tenant_id')
            ->exists();

        if ($invalid) {
            throw new \RuntimeException(
                "Cross-tenant relation detected between {$childTable} and {$parentTable}."
            );
        }
    }

    /**
     * Ubah tenant_id menjadi NOT NULL setelah proses backfill.
     */
    private function makeTenantColumnsNotNullable(): void
    {
        foreach ($this->ownedTables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            });
        }

        Schema::table('attribute_values', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
        });

        Schema::table('product_variant_attribute_values', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
        });
    }

    /**
     * Tambahkan unique key yang diperlukan sebagai target composite FK.
     */
    private function addParentUniqueConstraints(): void
    {
        foreach ([
            'categories',
            'products',
            'product_variants',
            'attributes',
            'attribute_values',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->unique(
                    ['tenant_id', 'id'],
                    "{$tableName}_tenant_id_id_unique"
                );
            });
        }

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->unique(
                ['tenant_id', 'category_id', 'id'],
                'sub_categories_tenant_category_id_unique'
            );
        });
    }

    /**
     * Tambahkan foreign key komposit untuk menjaga kepemilikan tenant.
     */
    private function addCompositeForeignKeys(): void
    {
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->foreign(
                ['tenant_id', 'category_id'],
                'sub_categories_tenant_category_fk'
            )
                ->references(['tenant_id', 'id'])
                ->on('categories');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign(
                ['tenant_id', 'category_id'],
                'products_tenant_category_fk'
            )
                ->references(['tenant_id', 'id'])
                ->on('categories');

            $table->foreign(
                ['tenant_id', 'category_id', 'sub_category_id'],
                'products_tenant_sub_category_fk'
            )
                ->references(['tenant_id', 'category_id', 'id'])
                ->on('sub_categories');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->foreign(
                ['tenant_id', 'product_id'],
                'variants_tenant_product_fk'
            )
                ->references(['tenant_id', 'id'])
                ->on('products');
        });

        foreach ([
            'inventories',
            'inventory_movements',
            'inventory_reservations',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->foreign(
                    ['tenant_id', 'product_variant_id'],
                    "{$tableName}_tenant_variant_fk"
                )
                    ->references(['tenant_id', 'id'])
                    ->on('product_variants');
            });
        }

        Schema::table('attribute_values', function (Blueprint $table) {
            $table->foreign(
                ['tenant_id', 'attribute_id'],
                'attribute_values_tenant_attribute_fk'
            )
                ->references(['tenant_id', 'id'])
                ->on('attributes');

            $table->foreign(
                'tenant_id',
                'attribute_values_tenant_fk'
            )
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });

        Schema::table('product_variant_attribute_values', function (Blueprint $table) {
            $table->index(
                ['tenant_id', 'product_variant_id'],
                'variant_attribute_values_tenant_variant_index'
            );

            $table->index(
                ['tenant_id', 'attribute_value_id'],
                'variant_attribute_values_tenant_value_index'
            );

            $table->foreign(
                ['tenant_id', 'product_variant_id'],
                'variant_attribute_values_tenant_variant_fk'
            )
                ->references(['tenant_id', 'id'])
                ->on('product_variants');

            $table->foreign(
                ['tenant_id', 'attribute_value_id'],
                'variant_attribute_values_tenant_value_fk'
            )
                ->references(['tenant_id', 'id'])
                ->on('attribute_values');

            $table->foreign(
                'tenant_id',
                'variant_attribute_values_tenant_fk'
            )
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
        });
    }

    /**
     * Ganti unique SKU dan barcode global menjadi unique per tenant.
     */
    private function replaceGlobalVariantUniqueConstraints(): void
    {
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

    /**
     * SQLite dapat menghapus trigger saat tabel direbuild oleh schema change.
     * Buat ulang trigger integritas stok setelah seluruh perubahan selesai.
     */
    private function restoreInventoryIntegrityTriggers(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::unprepared(
            'DROP TRIGGER IF EXISTS inventories_reserved_quantity_insert_check'
        );

        DB::unprepared(
            'DROP TRIGGER IF EXISTS inventories_reserved_quantity_update_check'
        );

        DB::unprepared("
            CREATE TRIGGER inventories_reserved_quantity_insert_check
            BEFORE INSERT ON inventories
            FOR EACH ROW
            WHEN NEW.reserved_quantity > NEW.quantity
            BEGIN
                SELECT RAISE(ABORT, 'reserved_quantity cannot exceed quantity');
            END
        ");

        DB::unprepared("
            CREATE TRIGGER inventories_reserved_quantity_update_check
            BEFORE UPDATE OF quantity, reserved_quantity ON inventories
            FOR EACH ROW
            WHEN NEW.reserved_quantity > NEW.quantity
            BEGIN
                SELECT RAISE(ABORT, 'reserved_quantity cannot exceed quantity');
            END
        ");
    }

    /**
     * Rollback migration.
     */
    public function down(): void
    {
        $this->dropCompositeForeignKeys();
        $this->restoreGlobalVariantUniqueConstraints();
        $this->dropParentUniqueConstraints();
        $this->dropNewTenantColumns();
        $this->restoreInventoryIntegrityTriggers();
    }

    /**
     * Hapus foreign key yang dibuat migration ini.
     */
    private function dropCompositeForeignKeys(): void
    {
        Schema::table('product_variant_attribute_values', function (Blueprint $table) {
            $table->dropForeign('variant_attribute_values_tenant_variant_fk');
            $table->dropForeign('variant_attribute_values_tenant_value_fk');
            $table->dropForeign('variant_attribute_values_tenant_fk');

            $table->dropIndex('variant_attribute_values_tenant_variant_index');
            $table->dropIndex('variant_attribute_values_tenant_value_index');
        });

        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropForeign('attribute_values_tenant_attribute_fk');
            $table->dropForeign('attribute_values_tenant_fk');
        });

        foreach ([
            'inventories',
            'inventory_movements',
            'inventory_reservations',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropForeign("{$tableName}_tenant_variant_fk");
            });
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropForeign('variants_tenant_product_fk');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_tenant_category_fk');
            $table->dropForeign('products_tenant_sub_category_fk');
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropForeign('sub_categories_tenant_category_fk');
        });
    }

    /**
     * Kembalikan SKU dan barcode menjadi unique global.
     *
     * Rollback akan gagal jika sudah ada SKU atau barcode yang sama
     * pada tenant berbeda. Data tersebut harus diselesaikan dahulu.
     */
    private function restoreGlobalVariantUniqueConstraints(): void
    {
        $duplicateSku = DB::table('product_variants')
            ->select('sku')
            ->groupBy('sku')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        $duplicateBarcode = DB::table('product_variants')
            ->whereNotNull('barcode')
            ->select('barcode')
            ->groupBy('barcode')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicateSku || $duplicateBarcode) {
            throw new \RuntimeException(
                'Cannot rollback variant unique constraints: duplicate SKU or barcode exists across tenants.'
            );
        }

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropUnique('variants_tenant_sku_unique');
            $table->dropUnique('variants_tenant_barcode_unique');

            $table->unique('sku', 'product_variants_sku_unique');
            $table->unique('barcode', 'product_variants_barcode_unique');
        });
    }

    /**
     * Hapus unique key parent yang dibuat migration ini.
     */
    private function dropParentUniqueConstraints(): void
    {
        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropUnique('sub_categories_tenant_category_id_unique');
        });

        foreach ([
            'categories',
            'products',
            'product_variants',
            'attributes',
            'attribute_values',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->dropUnique("{$tableName}_tenant_id_id_unique");
            });
        }
    }

    /**
     * Hapus tenant_id yang ditambahkan migration ini.
     *
     * tenant_id pada tabel lain tetap dipertahankan karena dibuat
     * oleh migration tenancy sebelumnya.
     */
    private function dropNewTenantColumns(): void
    {
        Schema::table('product_variant_attribute_values', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });

        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropColumn('tenant_id');
        });
    }
};
