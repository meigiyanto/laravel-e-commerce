<?php

namespace Tests\Feature\Evolution;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhaseTransitionProtectionTest extends TestCase
{
    private string $connection = 'evolution_transition_test';
    private string $originalConnection;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->originalConnection = DB::getDefaultConnection();
        $config = config('database.connections.sqlite');
        $config['database'] = ':memory:';
        $config['foreign_key_constraints'] = true;

        config([
            "database.connections.{$this->connection}" => $config,
        ]);

        DB::purge($this->connection);
        DB::setDefaultConnection($this->connection);

        Schema::connection($this->connection)->create(
            'evolution_phase_transitions',
            function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('program_id');
                $table->unsignedBigInteger('from_phase_id');
                $table->unsignedBigInteger('to_phase_id');
                $table->unsignedBigInteger('assessment_id');
                $table->unsignedBigInteger('requested_by');
                $table->unsignedBigInteger('decided_by')->nullable();
                $table->string('status', 20)->default('pending');
                $table->text('rationale');
                $table->text('decision_rationale')->nullable();
                $table->timestamp('decided_at')->nullable();
                $table->timestamps();
            }
        );

        $migration = require database_path(
            'migrations/2026_11_07_181000_protect_evolution_phase_transitions.php'
        );

        $migration->up();
    }

    protected function tearDown(): void
    {
        DB::setDefaultConnection($this->originalConnection);
        DB::purge($this->connection);
        config(["database.connections.{$this->connection}" => null]);

        parent::tearDown();
    }

    private function assertRejected(callable $callback): void
    {
        try {
            $callback();
            $this->fail('Database operation should have been rejected.');
        } catch (QueryException) {
            $this->assertTrue(true);
        }
    }

    private function transitionData(array $overrides = []): array
    {
        return array_merge([
            'program_id' => 1,
            'from_phase_id' => 10,
            'to_phase_id' => 20,
            'assessment_id' => 100,
            'requested_by' => 1,
            'status' => 'pending',
            'rationale' => 'Request phase transition.',
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides);
    }

    public function test_transition_must_be_inserted_as_pending(): void
    {
        $this->assertRejected(fn () =>
            DB::table('evolution_phase_transitions')->insert(
                $this->transitionData(['status' => 'approved'])
            )
        );

        $this->assertRejected(fn () =>
            DB::table('evolution_phase_transitions')->insert(
                $this->transitionData([
                    'decided_by' => 2,
                    'decision_rationale' => 'Already decided.',
                    'decided_at' => now(),
                ])
            )
        );

        $this->assertDatabaseCount('evolution_phase_transitions', 0);
    }

    public function test_pending_transition_can_be_decided_only_once(): void
    {
        $id = DB::table('evolution_phase_transitions')->insertGetId(
            $this->transitionData()
        );

        $this->assertRejected(fn () =>
            DB::table('evolution_phase_transitions')
                ->where('id', $id)
                ->update(['program_id' => 2])
        );

        $this->assertRejected(fn () =>
            DB::table('evolution_phase_transitions')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'decided_by' => 1,
                    'decision_rationale' => 'Invalid self-decision.',
                    'decided_at' => now(),
                ])
        );

        $this->assertRejected(fn () =>
            DB::table('evolution_phase_transitions')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',
                    'decided_by' => 2,
                    'decision_rationale' => '',
                    'decided_at' => now(),
                ])
        );

        $this->assertRejected(fn () =>
            DB::table('evolution_phase_transitions')
                ->where('id', $id)
                ->update(['status' => 'approved'])
        );

        $updated = DB::table('evolution_phase_transitions')
            ->where('id', $id)
            ->update([
                'status' => 'approved',
                'decided_by' => 2,
                'decision_rationale' => 'Transition approved.',
                'decided_at' => now(),
                'updated_at' => now(),
            ]);

        $this->assertSame(1, $updated);

        $this->assertRejected(fn () =>
            DB::table('evolution_phase_transitions')
                ->where('id', $id)
                ->update([
                    'status' => 'rejected',
                    'decided_by' => 3,
                    'decision_rationale' => 'Second decision.',
                    'decided_at' => now(),
                ])
        );

        $this->assertRejected(fn () =>
            DB::table('evolution_phase_transitions')
                ->where('id', $id)
                ->update([
                    'decision_rationale' => 'Changed after decision.',
                ])
        );

        $this->assertRejected(fn () =>
            DB::table('evolution_phase_transitions')
                ->where('id', $id)
                ->delete()
        );

        $this->assertDatabaseHas('evolution_phase_transitions', [
            'id' => $id,
            'status' => 'approved',
            'decided_by' => 2,
            'decision_rationale' => 'Transition approved.',
        ]);
    }

    public function test_rollback_removes_phase_transition_protection(): void
    {
        $id = DB::table('evolution_phase_transitions')->insertGetId(
            $this->transitionData()
        );

        $migration = require database_path(
            'migrations/2026_11_07_181000_protect_evolution_phase_transitions.php'
        );

        $migration->down();

        $this->assertSame(
            1,
            DB::table('evolution_phase_transitions')
                ->where('id', $id)
                ->update(['rationale' => 'Updated after rollback.'])
        );

        $this->assertSame(
            1,
            DB::table('evolution_phase_transitions')
                ->where('id', $id)
                ->delete()
        );
    }
}
