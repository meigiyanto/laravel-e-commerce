<?php

namespace Tests\Feature\Evolution;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FindingResolutionMigrationTest extends TestCase
{
    public function test_finding_resolution_table_structure_and_constraints_work(): void
    {
        $originalConnection = DB::getDefaultConnection();

        $sqliteConfig = config('database.connections.sqlite');
        $sqliteConfig['database'] = ':memory:';
        $sqliteConfig['foreign_key_constraints'] = true;

        config(['database.connections.evolution_migration' => $sqliteConfig]);
        DB::purge('evolution_migration');
        DB::setDefaultConnection('evolution_migration');

        try {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
            });

            Schema::create('evolution_findings', function (Blueprint $table) {
                $table->id();
            });

            $migration = require database_path(
                'migrations/2026_11_07_144938_create_evolution_finding_resolutions_table.php'
            );
            $migration->up();

            $this->assertTrue(
                Schema::hasTable('evolution_finding_resolutions')
            );

            $expectedColumns = [
                'id',
                'finding_id',
                'resolution',
                'rationale',
                'review_by',
                'evidence_reference',
                'recorded_by',
                'created_at',
            ];

            foreach ($expectedColumns as $column) {
                $this->assertTrue(
                    Schema::hasColumn('evolution_finding_resolutions', $column),
                    "Missing column: {$column}"
                );
            }

            $foreignKeys = DB::select(
                "PRAGMA foreign_key_list('evolution_finding_resolutions')"
            );

            $this->assertCount(2, $foreignKeys);

            $foreignKeyTargets = collect($foreignKeys)
                ->map(fn ($foreignKey) => [
                    'table' => $foreignKey->table,
                    'from' => $foreignKey->from,
                    'to' => $foreignKey->to,
                ])
                ->all();

            $this->assertContains([
                'table' => 'evolution_findings',
                'from' => 'finding_id',
                'to' => 'id',
            ], $foreignKeyTargets);

            $this->assertContains([
                'table' => 'users',
                'from' => 'recorded_by',
                'to' => 'id',
            ], $foreignKeyTargets);

            foreach ($foreignKeys as $foreignKey) {
                $this->assertSame('RESTRICT', strtoupper($foreignKey->on_delete));
                $this->assertSame('RESTRICT', strtoupper($foreignKey->on_update));
            }

            $indexes = collect(DB::select(
                "PRAGMA index_list('evolution_finding_resolutions')"
            ))->pluck('name')->all();

            $this->assertContains('idx_evp_resolutions_finding', $indexes);
            $this->assertContains('idx_evp_resolutions_type', $indexes);
            $this->assertContains('idx_evp_resolutions_review', $indexes);
            $this->assertContains('idx_evp_resolutions_actor', $indexes);

            $this->assertFalse(
                Schema::hasColumn('evolution_finding_resolutions', 'updated_at')
            );
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('evolution_migration');
            config(['database.connections.evolution_migration' => null]);
        }
    }
}
