<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolution_findings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedBigInteger('criterion_version_id');
            $table->string('result', 24);
            $table->string('severity', 20);
            $table->text('summary')->nullable();
            $table->json('details')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                ['assessment_id', 'criterion_version_id'],
                'uq_evp_findings_assessment_criterion'
            );

            $table->unique(
                ['assessment_id', 'id'],
                'uq_evp_findings_assessment_pair'
            );

            $table->index('result', 'idx_evp_findings_result');
            $table->index('severity', 'idx_evp_findings_severity');

            $table->foreign(
                ['assessment_id', 'criterion_version_id'],
                'fk_evp_findings_assessment_criterion'
            )->references(['assessment_id', 'criterion_version_id'])
                ->on('evolution_assessment_criteria')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_findings');
    }
};
