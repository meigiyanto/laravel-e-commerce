<?php

namespace Tests\Feature\Evolution;

use App\Models\Evolution\Assessment;
use App\Models\Evolution\Program;
use App\Models\Evolution\Criterion;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AssessmentModelTest extends TestCase
{
    public function test_assessment_and_criterion_snapshot_relationships_and_casts_work(): void
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

            foreach ([
                '2026_11_06_235504_create_evolution_program_tables.php',
                '2026_11_06_235505_create_evolution_criteria_tables.php',
                '2026_11_06_235506_create_evolution_assessment_tables.php',
            ] as $file) {
                $migration = require database_path("migrations/{$file}");
                $migration->up();
            }

            $assessor = User::factory()->create();
            $canceller = User::factory()->create();

            $program = Program::create([
                'code' => 'EVOLUTION-ASSESSMENT-001',
                'name' => 'Assessment Test',
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

            $snapshot = $assessment->criteria()->create([
                'criterion_version_id' => $criterionVersion->id,
                'criterion_key' => 'database_isolation',
                'criterion_version' => 1,
                'name' => 'Database Isolation',
                'description' => 'Snapshot of database isolation checks.',
                'severity' => 'critical',
                'evaluation_method' => 'hybrid',
                'definition' => [
                    'checks' => ['foreign_keys', 'query_scoping'],
                ],
            ]);

            $followUp = Assessment::create([
                'program_id' => $program->id,
                'phase_id' => $phase->id,
                'assessment_number' => 2,
                'status' => 'draft',
                'assessor_id' => $assessor->id,
                'supersedes_assessment_id' => $assessment->id,
                'baseline_commit' => 'def456abc789',
                'environment' => ['database' => 'sqlite'],
                'ruleset_version' => '1.0.0',
            ]);

            $cancelledAssessment = Assessment::create([
                'program_id' => $program->id,
                'phase_id' => $phase->id,
                'assessment_number' => 3,
                'status' => 'cancelled',
                'assessor_id' => $assessor->id,
                'baseline_commit' => 'ghi789abc123',
                'environment' => ['database' => 'sqlite'],
                'ruleset_version' => '1.0.0',
                'cancelled_by' => $canceller->id,
                'cancelled_at' => now(),
                'cancellation_reason' => 'Test cancellation.',
            ]);

            $assessment->refresh();
            $snapshot->refresh();
            $followUp->refresh();
            $cancelledAssessment->refresh();

            // BelongsTo relationships.
            $this->assertTrue($assessment->program->is($program));
            $this->assertTrue($assessment->phase->is($phase));
            $this->assertTrue($assessment->assessor->is($assessor));
            $this->assertTrue($cancelledAssessment->canceller->is($canceller));

            // Superseding assessment relationships.
            $this->assertTrue($followUp->supersededAssessment->is($assessment));
            $this->assertTrue(
                $assessment->supersedingAssessments->contains(
                    fn ($item) => $item->is($followUp)
                )
            );

            // Assessment criteria snapshot relationships.
            $this->assertCount(1, $assessment->criteria);
            $this->assertTrue($snapshot->assessment->is($assessment));
            $this->assertTrue(
                $snapshot->criterionVersion->is($criterionVersion)
            );

            // Assessment casts.
            $this->assertIsInt($assessment->assessment_number);
            $this->assertSame(1, $assessment->assessment_number);
            $this->assertIsArray($assessment->environment);
            $this->assertSame('8.3', $assessment->environment['php']);
            $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $assessment->started_at);
            $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $assessment->finished_at);

            // Snapshot casts and timestamp behavior.
            $this->assertSame(1, $snapshot->criterion_version);
            $this->assertIsArray($snapshot->definition);
            $this->assertSame(
                ['foreign_keys', 'query_scoping'],
                $snapshot->definition['checks']
            );
            $this->assertNotNull($snapshot->created_at);
            $this->assertNull($snapshot->getUpdatedAtColumn());

            // Nullable cancellation and superseding relationships.
            $this->assertNull($assessment->canceller);
            $this->assertNull($assessment->supersededAssessment);
            $this->assertSame('cancelled', $cancelledAssessment->status);
            $this->assertSame('Test cancellation.', $cancelledAssessment->cancellation_reason);
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('evolution_model');
            config(['database.connections.evolution_model' => null]);
        }
    }
}
