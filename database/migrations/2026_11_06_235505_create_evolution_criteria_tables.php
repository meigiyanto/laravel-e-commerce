<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolution_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('criterion_key', 100);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('criterion_key', 'uq_evp_criteria_key');
            $table->index('is_active', 'idx_evp_criteria_active');
        });

        Schema::create('evolution_criteria_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criterion_id');
            $table->unsignedInteger('version');
            $table->string('name', 150);
            $table->text('description')->nullable();
            $table->string('severity', 20);
            $table->string('evaluation_method', 20);
            $table->json('definition');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['criterion_id', 'version'],
                'uq_evp_criteria_versions_number'
            );

            $table->index(
                'severity',
                'idx_evp_criteria_versions_severity'
            );

            $table->index(
                'evaluation_method',
                'idx_evp_criteria_versions_method'
            );

            $table->foreign(
                'criterion_id',
                'fk_evp_criteria_versions_criterion'
            )->references('id')
                ->on('evolution_criteria')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_criteria_versions');
        Schema::dropIfExists('evolution_criteria');
    }
};
