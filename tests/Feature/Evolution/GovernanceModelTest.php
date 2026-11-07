<?php

namespace Tests\Feature\Evolution;

use App\Models\Evolution\Approval;
use App\Models\Evolution\Assessment;
use App\Models\Evolution\EvolutionEvent;
use App\Models\Evolution\PhaseTransition;
use App\Models\Evolution\Program;
use App\Models\Evolution\Review;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GovernanceModelTest extends TestCase
{
    public function test_governance_models_relationships_and_casts_work(): void
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

            $migrations = [
                '2026_11_06_235504_create_evolution_program_tables.php',
                '2026_11_06_235505_create_evolution_criteria_tables.php',
                '2026_11_06_235506_create_evolution_assessment_tables.php',
                '2026_11_06_235509_create_evolution_review_tables.php',
                '2026_11_06_235510_create_evolution_governance_tables.php',
            ];

            foreach ($migrations as $file) {
                $migration = require database_path("migrations/{$file}");
                $migration->up();
            }

            $creator = User::factory()->create();
            $assessor = User::factory()->create();
            $reviewer = User::factory()->create();
            $approver = User::factory()->create();
            $requester = User::factory()->create();
            $decider = User::factory()->create();

            $program = Program::create([
                'code' => 'GOVERNANCE-001',
                'name' => 'Governance Test',
                'created_by' => $creator->id,
            ]);

            $fromPhase = $program->phases()->create([
                'phase_key' => 'initial',
                'name' => 'Initial',
                'sequence' => 1,
                'entry_requirements' => [],
                'exit_requirements' => [],
                'is_initial' => true,
            ]);

            $toPhase = $program->phases()->create([
                'phase_key' => 'target',
                'name' => 'Target',
                'sequence' => 2,
                'entry_requirements' => [],
                'exit_requirements' => [],
                'is_target' => true,
            ]);

            $assessment = Assessment::create([
                'program_id' => $program->id,
                'phase_id' => $fromPhase->id,
                'assessment_number' => 1,
                'status' => 'awaiting_approval',
                'assessor_id' => $assessor->id,
                'baseline_commit' => 'abc123def456',
                'environment' => ['database' => 'sqlite'],
                'ruleset_version' => '1.0.0',
            ]);

            $review = Review::create([
                'assessment_id' => $assessment->id,
                'reviewer_id' => $reviewer->id,
                'outcome' => Review::OUTCOME_ACCEPTED_FOR_APPROVAL,
                'rationale' => 'Assessment passed review.',
            ]);

            $approval = Approval::create([
                'assessment_id' => $assessment->id,
                'review_id' => $review->id,
                'approver_id' => $approver->id,
                'outcome' => Approval::OUTCOME_APPROVED,
                'decision_rationale' => 'Approved.',
                'decided_at' => now(),
            ]);

            $transition = PhaseTransition::create([
                'program_id' => $program->id,
                'from_phase_id' => $fromPhase->id,
                'to_phase_id' => $toPhase->id,
                'assessment_id' => $assessment->id,
                'requested_by' => $requester->id,
                'decided_by' => $decider->id,
                'status' => PhaseTransition::STATUS_APPROVED,
                'rationale' => 'Request phase progression.',
                'decision_rationale' => 'Transition approved.',
                'decided_at' => now(),
            ]);

            $event = EvolutionEvent::create([
                'program_id' => $program->id,
                'assessment_id' => $assessment->id,
                'actor_type' => EvolutionEvent::ACTOR_PLATFORM_ADMIN,
                'actor_id' => $approver->id,
                'event_key' => 'assessment.approved',
                'payload' => [
                    'status' => 'approved',
                    'previous_status' => 'awaiting_approval',
                ],
            ]);

            $systemEvent = EvolutionEvent::create([
                'program_id' => $program->id,
                'assessment_id' => null,
                'actor_type' => EvolutionEvent::ACTOR_SYSTEM,
                'actor_id' => null,
                'event_key' => 'program.maintenance',
                'payload' => ['source' => 'scheduler'],
            ]);

            $review->refresh();
            $approval->refresh();
            $transition->refresh();
            $event->refresh();
            $systemEvent->refresh();

            // Review relationships.
            $this->assertTrue($review->assessment->is($assessment));
            $this->assertTrue($review->reviewer->is($reviewer));
            $this->assertTrue($assessment->review->is($review));
            $this->assertTrue($review->approval->is($approval));

            // Approval relationships.
            $this->assertTrue($approval->assessment->is($assessment));
            $this->assertTrue($approval->review->is($review));
            $this->assertTrue($approval->approver->is($approver));
            $this->assertTrue($assessment->approval->is($approval));

            // Phase transition relationships.
            $this->assertTrue($transition->program->is($program));
            $this->assertTrue($transition->fromPhase->is($fromPhase));
            $this->assertTrue($transition->toPhase->is($toPhase));
            $this->assertTrue($transition->assessment->is($assessment));
            $this->assertTrue($transition->requester->is($requester));
            $this->assertTrue($transition->decider->is($decider));

            $this->assertTrue(
                $fromPhase->outgoingTransitions->contains(
                    fn ($item) => $item->is($transition)
                )
            );

            $this->assertTrue(
                $toPhase->incomingTransitions->contains(
                    fn ($item) => $item->is($transition)
                )
            );

            $this->assertTrue(
                $assessment->phaseTransitions->contains(
                    fn ($item) => $item->is($transition)
                )
            );

            // Event relationships.
            $this->assertTrue($event->program->is($program));
            $this->assertTrue($event->assessment->is($assessment));
            $this->assertTrue($event->actor->is($approver));
            $this->assertTrue(
                $assessment->events->contains(
                    fn ($item) => $item->is($event)
                )
            );

            $this->assertTrue(
                $program->events->contains(
                    fn ($item) => $item->is($event)
                )
            );

            // Event payload and timestamp casts.
            $this->assertIsArray($event->payload);
            $this->assertSame('approved', $event->payload['status']);
            $this->assertSame(
                'awaiting_approval',
                $event->payload['previous_status']
            );

            $this->assertInstanceOf(
                \Illuminate\Support\Carbon::class,
                $event->created_at
            );

            // Other governance timestamp casts.
            $this->assertInstanceOf(
                \Illuminate\Support\Carbon::class,
                $review->created_at
            );

            $this->assertInstanceOf(
                \Illuminate\Support\Carbon::class,
                $approval->decided_at
            );

            $this->assertInstanceOf(
                \Illuminate\Support\Carbon::class,
                $transition->decided_at
            );

            // Nullable event relationships.
            $this->assertNull($systemEvent->assessment);
            $this->assertNull($systemEvent->actor);
            $this->assertSame(
                EvolutionEvent::ACTOR_SYSTEM,
                $systemEvent->actor_type
            );

            // Timestamp behavior.
            $this->assertNull($review->getUpdatedAtColumn());
            $this->assertNull($approval->getUpdatedAtColumn());
            $this->assertNull($event->getUpdatedAtColumn());
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('evolution_model');
            config(['database.connections.evolution_model' => null]);
        }
    }
}
