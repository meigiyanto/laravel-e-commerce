<?php

namespace Tests\Feature\Evolution;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GovernanceMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('evolution_programs', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('evolution_program_phases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->unique(['program_id', 'id']);
        });

        Schema::create('evolution_assessments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->unique(['program_id', 'id']);
        });

        Schema::create('evolution_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->unique(['assessment_id', 'id']);
        });

        $migration = require database_path(
            'migrations/2026_11_06_235510_create_evolution_governance_tables.php'
        );

        $migration->up();
    }

    private function seedGovernanceParents(): void
    {
        DB::table('users')->insert([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ]);

        DB::table('evolution_programs')->insert([
            ['id' => 1],
            ['id' => 2],
        ]);

        DB::table('evolution_program_phases')->insert([
            ['id' => 1, 'program_id' => 1],
            ['id' => 2, 'program_id' => 1],
            ['id' => 3, 'program_id' => 2],
        ]);

        DB::table('evolution_assessments')->insert([
            ['id' => 1, 'program_id' => 1],
            ['id' => 2, 'program_id' => 2],
        ]);

        DB::table('evolution_reviews')->insert([
            ['id' => 1, 'assessment_id' => 1],
            ['id' => 2, 'assessment_id' => 2],
        ]);
    }

    public function test_governance_tables_have_expected_structure_and_foreign_keys(): void
    {
        $this->assertTrue(Schema::hasTable('evolution_approvals'));
        $this->assertTrue(Schema::hasTable('evolution_phase_transitions'));
        $this->assertTrue(Schema::hasTable('evolution_events'));

        $this->assertCount(
            3,
            DB::select('PRAGMA foreign_key_list(evolution_approvals)')
        );
        
        $this->assertCount(
            8,
            DB::select('PRAGMA foreign_key_list(evolution_phase_transitions)')
        );

        $this->assertCount(
            4,
            DB::select('PRAGMA foreign_key_list(evolution_events)')
        );

        $this->assertTrue(Schema::hasColumns('evolution_approvals', [
            'assessment_id',
            'review_id',
            'approver_id',
            'outcome',
            'decision_rationale',
            'decided_at',
        ]));

        $this->assertTrue(Schema::hasColumns('evolution_phase_transitions', [
            'program_id',
            'from_phase_id',
            'to_phase_id',
            'assessment_id',
            'requested_by',
            'decided_by',
            'status',
            'rationale',
            'decision_rationale',
            'decided_at',
        ]));

        $this->assertTrue(Schema::hasColumns('evolution_events', [
            'program_id',
            'assessment_id',
            'actor_type',
            'actor_id',
            'event_key',
            'payload',
            'created_at',
        ]));
    }

    public function test_approval_rejects_review_from_another_assessment(): void
    {
        $this->seedGovernanceParents();

        DB::table('evolution_approvals')->insert([
            'assessment_id' => 1,
            'review_id' => 1,
            'approver_id' => 3,
            'outcome' => 'approved',
            'decision_rationale' => 'Approval valid.',
            'decided_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('evolution_approvals')->insert([
            'assessment_id' => 1,
            'review_id' => 2,
            'approver_id' => 3,
            'outcome' => 'approved',
            'decision_rationale' => 'Review milik assessment lain.',
            'decided_at' => now(),
        ]);
    }

    public function test_phase_transition_rejects_cross_program_references(): void
    {
        $this->seedGovernanceParents();

        $this->expectException(QueryException::class);

        DB::table('evolution_phase_transitions')->insert([
            'program_id' => 1,
            'from_phase_id' => 1,
            'to_phase_id' => 3,
            'assessment_id' => 1,
            'requested_by' => 1,
            'status' => 'pending',
            'rationale' => 'Cross-program transition must fail.',
        ]);
    }

    public function test_event_rejects_assessment_from_another_program(): void
    {
        $this->seedGovernanceParents();

        $this->expectException(QueryException::class);

        DB::table('evolution_events')->insert([
            'program_id' => 1,
            'assessment_id' => 2,
            'actor_type' => 'system',
            'event_key' => 'assessment.completed',
            'payload' => json_encode(['status' => 'completed']),
        ]);
    }

    public function test_approval_allows_only_one_record_per_assessment(): void
    {
        $this->seedGovernanceParents();

        DB::table('evolution_approvals')->insert([
            'assessment_id' => 1,
            'review_id' => 1,
            'approver_id' => 3,
            'outcome' => 'approved',
            'decision_rationale' => 'First approval.',
            'decided_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('evolution_approvals')->insert([
            'assessment_id' => 1,
            'review_id' => 1,
            'approver_id' => 2,
            'outcome' => 'rejected',
            'decision_rationale' => 'Duplicate approval.',
            'decided_at' => now(),
        ]);
    }
}
