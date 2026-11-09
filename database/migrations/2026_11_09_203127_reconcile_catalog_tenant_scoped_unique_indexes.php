<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $definitions = [
        'categories' => [
            'columns' => ['slug'],
            'old_indexes' => ['categories_slug_unique'],
            'new_index' => 'categories_scope_slug_unique',
        ],
        'sub_categories' => [
            'columns' => ['category_id',
                'slug'],
            'old_indexes' => ['sub_categories_category_id_slug_unique'],
            'new_index' => 'sub_categories_scope_category_slug_unique',
        ],
        'products' => [
            'columns' => ['slug'],
            'old_indexes' => ['products_slug_unique'],
            'new_index' => 'products_scope_slug_unique',
        ],
        'attributes' => [
            'columns' => ['slug'],
            'old_indexes' => ['attributes_slug_unique'],
            'new_index' => 'attributes_scope_slug_unique',
        ],
        'product_variants' => [
            'columns' => ['sku',
                'barcode'],
            'old_indexes' => [
                'product_variants_sku_unique',
                'product_variants_barcode_unique',
            ],
            'new_indexes' => [
                'variants_scope_sku_unique',
                'variants_scope_barcode_unique',
            ],
        ],
    ];

    public function up(): void
    {
        $this->assertNoZeroTenantIds();
        $this->assertNoScopedDuplicates();

        foreach ($this->definitions as $table => $definition) {
            $this->addGeneratedColumn($table);
        }

        foreach ($this->definitions as $table => $definition) {
            foreach ($this->getNewIndexes($definition) as $index) {
                $this->createUniqueIndex($table, $index);
            }
        }

        foreach ($this->definitions as $table => $definition) {
            foreach ($definition['old_indexes'] as $index) {
                $this->dropIndex($table, $index);
            }
        }
    }

    public function down(): void
    {
        $this->assertNoGlobalDuplicates();

        foreach ($this->definitions as $table => $definition) {
            foreach ($this->getNewIndexes($definition) as $index) {
                $this->dropIndex($table, $index);
            }
        }

        foreach ($this->definitions as $table => $definition) {
            $this->dropGeneratedColumn($table);
        }

        $legacyIndexes = [
            'categories' => [
                'categories_slug_unique' => ['slug'],
            ],
            'sub_categories' => [
                'sub_categories_category_id_slug_unique' => [
                    'category_id',
                    'slug',
                ],
            ],
            'products' => [
                'products_slug_unique' => ['slug'],
            ],
            'attributes' => [
                'attributes_slug_unique' => ['slug'],
            ],
            'product_variants' => [
                'product_variants_sku_unique' => ['sku'],
                'product_variants_barcode_unique' => ['barcode'],
            ],
        ];

        foreach ($legacyIndexes as $table => $indexes) {
            foreach ($indexes as $index => $columns) {
                $this->createUniqueIndex($table, $index, $columns);
            }
        }
    }

    private function assertNoZeroTenantIds(): void
    {
        foreach (array_keys($this->definitions) as $table) {
            $exists = DB::table($table)->where('tenant_id', 0)->exists();

            if ($exists) {
                throw new RuntimeException(
                    "Cannot reconcile {$table}: tenant_id = 0 exists."
                );
            }
        }
    }

    private function assertNoScopedDuplicates(): void
    {
        foreach ($this->definitions as $table => $definition) {
            foreach ($this->getUniqueColumnGroups($definition) as $columns) {
                $query = DB::table($table)
                ->select(array_merge(['tenant_id'], $columns))
                ->groupBy(array_merge(['tenant_id'], $columns))
                ->havingRaw('COUNT(*) > 1');

                if ($table === 'product_variants' && $columns[0] === 'barcode') {
                    $query->whereNotNull('barcode');
                }

                if ($query->exists()) {
                    throw new RuntimeException(
                        "Scoped duplicate detected in {$table}: "
                        .implode(', ', $columns)
                    );
                }
            }
        }
    }

    private function assertNoGlobalDuplicates(): void
    {
        foreach ($this->definitions as $table => $definition) {
            foreach ($this->getUniqueColumnGroups($definition) as $columns) {
                $query = DB::table($table)
                ->select($columns)
                ->groupBy($columns)
                ->havingRaw('COUNT(*) > 1');

                if ($table === 'product_variants' && $columns[0] === 'barcode') {
                    $query->whereNotNull('barcode');
                }

                if ($query->exists()) {
                    throw new RuntimeException(
                        "Cannot rollback {$table}: global duplicates exist for "
                        .implode(', ', $columns)
                    );
                }
            }
        }
    }

    private function getUniqueColumnGroups(array $definition): array
    {
        if (isset($definition['new_indexes'])) {
            return [
                ['sku'],
                ['barcode'],
            ];
        }

        return [$definition['columns']];
    }

    private function getNewIndexes(array $definition): array
    {
        if (isset($definition['new_indexes'])) {
            return $definition['new_indexes'];
        }

        return [$definition['new_index']];
    }

    private function addGeneratedColumn(string $table): void
    {
        if (Schema::hasColumn($table, 'tenant_scope_id')) {
            throw new RuntimeException(
                "Column {$table}.tenant_scope_id already exists."
            );
        }

        DB::statement(
            "ALTER TABLE `{$table}` ADD COLUMN `tenant_scope_id` "
            ."BIGINT UNSIGNED GENERATED ALWAYS AS "
            ."(COALESCE(`tenant_id`, 0)) STORED"
        );
    }

    private function dropGeneratedColumn(string $table): void
    {
        if (Schema::hasColumn($table, 'tenant_scope_id')) {
            DB::statement(
                "ALTER TABLE `{$table}` DROP COLUMN `tenant_scope_id`"
            );
        }
    }

    private function createUniqueIndex(
        string $table,
        string $index,
        ?array $columns = null
    ): void {
        if ($this->indexExists($table, $index)) {
            throw new RuntimeException(
                "Index {$index} already exists on {$table}."
            );
        }

        $columns ??= $this->getColumnsForIndex($table, $index);

        $quoted = array_map(
            fn ($column) => "`{$column}`",
            $columns
        );

        DB::statement(
            "ALTER TABLE `{$table}` ADD UNIQUE INDEX `{$index}` ("
            .implode(', ', $quoted).')'
        );
    }

    private function getColumnsForIndex(string $table, string $index): array
    {
        foreach ($this->definitions[$table]['new_indexes'] ?? [] as $i => $newIndex) {
            if ($newIndex === $index) {
                return ['tenant_scope_id',
                    $i === 0 ? 'sku' : 'barcode'];
            }
        }

        return array_merge(
            ['tenant_scope_id'],
            $this->definitions[$table]['columns']
        );
    }

    private function dropIndex(string $table, string $index): void
    {
        if (! $this->indexExists($table, $index)) {
            throw new RuntimeException(
                "Expected index {$index} is missing on {$table}."
            );
        }

        DB::statement(
            "ALTER TABLE `{$table}` DROP INDEX `{$index}`"
        );
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::table('information_schema.statistics')
        ->where('table_schema', DB::getDatabaseName())
        ->where('table_name', $table)
        ->where('index_name', $index)
        ->exists();
    }
};