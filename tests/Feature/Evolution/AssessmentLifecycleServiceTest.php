<?php

namespace Tests\Feature\Evolution;

use App\Models\Evolution\Assessment;
use App\Models\Evolution\Criterion;
use App\Models\Evolution\EvolutionEvent;
use App\Models\Evolution\Program;
use App\Models\User;
use App\Services\Evolution\AssessmentLifecycleService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AssessmentLifecycleServiceTest extends TestCase
{
    private string $originalConnection;

    private AssessmentLifecycleService $service;

    private User $assessor;

    private Program $program;

    private $phase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalConnection = DB::getDefaultConnection();
        $sqliteConfig = config('database.connections.sqlite');
        $sqliteConfig['database'] = ':memory:';
        $sqliteConfig['foreign_key_constraints'] = true;

        config(['database.connections.evolution_lifecycle' => $sqliteConfig]);
        DB::purge('evolution_lifecycle');
        DB::setDefaultConnection('evolution_lifecycle');

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

            Schema::create('evolution_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('program_id');
                $table->unsignedBigInteger('assessment_id')->nullable();
                $table->string('actor_type', 20);
                $table->foreignId('actor_id')->nullable();
                $table->string('event_key', 100);
                $table->json('payload')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->foreign('program_id')->references('id')->on('evolution_programs');
                $table->foreign(['program_id', 'assessment_id'])
                ->references(['program_id', 'id'])->on('evolution_assessments');
                $table->foreign('actor_id')->references('id')->on('users');
            });

            Schema::create('evolution_findings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('assessment_id');
                $table->unsignedBigInteger('criterion_version_id');
                $table->string('result', 30);
                $table->string('severity', 20);
                $table->text('summary')->nullable();
                $table->json('details')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(
                    ['assessment_id', 'criterion_version_id'],
                    'uq_evp_findings_assessment_criterion'
                );

                $table->foreign('assessment_id')
                ->references('id')
                ->on('evolution_assessments')
                ->restrictOnDelete();

                $table->foreign('criterion_version_id')
                ->references('id')
                ->on('evolution_criteria_versions')
                ->restrictOnDelete();
            });

            $this->assessor = User::factory()->create();
            $this->program = Program::create([
                'code' => 'EVOLUTION-LIFECYCLE-001',
                'name' => 'Lifecycle Test',
                'status' => 'active',
                'created_by' => $this->assessor->id,
            ]);
            
            $this->phase = $this->program->phases()->create([
                'phase_key' => 'single_store',
                'name' => 'Single Store',
                'sequence' => 1,
                'entry_requirements' => [],
                'exit_requirements' => [],
                'is_initial' => true,
            ]);

            $criterion = Criterion::create([
                'criterion_key' => 'database_isolation',
                'is_active' => true,
            ]);
            $criterion->versions()->create([
                'version' => 1,
                'name' => 'Database Isolation',
                'severity' => 'critical',
                'evaluation_method' => 'hybrid',
                'definition' => ['checks' => ['foreign_keys']],
            ]);

            $this->service = app(AssessmentLifecycleService::class);
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->originalConnection);
        DB::purge('evolution_lifecycle');
        config(['database.connections.evolution_lifecycle' => null]);

        parent::tearDown();
    }

    public function test_create_draft_snapshots_active_criteria_and_records_event(): void
    {
        $assessment = $this->createDraft();

        $this->assertSame('draft', $assessment->status);
        $this->assertSame(1, $assessment->assessment_number);
        $this->assertCount(1, $assessment->criteria);
        $this->assertSame('database_isolation', $assessment->criteria->first()->criterion_key);
        $this->assertDatabaseHas('evolution_events', [
            'assessment_id' => $assessment->id,
            'event_key' => 'assessment.created',
        ]);
    }

    public function test_start_moves_draft_to_running_and_records_event(): void
    {
        $assessment = $this->createDraft();

        $running = $this->service->start($assessment, $this->assessor);

        $this->assertSame('running', $running->status);
        $this->assertNotNull($running->started_at);
        $this->assertDatabaseHas('evolution_events', [
            'assessment_id' => $assessment->id,
            'event_key' => 'assessment.started',
        ]);
    }

    public function test_fail_moves_running_assessment_to_failed(): void
    {
        $assessment = $this->service->start($this->createDraft(), $this->assessor);

        $failed = $this->service->fail($assessment, $this->assessor, 'Environment check failed.');

        $this->assertSame('failed', $failed->status);
        $this->assertNotNull($failed->finished_at);
        $event = EvolutionEvent::query()
        ->where('assessment_id', $failed->id)
        ->where('event_key', 'assessment.failed')
        ->firstOrFail();
        $this->assertSame('Environment check failed.', $event->payload['reason']);
    }

    public function test_cancel_draft_records_cancellation_and_actor(): void
    {
        $assessment = $this->createDraft();

        $cancelled = $this->service->cancel($assessment, $this->assessor, 'No longer required.');

        $this->assertSame('cancelled', $cancelled->status);
        $this->assertSame($this->assessor->id, $cancelled->cancelled_by);
        $this->assertSame('No longer required.', $cancelled->cancellation_reason);
        $this->assertNull($cancelled->finished_at);
        $this->assertNotNull($cancelled->cancelled_at);
    }

    public function test_assessor_cannot_cancel_running_assessment(): void
    {
        $assessment = $this->service->start($this->createDraft(), $this->assessor);

        $this->expectException(ValidationException::class);

        $this->service->cancel($assessment, $this->assessor, 'Attempted cancellation.');
    }

    public function test_invalid_transition_from_completed_state_is_rejected(): void
    {
        $assessment = $this->createDraft();
        $assessment->forceFill(['status' => 'completed'])->save();

        $this->expectException(ValidationException::class);

        $this->service->start($assessment, $this->assessor);
    }

    private function createDraft(): Assessment
    {
        return $this->service->createDraft(
            $this->program,
            $this->phase,
            $this->assessor,
            'abc123',
            ['database' => 'sqlite'],
            '1.0.0',
        );
    }


    public function test_record_finding_saves_result_and_records_event(): void
    {
        $assessment = $this->service->start(
            $this->createDraft(),
            $this->assessor
        );

        $criterionVersionId = $assessment->criteria->first()->criterion_version_id;

        $finding = $this->service->recordFinding(
            $assessment,
            $this->assessor,
            $criterionVersionId,
            'pass',
            'low',
            'Database isolation is valid.',
            ['checks' => ['foreign_keys' => true]],
        );

        $this->assertDatabaseHas('evolution_findings', [
            'id' => $finding->id,
            'assessment_id' => $assessment->id,
            'criterion_version_id' => $criterionVersionId,
            'result' => 'pass',
            'severity' => 'low',
        ]);

        $this->assertDatabaseHas('evolution_events', [
            'assessment_id' => $assessment->id,
            'event_key' => 'finding.recorded',
        ]);

        $this->assertSame(
            ['checks' => ['foreign_keys' => true]],
            $finding->details
        );
    }

    public function test_record_finding_rejects_criterion_outside_assessment_snapshot(): void
    {
        $assessment = $this->service->start(
            $this->createDraft(),
            $this->assessor
        );

        $otherCriterion = Criterion::create([
            'criterion_key' => 'external_criterion',
            'is_active' => true,
        ]);

        $otherVersion = $otherCriterion->versions()->create([
            'version' => 1,
            'name' => 'External Criterion',
            'severity' => 'high',
            'evaluation_method' => 'manual',
            'definition' => ['checks' => []],
        ]);

        $this->expectException(ValidationException::class);

        $this->service->recordFinding(
            $assessment,
            $this->assessor,
            $otherVersion->id,
            'pass',
            'low'
        );
    }

    public function test_record_finding_rejects_duplicate_criterion(): void
    {
        $assessment = $this->service->start(
            $this->createDraft(),
            $this->assessor
        );

        $criterionVersionId = $assessment->criteria->first()->criterion_version_id;

        $this->service->recordFinding(
            $assessment,
            $this->assessor,
            $criterionVersionId,
            'pass',
            'low'
        );

        try {
            $this->service->recordFinding(
                $assessment,
                $this->assessor,
                $criterionVersionId,
                'fail',
                'high'
            );

            $this->fail('Duplicate finding should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'criterion_version_id',
                $exception->errors()
            );
        }

        $this->assertDatabaseCount('evolution_findings', 1);
    }

    public function test_assessment_cannot_complete_with_missing_findings(): void
    {
        $assessment = $this->service->start(
            $this->createDraft(),
            $this->assessor
        );

        $this->expectException(ValidationException::class);

        $this->service->complete($assessment, $this->assessor);
    }

    public function test_assessment_completes_after_all_findings_are_recorded(): void
    {
        $assessment = $this->service->start(
            $this->createDraft(),
            $this->assessor
        );

        foreach ($assessment->criteria as $criterion) {
            $this->service->recordFinding(
                $assessment,
                $this->assessor,
                $criterion->criterion_version_id,
                'pass',
                'low',
                'Criterion passed.'
            );
        }

        $completed = $this->service->complete(
            $assessment->fresh(),
            $this->assessor
        );

        $this->assertSame('completed', $completed->status);
        $this->assertNotNull($completed->finished_at);

        $this->assertDatabaseHas('evolution_events', [
            'assessment_id' => $assessment->id,
            'event_key' => 'assessment.completed',
        ]);
    }

    public function test_non_assessor_cannot_record_finding(): void
    {
        $assessment = $this->service->start(
            $this->createDraft(),
            $this->assessor
        );

        $otherUser = User::factory()->create();

        $this->expectException(ValidationException::class);

        $this->service->recordFinding(
            $assessment,
            $otherUser,
            $assessment->criteria->first()->criterion_version_id,
            'pass',
            'low'
        );
    }

    public function test_finding_cannot_be_recorded_on_draft_assessment(): void
    {
        $assessment = $this->createDraft();

        $this->expectException(ValidationException::class);

        $this->service->recordFinding(
            $assessment,
            $this->assessor,
            $assessment->criteria->first()->criterion_version_id,
            'pass',
            'low'
        );
    }

}