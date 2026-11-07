<?php

namespace Tests\Feature\Evolution;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CriteriaMigrationTest extends TestCase
{
    public function test_evolution_criteria_migration_is_compatible_with_sqlite(): void
    {
        $originalConnection = DB::getDefaultConnection();

        $sqliteConfig = config('database.connections.sqlite');
        $sqliteConfig['database'] = ':memory:';
        $sqliteConfig['foreign_key_constraints'] = true;

        config(['database.connections.evolution_criteria_test' => $sqliteConfig]);
        DB::purge('evolution_criteria_test');
        DB::setDefaultConnection('evolution_criteria_test');

        try {
            $db = DB::connection('evolution_criteria_test');
            $schema = Schema::connection('evolution_criteria_test');

            $migrationPath = database_path(
                'migrations/2026_11_06_235505_create_evolution_criteria_tables.php'
            );

            $this->assertFileExists($migrationPath);

            $migration = require $migrationPath;
            $migration->up();

            // SQLite foreign key enforcement.
            $this->assertSame(
                1,
                (int) $db->selectOne('PRAGMA foreign_keys')->foreign_keys
            );

            // Tables and columns.
            $this->assertTrue($schema->hasTable('evolution_criteria'));
            $this->assertTrue($schema->hasTable('evolution_criteria_versions'));

            $this->assertTrue($schema->hasColumns('evolution_criteria', [
                'id', 'criterion_key', 'is_active', 'created_at', 'updated_at',
            ]));

            $this->assertTrue($schema->hasColumns('evolution_criteria_versions', [
                'id', 'criterion_id', 'version', 'name', 'description',
                'severity', 'evaluation_method', 'definition', 'created_at',
            ]));

            // Foreign key.
            $foreignKeys = $schema->getForeignKeys('evolution_criteria_versions');

            $this->assertCount(1, $foreignKeys);
            $this->assertSame('evolution_criteria', $foreignKeys[0]['foreign_table']);
            $this->assertSame(['criterion_id'], $foreignKeys[0]['columns']);
            $this->assertSame(['id'], $foreignKeys[0]['foreign_columns']);

            // Index definitions.
            $criteriaIndexes = $schema->getIndexes('evolution_criteria');
            $versionIndexes = $schema->getIndexes('evolution_criteria_versions');

            $this->assertContains(
                'uq_evp_criteria_key',
                array_column($criteriaIndexes, 'name')
            );
            $this->assertContains(
                'idx_evp_criteria_active',
                array_column($criteriaIndexes, 'name')
            );
            $this->assertContains(
                'uq_evp_criteria_versions_number',
                array_column($versionIndexes, 'name')
            );
            $this->assertContains(
                'idx_evp_criteria_versions_severity',
                array_column($versionIndexes, 'name')
            );
            $this->assertContains(
                'idx_evp_criteria_versions_method',
                array_column($versionIndexes, 'name')
            );

            // Create a criterion and its first version.
            $criterionId = $db->table('evolution_criteria')->insertGetId([
                'criterion_key' => 'database_integrity',
            ]);

            $versionData = [
                'criterion_id' => $criterionId,
                'version' => 1,
                'name' => 'Database Integrity',
                'description' => 'Checks database integrity.',
                'severity' => 'critical',
                'evaluation_method' => 'automated',
                'definition' => json_encode(['rules' => []]),
            ];

            $db->table('evolution_criteria_versions')->insert($versionData);

            // Duplicate version number for the same criterion must fail.
            $duplicateRejected = false;

            try {
                $db->table('evolution_criteria_versions')->insert($versionData);
            } catch (QueryException) {
                $duplicateRejected = true;
            }

            $this->assertTrue($duplicateRejected);

            // Deleting a criterion with existing versions must fail.
            $deleteRejected = false;

            try {
                $db->table('evolution_criteria')
                    ->where('id', $criterionId)
                    ->delete();
            } catch (QueryException) {
                $deleteRejected = true;
            }

            $this->assertTrue($deleteRejected);

            // Final SQLite integrity check.
            $this->assertEmpty($db->select('PRAGMA foreign_key_check'));
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('evolution_criteria_test');
            config(['database.connections.evolution_criteria_test' => null]);
        }
    }
}
