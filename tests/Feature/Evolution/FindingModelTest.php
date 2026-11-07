<?php

namespace Tests\Feature\Evolution;

use App\Models\Evolution\Assessment;
use App\Models\Evolution\Criterion;
use App\Models\Evolution\Finding;
use App\Models\Evolution\FindingResolution;
use App\Models\Evolution\Program;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FindingModelTest extends TestCase
{
    public function test_finding_and_resolution_relationships_and_casts_work(): void
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
                '2026_11_07_144938_create_evolution_finding_resolutions_table.php',
            ];

            foreach ($migrationFiles as $file) {
                $migration = require database_path("migrations/{$file}");
                $migration->up();
            }

            $assessor = User::factory()->create();
            $recorder = User::factory()->create();

            $program = Program::create([
                'code' => 'EVOLUTION-FINDING-001',
                'name' => 'Finding Model Test',
                'created_by' => $assessor->id,
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

            $criterionVersion = $criterion->versions()->create([
                'version' => 1,
                'name' => 'Database Isolation',
                'severity' => 'critical',
                'evaluation_method' => 'hybrid',
                'definition' => [
                    'checks' => ['foreign_keys', 'query_scoping'],
                ],
            ]);

            $assessment = Assessment::create([
                'program_id' => $program->id,
                'phase_id' => $phase->id,
                'assessment_number' => 1,
                'status' => 'completed',
                'assessor_id' => $assessor->id,
                'baseline_commit' => 'abc123def456',
                'environment' => [
                    'php' => '8.3',
                    'laravel' => '13',
                    'database' => 'sqlite',
                ],
                'ruleset_version' => '1.0.0',
                'started_at' => now()->subHour(),
                'finished_at' => now(),
            ]);

            $assessment->criteria()->create([
                'criterion_version_id' => $criterionVersion->id,
                'criterion_key' => 'database_isolation',
                'criterion_version' => 1,
                'name' => 'Database Isolation',
                'description' => 'Database isolation verification.',
                'severity' => 'critical',
                'evaluation_method' => 'hybrid',
                'definition' => [
                    'checks' => ['foreign_keys', 'query_scoping'],
                ],
            ]);

            $finding = Finding::create([
                'assessment_id' => $assessment->id,
                'criterion_version_id' => $criterionVersion->id,
                'result' => Finding::RESULT_FAIL,
                'severity' => Finding::SEVERITY_CRITICAL,
                'summary' => 'Database isolation check failed.',
                'details' => [
                    'failed_checks' => ['query_scoping'],
                    'expected' => 'Scoped queries',
                    'actual' => 'Unscoped query detected',
                ],
            ]);

            $firstResolution = $finding->resolutions()->create([
                'resolution' => FindingResolution::REMEDIATION_PLANNED,
                'rationale' => 'Remediation has been scheduled.',
                'review_by' => now()->addDays(7),
                'evidence_reference' => [
                    'ticket' => 'EVP-101',
                    'references' => ['issue-101'],
                ],
                'recorded_by' => $recorder->id,
            ]);

            $secondResolution = $finding->resolutions()->create([
                'resolution' => FindingResolution::REMEDIATION_COMPLETED,
                'rationale' => 'The remediation has been completed.',
                'review_by' => null,
                'evidence_reference' => [
                    'commit' => 'def456abc789',
                    'tests' => ['Feature/DatabaseIsolationTest'],
                ],
                'recorded_by' => $recorder->id,
            ]);

            $finding->refresh();
            $firstResolution->refresh();
            $secondResolution->refresh();

            // Finding belongs to assessment.
            $this->assertTrue($finding->assessment->is($assessment));

            // Assessment has one finding.
            $this->assertCount(1, $assessment->findings);
            $this->assertTrue(
                $assessment->findings->first()->is($finding)
            );

            // Finding has multiple resolutions.
            $this->assertCount(2, $finding->resolutions);
            $this->assertSame(
                [$firstResolution->id, $secondResolution->id],
                $finding->resolutions->pluck('id')->all()
            );

            // Resolution belongs to finding.
            $this->assertTrue($firstResolution->finding->is($finding));
            $this->assertTrue($secondResolution->finding->is($finding));

            // Resolution belongs to its recorder.
            $this->assertTrue($firstResolution->recorder->is($recorder));
            $this->assertTrue($secondResolution->recorder->is($recorder));

            // Finding JSON cast.
            $this->assertIsArray($finding->details);
            $this->assertSame(
                ['query_scoping'],
                $finding->details['failed_checks']
            );

            // Finding datetime cast and append-only timestamp.
            $this->assertInstanceOf(Carbon::class, $finding->created_at);
            $this->assertNull($finding->getUpdatedAtColumn());

            // Finding result and severity.
            $this->assertSame(Finding::RESULT_FAIL, $finding->result);
            $this->assertSame(
                Finding::SEVERITY_CRITICAL,
                $finding->severity
            );

            // Resolution JSON casts.
            $this->assertIsArray($firstResolution->evidence_reference);
            $this->assertSame(
                'EVP-101',
                $firstResolution->evidence_reference['ticket']
            );

            $this->assertIsArray($secondResolution->evidence_reference);
            $this->assertSame(
                'def456abc789',
                $secondResolution->evidence_reference['commit']
            );

            // Resolution datetime casts.
            $this->assertInstanceOf(Carbon::class, $firstResolution->review_by);
            $this->assertNull($secondResolution->review_by);
            $this->assertInstanceOf(Carbon::class, $firstResolution->created_at);

            // Resolution append-only timestamp.
            $this->assertNull($firstResolution->getUpdatedAtColumn());

            // Resolution values.
            $this->assertSame(
                FindingResolution::REMEDIATION_PLANNED,
                $firstResolution->resolution
            );
            $this->assertSame(
                FindingResolution::REMEDIATION_COMPLETED,
                $secondResolution->resolution
            );

            // Nullable summary and details are supported by the schema.
            $this->assertSame(
                'Database isolation check failed.',
                $finding->summary
            );
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('evolution_model');
            config(['database.connections.evolution_model' => null]);
        }
    }
}
