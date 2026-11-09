<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
    * Semua tabel katalog dan turunannya yang memiliki tenant_id.
    */
    private array $tables = [
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
    * Indeks unik lama dan indeks unik penggantinya.
    */
    private array $uniqueIndexes = [
        'categories' => [
            'old' => ['categories_slug_unique',
                ['slug']],
            'new' => ['categories_scope_slug_unique',
                ['slug']],
        ],
        'sub_categories' => [
            'old' => ['sub_categories_category_id_slug_unique',
                ['category_id',
                    'slug']],
            'new' => ['sub_categories_scope_category_slug_unique',
                ['category_id',
                    'slug']],
        ],
        'products' => [
            'old' => ['products_slug_unique',
                ['slug']],
            'new' => ['products_scope_slug_unique',
                ['slug']],
        ],
        'attributes' => [
            'old' => ['attributes_slug_unique',
                ['slug']],
            'new' => ['attributes_scope_slug_unique',
                ['slug']],
        ],
        'product_variants' => [
            'old_sku' => ['product_variants_sku_unique',
                ['sku']],
            'new_sku' => ['product_variants_scope_sku_unique',
                ['sku']],
            'old_barcode' => ['product_variants_barcode_unique',
                ['barcode']],
            'new_barcode' => ['product_variants_scope_barcode_unique',
                ['barcode']],
        ],
    ];

    public function up(): void
    {
        $this->assertSupportedDatabase();
        $this->assertTablesExist();
        $this->assertReservedTenantIdIsUnused();

        $this->addTenantScopeColumns();
        $this->assertScopedValuesAreUnique();
        $this->addScopedUniqueIndexes();
        $this->dropGlobalUniqueIndexes();
    }

    private function assertSupportedDatabase(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            throw new RuntimeException(
                'Catalog tenant scope migration requires MySQL or MariaDB.'
            );
        }
    }

    private function assertTablesExist(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException(
                    "Required catalog table does not exist: {$table}."
                );
            }

            if (! Schema::hasColumn($table, 'tenant_id')) {
                throw new RuntimeException(
                    "Required tenant_id column does not exist: {$table}."
                );
            }
        }
    }

    /**
    * Nilai 0 dicadangkan untuk data Single Store.
    * Tenant sungguhan menggunakan ID positif.
    */
    private function assertReservedTenantIdIsUnused(): void
    {
        foreach ($this->tables as $table) {
            if (DB::table($table)->where('tenant_id', 0)->exists()) {
                throw new RuntimeException(
                    "Cannot reserve tenant scope 0: {$table} contains tenant_id = 0."
                );
            }
        }
    }

    /**
    * Tambahkan generated column ke seluruh tabel terkait.
    *
    * NULL tenant_id -> tenant_scope_id 0.
    * tenant_id positif -> tenant_scope_id sesuai tenant.
    */
    private function addTenantScopeColumns(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasColumn($table, 'tenant_scope_id')) {
                continue;
            }

            DB::statement("
                ALTER TABLE `{$table}`
                ADD COLUMN `tenant_scope_id` BIGINT UNSIGNED
                GENERATED ALWAYS AS (COALESCE(`tenant_id`, 0)) STORED
            ");
        }
    }

    /**
    * Periksa duplikasi sebelum membuat indeks unik baru.
    */
    private function assertScopedValuesAreUnique(): void
    {
        $checks = [
            'categories' => [['slug']],
            'sub_categories' => [['category_id',
                'slug']],
            'products' => [['slug']],
            'attributes' => [['slug']],
            'product_variants' => [['sku'],
                ['barcode']],
        ];

        foreach ($checks as $table => $groups) {
            foreach ($groups as $columns) {
                $this->assertNoDuplicates(
                    $table,
                    $columns,
                    $table === 'product_variants' && $columns === ['barcode']
                );
            }
        }
    }

    private function assertNoDuplicates(
        string $table,
        array $columns,
        bool $nullableValues = false
    ): void {
        $query = DB::table($table);

        foreach ($columns as $column) {
            $query->whereNotNull($column);
        }

        $groupColumns = array_merge(['tenant_scope_id'], $columns);

        $duplicateExists = $query
        ->select($groupColumns)
        ->groupBy($groupColumns)
        ->havingRaw('COUNT(*) > 1')
        ->exists();

        if ($duplicateExists) {
            throw new RuntimeException(
                "Duplicate scoped values detected in {$table}: "
                .implode(', ', $columns)
            );
        }
    }
    /**
    * Buat indeks unik berdasarkan tenant_scope_id.
    */
    private function addScopedUniqueIndexes(): void
    {
        foreach ($this->uniqueIndexes as $table => $indexes) {
            foreach ($indexes as $key => [$name, $columns]) {
                if (! str_starts_with($key, 'new')) {
                    continue;
                }

                $this->addUniqueIndex(
                    $table,
                    $name,
                    array_merge(['tenant_scope_id'], $columns)
                );
            }
        }
    }

    private function addUniqueIndex(
        string $table,
        string $name,
        array $columns
    ): void {
        if ($this->hasIndex($table, $name)) {
            return;
        }

        $quotedColumns = implode(
            ', ',
            array_map(fn ($column) => "`{$column}`", $columns)
        );

        DB::statement(
            "ALTER TABLE `{$table}` ADD UNIQUE INDEX `{$name}` ({$quotedColumns})"
        );
    }

    /**
    * Hapus indeks global setelah indeks pengganti tersedia.
    */
    private function dropGlobalUniqueIndexes(): void
    {
        foreach ($this->uniqueIndexes as $table => $indexes) {
            foreach ($indexes as $key => [$name]) {
                if (! str_starts_with($key, 'old')) {
                    continue;
                }

                $this->dropIndex($table, $name);
            }
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        return ! empty(DB::select(
            "SHOW INDEX FROM `{$table}` WHERE Key_name = ?",
            [$name]
        ));
    }

    private function dropIndex(string $table, string $name): void
    {
        if (! $this->hasIndex($table, $name)) {
            return;
        }

        DB::statement(
            "ALTER TABLE `{$table}` DROP INDEX `{$name}`"
        );
    }

    /**
    * Rollback hanya boleh dilakukan jika keunikan global
    * dapat dipulihkan tanpa konflik antar-tenant.
    */
    public function down(): void
    {
        $this->assertSupportedDatabase();
        $this->assertGlobalValuesAreUnique();

        $this->restoreGlobalUniqueIndexes();
        $this->dropScopedUniqueIndexes();
        $this->dropTenantScopeColumns();
    }

    private function assertGlobalValuesAreUnique(): void
    {
        $checks = [
            'categories' => [['slug']],
            'sub_categories' => [['category_id',
                'slug']],
            'products' => [['slug']],
            'attributes' => [['slug']],
            'product_variants' => [['sku'],
                ['barcode']],
        ];

        foreach ($checks as $table => $groups) {
            foreach ($groups as $columns) {
                $query = DB::table($table);

                foreach ($columns as $column) {
                    $query->whereNotNull($column);
                }

                $duplicateExists = $query
                ->select($columns)
                ->selectRaw('COUNT(*) AS aggregate')
                ->groupBy(...$columns)
                ->havingRaw('COUNT(*) > 1')
                ->exists();

                if ($duplicateExists) {
                    throw new RuntimeException(
                        "Cannot rollback: global duplicate detected in {$table}."
                    );
                }
            }
        }
    }

    private function restoreGlobalUniqueIndexes(): void
    {
        foreach ($this->uniqueIndexes as $table => $indexes) {
            foreach ($indexes as $key => [$name, $columns]) {
                if (! str_starts_with($key, 'old')) {
                    continue;
                }

                $this->addUniqueIndex($table, $name, $columns);
            }
        }
    }

    private function dropScopedUniqueIndexes(): void
    {
        foreach ($this->uniqueIndexes as $table => $indexes) {
            foreach ($indexes as $key => [$name]) {
                if (! str_starts_with($key, 'new')) {
                    continue;
                }

                $this->dropIndex($table, $name);
            }
        }
    }

    private function dropTenantScopeColumns(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (! Schema::hasColumn($table, 'tenant_scope_id')) {
                continue;
            }

            DB::statement(
                "ALTER TABLE `{$table}` DROP COLUMN `tenant_scope_id`"
            );
        }
    }
};