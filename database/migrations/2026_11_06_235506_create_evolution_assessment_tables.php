<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolution_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id');
            $table->foreignId('phase_id');
            $table->unsignedInteger('assessment_number');
            $table->string('status', 32)->default('draft');
            $table->foreignId('assessor_id');
            $table->unsignedBigInteger('supersedes_assessment_id')->nullable();
            $table->string('baseline_commit', 64);
            $table->json('environment');
            $table->string('ruleset_version', 64);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();

            $table->unique(
                ['program_id', 'assessment_number'],
                'uq_evp_assessments_program_number'
            );

            $table->unique(
                ['program_id', 'id'],
                'uq_evp_assessments_program_pair'
            );

            $table->index('status', 'idx_evp_assessments_status');
            $table->index(
                ['program_id', 'phase_id'],
                'idx_evp_assessments_program_phase'
            );
            $table->index(
                ['program_id', 'supersedes_assessment_id'],
                'idx_evp_assessments_supersedes'
            );
            $table->index('assessor_id', 'idx_evp_assessments_assessor');
            $table->index('cancelled_by', 'idx_evp_assessments_cancelled_by');

            $table->foreign('program_id', 'fk_evp_assessments_program')
                ->references('id')
                ->on('evolution_programs')
                ->restrictOnDelete();

            $table->foreign('assessor_id', 'fk_evp_assessments_assessor')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign('cancelled_by', 'fk_evp_assessments_cancelled_by')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->foreign(
                ['program_id', 'phase_id'],
                'fk_evp_assessments_program_phase'
            )->references(['program_id', 'id'])
                ->on('evolution_program_phases')
                ->restrictOnDelete();

            $table->foreign(
                ['program_id', 'supersedes_assessment_id'],
                'fk_evp_assessments_supersedes'
            )->references(['program_id', 'id'])
                ->on('evolution_assessments')
                ->restrictOnDelete();
        });

        Schema::create('evolution_assessment_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id');
            $table->foreignId('criterion_version_id');
            $table->string('criterion_key', 100);
            $table->unsignedInteger('criterion_version');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('severity', 20);
            $table->string('evaluation_method', 20);
            $table->json('definition');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['assessment_id', 'criterion_version_id'],
                'uq_evp_assessment_criteria_version'
            );

            $table->index(
                'criterion_version_id',
                'idx_evp_assessment_criteria_version'
            );

            $table->foreign(
                'assessment_id',
                'fk_evp_assessment_criteria_assessment'
            )->references('id')
                ->on('evolution_assessments')
                ->restrictOnDelete();

            $table->foreign(
                'criterion_version_id',
                'fk_evp_assessment_criteria_version'
            )->references('id')
                ->on('evolution_criteria_versions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_assessment_criteria');
        Schema::dropIfExists('evolution_assessments');
    }
};
