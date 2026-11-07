<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EvolutionFindingMigrationTest extends TestCase
{
    public function test_evolution_finding_migration_is_compatible_with_sqlite(): void
    {
        $originalConnection = DB::getDefaultConnection();
        $sqliteConfig = config('database.connections.sqlite');
        $sqliteConfig['database'] = ':memory:';
        $sqliteConfig['foreign_key_constraints'] = true;

        config(['database.connections.evolution_finding_test' => $sqliteConfig]);
        DB::purge('evolution_finding_test');
        DB::setDefaultConnection('evolution_finding_test');

        try {
            $db = DB::connection('evolution_finding_test');
            $schema = Schema::connection('evolution_finding_test');

            $schema->create('users', function (Blueprint $table) {
                $table->id();
            });

            $migrationPaths = [
                'migrations/2026_11_06_235504_create_evolution_program_tables.php',
                'migrations/2026_11_06_235505_create_evolution_criteria_tables.php',
                'migrations/2026_11_06_235506_create_evolution_assessment_tables.php',
                'migrations/2026_11_06_235508_create_evolution_finding_tables.php',
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

            $this->assertTrue($schema->hasTable('evolution_findings'));

            $this->assertTrue($schema->hasColumns('evolution_findings', [
                'id',
                'assessment_id',
                'criterion_version_id',
                'result',
                'severity',
                'summary',
                'details',
                'created_at',
            ]));

            $foreignKeys = $schema->getForeignKeys('evolution_findings');
            $this->assertCount(1, $foreignKeys);

            $this->assertSame(
                ['assessment_id', 'criterion_version_id'],
                $foreignKeys[0]['columns']
            );

            $this->assertSame(
                'evolution_assessment_criteria',
                $foreignKeys[0]['foreign_table']
            );

            $this->assertSame(
                ['assessment_id', 'criterion_version_id'],
                $foreignKeys[0]['foreign_columns']
            );

            $indexes = array_column(
                $schema->getIndexes('evolution_findings'),
                'name'
            );

            foreach ([
                'uq_evp_findings_assessment_criterion',
                'uq_evp_findings_assessment_pair',
                'idx_evp_findings_result',
                'idx_evp_findings_severity',
            ] as $index) {
                $this->assertContains($index, $indexes);
            }

            // Prepare user and program.
            $userId = $db->table('users')->insertGetId([]);

            $programId = $db->table('evolution_programs')->insertGetId([
                'code' => 'PROGRAM-001',
                'name' => 'Program 001',
                'created_by' => $userId,
            ]);

            $phaseId = $db->table('evolution_program_phases')->insertGetId([
                'program_id' => $programId,
                'phase_key' => 'phase_1',
                'name' => 'Phase 1',
                'sequence' => 1,
                'entry_requirements' => json_encode([]),
                'exit_requirements' => json_encode([]),
            ]);

            $assessmentId = $db->table('evolution_assessments')->insertGetId([
                'program_id' => $programId,
                'phase_id' => $phaseId,
                'assessment_number' => 1,
                'assessor_id' => $userId,
                'baseline_commit' => str_repeat('a', 40),
                'environment' => json_encode(['database' => 'sqlite']),
                'ruleset_version' => '1.0.0',
            ]);

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

            // Prepare the assessment criterion snapshot.
            $db->table('evolution_assessment_criteria')->insert([
                'assessment_id' => $assessmentId,
                'criterion_version_id' => $criterionVersionId,
                'criterion_key' => 'database_integrity',
                'criterion_version' => 1,
                'name' => 'Database Integrity',
                'severity' => 'critical',
                'evaluation_method' => 'automated',
                'definition' => json_encode(['rules' => []]),
            ]);

            // Valid finding.
            $finding = [
                'assessment_id' => $assessmentId,
                'criterion_version_id' => $criterionVersionId,
                'result' => 'fail',
                'severity' => 'critical',
                'summary' => 'Database integrity check failed',
                'details' => json_encode(['rule' => 'foreign_key_integrity']),
            ];

            $db->table('evolution_findings')->insert($finding);

            // Reject duplicate finding for the same assessment criterion.
            $duplicateRejected = false;

            try {
                $db->table('evolution_findings')->insert($finding);
            } catch (QueryException) {
                $duplicateRejected = true;
            }

            $this->assertTrue($duplicateRejected);

            // Reject finding without a matching assessment snapshot.
            $invalidRejected = false;

            try {
                $db->table('evolution_findings')->insert([
                    'assessment_id' => $assessmentId,
                    'criterion_version_id' => 999999,
                    'result' => 'fail',
                    'severity' => 'high',
                ]);
            } catch (QueryException) {
                $invalidRejected = true;
            }

            $this->assertTrue($invalidRejected);

            $this->assertEmpty($db->select('PRAGMA foreign_key_check'));
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('evolution_finding_test');
            config(['database.connections.evolution_finding_test' => null]);
        }
    }
}
