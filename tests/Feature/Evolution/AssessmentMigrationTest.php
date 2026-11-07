<?php

namespace Tests\Feature\Evolution;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AssessmentMigrationTest extends TestCase
{
    public function test_evolution_assessment_migration_is_compatible_with_sqlite(): void
    {
        $originalConnection = DB::getDefaultConnection();

        $sqliteConfig = config('database.connections.sqlite');
        $sqliteConfig['database'] = ':memory:';
        $sqliteConfig['foreign_key_constraints'] = true;

        config(['database.connections.evolution_assessment_test' => $sqliteConfig]);
        DB::purge('evolution_assessment_test');
        DB::setDefaultConnection('evolution_assessment_test');

        try {
            $db = DB::connection('evolution_assessment_test');
            $schema = Schema::connection('evolution_assessment_test');

            $schema->create('users', function (Blueprint $table) {
                $table->id();
            });

            $migrationPaths = [
                'migrations/2026_11_06_235504_create_evolution_program_tables.php',
                'migrations/2026_11_06_235505_create_evolution_criteria_tables.php',
                'migrations/2026_11_06_235506_create_evolution_assessment_tables.php',
            ];

            foreach ($migrationPaths as $path) {
                $migrationPath = database_path($path);
                $this->assertFileExists($migrationPath);
                (require $migrationPath)->up();
            }

            $this->assertSame(
                1,
                (int) $db->selectOne('PRAGMA foreign_keys')->foreign_keys
            );

            // Table and column structure.
            $this->assertTrue($schema->hasTable('evolution_assessments'));
            $this->assertTrue($schema->hasTable('evolution_assessment_criteria'));

            $this->assertTrue($schema->hasColumns('evolution_assessments', [
                'id', 'program_id', 'phase_id', 'assessment_number',
                'status', 'assessor_id', 'supersedes_assessment_id',
                'baseline_commit', 'environment', 'ruleset_version',
                'started_at', 'finished_at', 'cancelled_at',
                'cancelled_by', 'cancellation_reason',
                'created_at', 'updated_at',
            ]));

            $this->assertTrue($schema->hasColumns('evolution_assessment_criteria', [
                'id', 'assessment_id', 'criterion_version_id',
                'criterion_key', 'criterion_version', 'name',
                'description', 'severity', 'evaluation_method',
                'definition', 'created_at',
            ]));

            // Foreign key structure.
            $assessmentFK = $schema->getForeignKeys('evolution_assessments');
            $snapshotFK = $schema->getForeignKeys('evolution_assessment_criteria');

            $this->assertCount(5, $assessmentFK);
            $this->assertCount(2, $snapshotFK);

            $this->assertContains(
                ['program_id', 'phase_id'],
                array_column($assessmentFK, 'columns')
            );

            $this->assertContains(
                ['program_id', 'supersedes_assessment_id'],
                array_column($assessmentFK, 'columns')
            );

            $this->assertContains(
                ['assessment_id'],
                array_column($snapshotFK, 'columns')
            );

            $this->assertContains(
                ['criterion_version_id'],
                array_column($snapshotFK, 'columns')
            );

            // Index structure.
            $assessmentIndexes = array_column(
                $schema->getIndexes('evolution_assessments'),
                'name'
            );

            $snapshotIndexes = array_column(
                $schema->getIndexes('evolution_assessment_criteria'),
                'name'
            );

            foreach ([
                'uq_evp_assessments_program_number',
                'uq_evp_assessments_program_pair',
                'idx_evp_assessments_status',
                'idx_evp_assessments_program_phase',
                'idx_evp_assessments_supersedes',
                'idx_evp_assessments_assessor',
                'idx_evp_assessments_cancelled_by',
            ] as $index) {
                $this->assertContains($index, $assessmentIndexes);
            }

            foreach ([
                'uq_evp_assessment_criteria_version',
                'idx_evp_assessment_criteria_version',
            ] as $index) {
                $this->assertContains($index, $snapshotIndexes);
            }

            // Prepare users.
            $userId = $db->table('users')->insertGetId([]);
            $otherUserId = $db->table('users')->insertGetId([]);

            // Prepare two programs.
            $programIds = [];

            foreach (['PROGRAM-001', 'PROGRAM-002'] as $code) {
                $programIds[] = $db->table('evolution_programs')->insertGetId([
                    'code' => $code,
                    'name' => $code,
                    'created_by' => $userId,
                ]);
            }

            // Prepare one phase for each program.
            $phaseIds = [];

            foreach ($programIds as $index => $programId) {
                $phaseIds[] = $db->table('evolution_program_phases')->insertGetId([
                    'program_id' => $programId,
                    'phase_key' => 'phase_' . ($index + 1),
                    'name' => 'Phase ' . ($index + 1),
                    'sequence' => 1,
                    'entry_requirements' => json_encode([]),
                    'exit_requirements' => json_encode([]),
                ]);
            }

            // Insert valid assessments.
            $assessmentIds = [];

            foreach ($programIds as $index => $programId) {
                $assessmentIds[] = $db->table('evolution_assessments')->insertGetId([
                    'program_id' => $programId,
                    'phase_id' => $phaseIds[$index],
                    'assessment_number' => 1,
                    'assessor_id' => $userId,
                    'baseline_commit' => str_repeat('a', 40),
                    'environment' => json_encode(['database' => 'sqlite']),
                    'ruleset_version' => '1.0.0',
                ]);
            }

            // Reject phase belonging to another program.
            $phaseRejected = false;

            try {
                $db->table('evolution_assessments')->insert([
                    'program_id' => $programIds[0],
                    'phase_id' => $phaseIds[1],
                    'assessment_number' => 2,
                    'assessor_id' => $userId,
                    'baseline_commit' => str_repeat('b', 40),
                    'environment' => json_encode([]),
                    'ruleset_version' => '1.0.0',
                ]);
            } catch (QueryException) {
                $phaseRejected = true;
            }

            $this->assertTrue($phaseRejected);

            // Reject superseded assessment belonging to another program.
            $supersedesRejected = false;

            try {
                $db->table('evolution_assessments')->insert([
                    'program_id' => $programIds[0],
                    'phase_id' => $phaseIds[0],
                    'assessment_number' => 2,
                    'assessor_id' => $userId,
                    'supersedes_assessment_id' => $assessmentIds[1],
                    'baseline_commit' => str_repeat('c', 40),
                    'environment' => json_encode([]),
                    'ruleset_version' => '1.0.0',
                ]);
            } catch (QueryException) {
                $supersedesRejected = true;
            }

            $this->assertTrue($supersedesRejected);

            // Prepare criterion and version.
            $criterionId = $db->table('evolution_criteria')->insertGetId([
                'criterion_key' => 'database_integrity',
            ]);

            $criterionVersionId = $db->table('evolution_criteria_versions')->insertGetId([
                'criterion_id' => $criterionId,
                'version' => 1,
                'name' => 'Database Integrity',
                'severity' => 'critical',
                'evaluation_method' => 'automated',
                'definition' => json_encode(['rules' => []]),
            ]);

            // Insert snapshot.
            $snapshot = [
                'assessment_id' => $assessmentIds[0],
                'criterion_version_id' => $criterionVersionId,
                'criterion_key' => 'database_integrity',
                'criterion_version' => 1,
                'name' => 'Database Integrity',
                'severity' => 'critical',
                'evaluation_method' => 'automated',
                'definition' => json_encode(['rules' => []]),
            ];

            $db->table('evolution_assessment_criteria')->insert($snapshot);

            // Reject duplicate criterion version in the same assessment.
            $duplicateRejected = false;

            try {
                $db->table('evolution_assessment_criteria')->insert($snapshot);
            } catch (QueryException) {
                $duplicateRejected = true;
            }

            $this->assertTrue($duplicateRejected);

            // Final SQLite integrity check.
            $this->assertEmpty($db->select('PRAGMA foreign_key_check'));
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('evolution_assessment_test');
            config(['database.connections.evolution_assessment_test' => null]);
        }
    }
}
