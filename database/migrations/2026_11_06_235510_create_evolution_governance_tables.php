<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolution_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedBigInteger('review_id');
            $table->foreignId('approver_id');
            $table->string('outcome', 20);
            $table->text('decision_rationale');
            $table->timestamp('decided_at');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                'assessment_id',
                'uq_evp_approvals_assessment'
            );

            $table->index('review_id', 'idx_evp_approvals_review');
            $table->index('approver_id', 'idx_evp_approvals_approver');
            $table->index('outcome', 'idx_evp_approvals_outcome');

            $table->foreign(
                ['assessment_id', 'review_id'],
                'fk_evp_approvals_review'
            )->references(['assessment_id', 'id'])
                ->on('evolution_reviews')
                ->restrictOnDelete();

            $table->foreign(
                'approver_id',
                'fk_evp_approvals_approver'
            )->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::create('evolution_phase_transitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('from_phase_id');
            $table->unsignedBigInteger('to_phase_id');
            $table->unsignedBigInteger('assessment_id');
            $table->foreignId('requested_by');
            $table->foreignId('decided_by')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('rationale');
            $table->text('decision_rationale')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();

            $table->index(
                ['program_id', 'status'],
                'idx_evp_transitions_program_status'
            );

            $table->index(
                ['program_id', 'from_phase_id'],
                'idx_evp_transitions_from_phase'
            );

            $table->index(
                ['program_id', 'to_phase_id'],
                'idx_evp_transitions_to_phase'
            );

            $table->index(
                'assessment_id',
                'idx_evp_transitions_assessment'
            );

            $table->index(
                'requested_by',
                'idx_evp_transitions_requester'
            );

            $table->index(
                'decided_by',
                'idx_evp_transitions_decider'
            );

            $table->foreign(
                ['program_id', 'from_phase_id'],
                'fk_evp_transitions_from_phase'
            )->references(['program_id', 'id'])
                ->on('evolution_program_phases')
                ->restrictOnDelete();

            $table->foreign(
                ['program_id', 'to_phase_id'],
                'fk_evp_transitions_to_phase'
            )->references(['program_id', 'id'])
                ->on('evolution_program_phases')
                ->restrictOnDelete();

            $table->foreign(
                ['program_id', 'assessment_id'],
                'fk_evp_transitions_assessment'
            )->references(['program_id', 'id'])
                ->on('evolution_assessments')
                ->restrictOnDelete();

            $table->foreign(
                'requested_by',
                'fk_evp_transitions_requester'
            )->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(
                'decided_by',
                'fk_evp_transitions_decider'
            )->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::create('evolution_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('assessment_id')->nullable();
            $table->string('actor_type', 20);
            $table->foreignId('actor_id')->nullable();
            $table->string('event_key', 100);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['program_id', 'created_at'],
                'idx_evp_events_program_created'
            );

            $table->index(
                ['assessment_id', 'created_at'],
                'idx_evp_events_assessment_created'
            );

            $table->index(
                ['actor_type', 'actor_id'],
                'idx_evp_events_actor'
            );

            $table->index(
                'event_key',
                'idx_evp_events_key'
            );

            $table->foreign(
                'program_id',
                'fk_evp_events_program'
            )->references('id')
                ->on('evolution_programs')
                ->restrictOnDelete();

            $table->foreign(
                ['program_id', 'assessment_id'],
                'fk_evp_events_assessment'
            )->references(['program_id', 'id'])
                ->on('evolution_assessments')
                ->restrictOnDelete();

            $table->foreign(
                'actor_id',
                'fk_evp_events_actor'
            )->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_events');
        Schema::dropIfExists('evolution_phase_transitions');
        Schema::dropIfExists('evolution_approvals');
    }
};
