<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolution_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 64);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status', 32)->default('paused');
            $table->foreignId('created_by');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->unique('code', 'uq_evp_programs_code');
            $table->index('status', 'idx_evp_programs_status');
            $table->index('created_by', 'idx_evp_programs_creator');

            $table->foreign('created_by', 'fk_evp_programs_creator')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();
        });

        Schema::create('evolution_program_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id');
            $table->string('phase_key', 64);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('sequence');
            $table->json('entry_requirements');
            $table->json('exit_requirements');
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_terminal')->default(false);
            $table->boolean('is_target')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['program_id', 'phase_key'],
                'uq_evp_phases_program_key'
            );

            $table->unique(
                ['program_id', 'sequence'],
                'uq_evp_phases_program_sequence'
            );

            $table->unique(
                ['program_id', 'id'],
                'uq_evp_phases_program_pair'
            );

            $table->index(
                ['program_id', 'is_initial'],
                'idx_evp_phases_initial'
            );

            $table->index(
                ['program_id', 'is_target'],
                'idx_evp_phases_target'
            );

            $table->foreign('program_id', 'fk_evp_phases_program')
                ->references('id')
                ->on('evolution_programs')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_program_phases');
        Schema::dropIfExists('evolution_programs');
    }
};
