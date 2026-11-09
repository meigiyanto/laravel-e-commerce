<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $definitions = [
        'categories' => [
            'scoped' => [
                'categories_scope_slug_unique' => ['tenant_scope_id',
                    'slug'],
            ],
            'global' => [
                'categories_slug_unique' => ['slug'],
            ],
        ],
        'sub_categories' => [
            'scoped' => [
                'sub_categories_scope_category_slug_unique' => [
                    'tenant_scope_id',
                    'category_id',
                    'slug',
                ],
            ],
            'global' => [
                'sub_categories_category_id_slug_unique' => [
                    'category_id',
                    'slug',
                ],
            ],
        ],
        'products' => [
            'scoped' => [
                'products_scope_slug_unique' => ['tenant_scope_id',
                    'slug'],
            ],
            'global' => [
                'products_slug_unique' => ['slug'],
            ],
        ],
        'attributes' => [
            'scoped' => [
                'attributes_scope_slug_unique' => ['tenant_scope_id',
                    'slug'],
            ],
            'global' => [
                'attributes_slug_unique' => ['slug'],
            ],
        ],
        'product_variants' => [
            'scoped' => [
                'product_variants_scope_sku_unique' => [
                    'tenant_scope_id',
                    'sku',
                ],
                'product_variants_scope_barcode_unique' => [
                    'tenant_scope_id',
                    'barcode',
                ],
            ],
            'global' => [
                'product_variants_sku_unique' => ['sku'],
                'product_variants_barcode_unique' => ['barcode'],
            ],
            'aliases' => [
                'variants_scope_sku_unique' => [
                    'tenant_scope_id',
                    'sku',
                ],
                'variants_scope_barcode_unique' => [
                    'tenant_scope_id',
                    'barcode',
                ],
            ],
        ],
    ];

    private array $unusedScopeTables = [
        'inventories',
        'inventory_movements',
        'inventory_reservations',
        'attribute_values',
        'product_variant_attribute_values',
    ];

    public function up(): void
    {
        $this->assertSupportedDatabase();
        $this->assertRequiredTables();
        $this->assertReservedTenantIdIsUnused();

        $this->ensureScopeColumns();
        $this->assertScopedValuesAreUnique();
        $this->ensureScopedIndexes();
        $this->dropGlobalIndexes();
        $this->dropUnusedScopeColumns();
    }

    private function assertSupportedDatabase(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException(
                'This migration requires MySQL or MariaDB.'
            );
        }
    }

    private function assertRequiredTables(): void
    {
        $tables = array_unique(array_merge(
            array_keys($this->definitions),
            $this->unusedScopeTables
        ));

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException(
                    "Required table does not exist: {$table}."
                );
            }

            if (! Schema::hasColumn($table, 'tenant_id')) {
                throw new RuntimeException(
                    "Required tenant_id column does not exist: {$table}."
                );
            }
        }
    }

    private function assertReservedTenantIdIsUnused(): void
    {
        foreach (array_unique(array_merge(
            array_keys($this->definitions),
            $this->unusedScopeTables
        )) as $table) {
            if (DB::table($table)->where('tenant_id', 0)->exists()) {
                throw new RuntimeException(
                    "Reserved tenant_id 0 is present in {$table}."
                );
            }
        }
    }

    private function ensureScopeColumns(): void
    {
        foreach (array_keys($this->definitions) as $table) {
            if (! Schema::hasColumn($table, 'tenant_scope_id')) {
                DB::statement(
                    "ALTER TABLE `{$table}` ADD COLUMN "
                    . "`tenant_scope_id` BIGINT UNSIGNED "
                    . "GENERATED ALWAYS AS (COALESCE(`tenant_id`, 0)) STORED"
                );
            }

            $this->assertScopeColumnDefinition($table);
        }
    }

    private function assertScopedValuesAreUnique(): void
    {
        foreach ($this->definitions as $table => $definition) {
            foreach ($definition['scoped'] as $columns) {
                $values = array_values(array_diff(
                    $columns,
                    ['tenant_scope_id']
                ));

                $query = DB::table($table);

                foreach ($values as $column) {
                    $query->whereNotNull($column);
                }

                $groupColumns = $columns;

                if (
                    $query->select($groupColumns)
                    ->groupBy($groupColumns)
                    ->havingRaw('COUNT(*) > 1')
                    ->exists()
                ) {
                    throw new RuntimeException(
                        "Duplicate scoped values detected in {$table}: "
                        . implode(', ', $values)
                    );
                }
            }
        }
    }

    private function indexes(string $table): array
    {
        $rows = DB::table('information_schema.statistics')
        ->select([
            'index_name',
            'non_unique',
            'column_name',
            'seq_in_index',
        ])
        ->where('table_schema', DB::connection()->getDatabaseName())
        ->where('table_name', $table)
        ->orderBy('index_name')
        ->orderBy('seq_in_index')
        ->get();

        return $rows->groupBy('index_name')->map(
            fn ($items) => [
                'unique' => (int) $items->first()->non_unique === 0,
                'columns' => $items->pluck('column_name')->all(),
            ]
        )->all();
    }

    private function ensureScopedIndexes(): void
    {
        foreach ($this->definitions as $table => $definition) {
            foreach ($definition['scoped'] as $name => $columns) {
                $indexes = $this->indexes($table);

                if (isset($indexes[$name])) {
                    if (
                        ! $indexes[$name]['unique'] || $indexes[$name]['columns'] !== $columns
                    ) {
                        throw new RuntimeException(
                            "Index {$name} exists with an unexpected definition."
                        );
                    }

                    continue;
                }

                $matching = collect($indexes)->first(
                    fn ($index) =>
                    $index['unique'] && $index['columns'] === $columns
                );

                if ($matching) {
                    $oldName = collect($indexes)->search(
                        fn ($index) =>
                        $index['unique'] && $index['columns'] === $columns
                    );

                    DB::statement(
                        "ALTER TABLE `{$table}` RENAME INDEX "
                        . "`{$oldName}` TO `{$name}`"
                    );

                    continue;
                }

                $quoted = implode(
                    ', ',
                    array_map(fn ($column) => "`{$column}`", $columns)
                );

                DB::statement(
                    "ALTER TABLE `{$table}` ADD UNIQUE INDEX "
                    . "`{$name}` ({$quoted})"
                );
            }
        }
    }

    private function dropGlobalIndexes(): void
    {
        foreach ($this->definitions as $table => $definition) {
            foreach ($definition['global'] as $name => $columns) {
                $indexes = $this->indexes($table);

                if (! isset($indexes[$name])) {
                    continue;
                }

                if (
                    ! $indexes[$name]['unique'] || $indexes[$name]['columns'] !== $columns
                ) {
                    throw new RuntimeException(
                        "Global index {$name} has an unexpected definition."
                    );
                }

                DB::statement(
                    "ALTER TABLE `{$table}` DROP INDEX `{$name}`"
                );
            }
        }
    }

    private function dropUnusedScopeColumns(): void
    {
        foreach ($this->unusedScopeTables as $table) {
            if (! Schema::hasColumn($table, 'tenant_scope_id')) {
                continue;
            }

            $this->assertScopeColumnDefinition($table);

            foreach ($this->indexes($table) as $name => $index) {
                if (in_array('tenant_scope_id', $index['columns'], true)) {
                    throw new RuntimeException(
                        "Cannot remove tenant_scope_id from {$table}: "
                        . "index {$name} still depends on it."
                    );
                }
            }

            DB::statement(
                "ALTER TABLE `{$table}` DROP COLUMN `tenant_scope_id`"
            );
        }
    }

    public function down(): void
    {
        throw new RuntimeException(
            'This reconciliation is forward-only. '
            . 'Restoring global unique indexes requires a separate '
            . 'data-integrity review.'
        );
    }

    private function assertScopeColumnDefinition(string $table): void
    {
        $column = DB::table('information_schema.columns')
        ->where('table_schema', DB::connection()->getDatabaseName())
        ->where('table_name', $table)
        ->where('column_name', 'tenant_scope_id')
        ->first();

        if (! $column) {
            throw new RuntimeException(
                "tenant_scope_id does not exist in {$table}."
            );
        }

        $extra = strtolower((string) $column->extra);
        $expression = strtolower((string) $column->generation_expression);
        $normalized = preg_replace('/[`\\s]/', '', $expression);

        if (! str_contains($extra, 'generated') || ! str_starts_with(strtolower($column->column_type), 'bigint') || ! str_contains(strtolower($column->column_type), 'unsigned') || $normalized !== 'coalesce(tenant_id,0)') {
            throw new RuntimeException(
                "Unexpected tenant_scope_id definition in {$table}."
            );
        }
    }
};