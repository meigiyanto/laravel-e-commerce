<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EvolutionProgramMigrationTest extends TestCase
{
    public function test_evolution_program_migration_is_compatible_with_sqlite(): void
    {
        $originalConnection = DB::getDefaultConnection();

        $sqliteConfig = config('database.connections.sqlite');
        $sqliteConfig['database'] = ':memory:';
        $sqliteConfig['foreign_key_constraints'] = true;

        config(['database.connections.evolution_schema' => $sqliteConfig]);
        DB::purge('evolution_schema');
        DB::setDefaultConnection('evolution_schema');

        try {
            $schema = Schema::connection('evolution_schema');
            $db = DB::connection('evolution_schema');

            $schema->create('users', function (Blueprint $table) {
                $table->id();
            });

            $migrationPath = database_path(
                'migrations/2026_11_06_235504_create_evolution_program_tables.php'
            );

            $this->assertFileExists($migrationPath);

            $migration = require $migrationPath;
            $migration->up();

            // SQLite foreign key enforcement.
            $this->assertSame(
                1,
                (int) $db->selectOne('PRAGMA foreign_keys')->foreign_keys
            );

            // Tables and important columns.
            $this->assertTrue($schema->hasTable('evolution_programs'));
            $this->assertTrue($schema->hasTable('evolution_program_phases'));

            $this->assertTrue($schema->hasColumns('evolution_programs', [
                'id', 'code', 'name', 'status', 'created_by',
                'started_at', 'completed_at', 'cancelled_at',
                'created_at', 'updated_at',
            ]));

            $this->assertTrue($schema->hasColumns('evolution_program_phases', [
                'id', 'program_id', 'phase_key', 'name', 'sequence',
                'entry_requirements', 'exit_requirements',
                'is_initial', 'is_terminal', 'is_target', 'created_at',
            ]));

            // Foreign keys.
            $programFK = $schema->getForeignKeys('evolution_programs');
            $phaseFK = $schema->getForeignKeys('evolution_program_phases');

            $this->assertCount(1, $programFK);
            $this->assertSame('users', $programFK[0]['foreign_table']);
            $this->assertSame(['created_by'], $programFK[0]['columns']);

            $this->assertCount(1, $phaseFK);
            $this->assertSame('evolution_programs', $phaseFK[0]['foreign_table']);
            $this->assertSame(['program_id'], $phaseFK[0]['columns']);

            // Unique constraints and indexes.
            $programIndexes = array_column(
                $schema->getIndexes('evolution_programs'),
                'name'
            );

            $phaseIndexes = array_column(
                $schema->getIndexes('evolution_program_phases'),
                'name'
            );

            foreach ([
                'uq_evp_programs_code',
                'idx_evp_programs_status',
                'idx_evp_programs_creator',
            ] as $index) {
                $this->assertContains($index, $programIndexes);
            }

            foreach ([
                'uq_evp_phases_program_key',
                'uq_evp_phases_program_sequence',
                'uq_evp_phases_program_pair',
                'idx_evp_phases_initial',
                'idx_evp_phases_target',
            ] as $index) {
                $this->assertContains($index, $phaseIndexes);
            }

            // Create referenced records.
            $userId = $db->table('users')->insertGetId([]);

            $programId = $db->table('evolution_programs')->insertGetId([
                'code' => 'EVOLUTION-001',
                'name' => 'Evolution Program',
                'created_by' => $userId,
            ]);

            $db->table('evolution_program_phases')->insert([
                'program_id' => $programId,
                'phase_key' => 'single_store',
                'name' => 'Single Store',
                'sequence' => 1,
            ]);

            // Parent deletion must be restricted while a phase references it.
            try {
                $db->table('evolution_programs')
                    ->where('id', $programId)
                    ->delete();

                $this->fail('Program deletion should be restricted.');
            } catch (QueryException $e) {
                $this->assertNotEmpty($e->getMessage());
            }

            // Final SQLite integrity check.
            $this->assertEmpty($db->select('PRAGMA foreign_key_check'));
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('evolution_schema');
            config(['database.connections.evolution_schema' => null]);
        }
    }
}
