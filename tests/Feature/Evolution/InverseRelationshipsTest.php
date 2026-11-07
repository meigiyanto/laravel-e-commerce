<?php

namespace Tests\Feature\Evolution;

use App\Models\Evolution\Assessment;
use App\Models\Evolution\Criterion;
use App\Models\Evolution\Finding;
use App\Models\Evolution\Program;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InverseRelationshipsTest extends TestCase
{
    public function test_inverse_evolution_relationships_work(): void
    {
        $originalConnection = DB::getDefaultConnection();

        $sqliteConfig = config('database.connections.sqlite');
        $sqliteConfig['database'] = ':memory:';
        $sqliteConfig['foreign_key_constraints'] = true;

        config(['database.connections.evolution_model' => $sqliteConfig]);
        DB::purge('evolution_model');
        DB::setDefaultConnection('evolution_model');

        try {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role')->default('customer');
                $table->rememberToken();
                $table->timestamps();
            });

            $migrationFiles = [
                '2026_11_06_235504_create_evolution_program_tables.php',
                '2026_11_06_235505_create_evolution_criteria_tables.php',
                '2026_11_06_235506_create_evolution_assessment_tables.php',
                '2026_11_06_235508_create_evolution_finding_tables.php',
            ];

            foreach ($migrationFiles as $file) {
                $migration = require database_path("migrations/{$file}");
                $migration->up();
            }

            $user = User::factory()->create();

            $program = Program::create([
                'code' => 'EVOLUTION-INVERSE-001',
                'name' => 'Inverse Relationships Test',
                'created_by' => $user->id,
            ]);

            $phase = $program->phases()->create([
                'phase_key' => 'single_store',
                'name' => 'Single Store',
                'sequence' => 1,
                'entry_requirements' => [],
                'exit_requirements' => [],
                'is_initial' => true,
            ]);

            $criterion = Criterion::create([
                'criterion_key' => 'database_isolation',
            ]);

            $version = $criterion->versions()->create([
                'version' => 1,
                'name' => 'Database Isolation',
                'severity' => 'critical',
                'evaluation_method' => 'hybrid',
                'definition' => ['checks' => ['foreign_keys']],
            ]);

            $assessment = Assessment::create([
                'program_id' => $program->id,
                'phase_id' => $phase->id,
                'assessment_number' => 1,
                'status' => 'completed',
                'assessor_id' => $user->id,
                'baseline_commit' => 'abc123def456',
                'environment' => ['database' => 'sqlite'],
                'ruleset_version' => '1.0.0',
                'started_at' => now()->subHour(),
                'finished_at' => now(),
            ]);

            $assessmentCriterion = $assessment->criteria()->create([
                'criterion_version_id' => $version->id,
                'criterion_key' => 'database_isolation',
                'criterion_version' => 1,
                'name' => 'Database Isolation',
                'severity' => 'critical',
                'evaluation_method' => 'hybrid',
                'definition' => ['checks' => ['foreign_keys']],
            ]);

            $finding = Finding::create([
                'assessment_id' => $assessment->id,
                'criterion_version_id' => $version->id,
                'result' => Finding::RESULT_FAIL,
                'severity' => Finding::SEVERITY_CRITICAL,
                'summary' => 'Foreign key validation failed.',
                'details' => ['failed' => ['foreign_keys']],
            ]);

            // Program -> Assessments.
            $this->assertCount(1, $program->assessments);
            $this->assertTrue(
                $program->assessments->first()->is($assessment)
            );

            // ProgramPhase -> Assessments.
            $this->assertCount(1, $phase->assessments);
            $this->assertTrue(
                $phase->assessments->first()->is($assessment)
            );

            // CriterionVersion -> AssessmentCriteria.
            $this->assertCount(1, $version->assessmentCriteria);
            $this->assertTrue(
                $version->assessmentCriteria->first()->is($assessmentCriterion)
            );

            // Finding -> CriterionVersion.
            $this->assertTrue(
                $finding->criterionVersion->is($version)
            );
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('evolution_model');
            config(['database.connections.evolution_model' => null]);
        }
    }
}
