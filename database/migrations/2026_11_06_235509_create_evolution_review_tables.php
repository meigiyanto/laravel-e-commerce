<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolution_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->foreignId('reviewer_id');
            $table->string('outcome', 32);
            $table->text('rationale');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(
                'assessment_id',
                'uq_evp_reviews_assessment'
            );

            $table->unique(
                ['assessment_id', 'id'],
                'uq_evp_reviews_assessment_pair'
            );

            $table->index('outcome', 'idx_evp_reviews_outcome');
            $table->index('reviewer_id', 'idx_evp_reviews_reviewer');

            $table->foreign(
                'assessment_id',
                'fk_evp_reviews_assessment'
            )->references('id')
                ->on('evolution_assessments')
                ->restrictOnDelete();

            $table->foreign(
                'reviewer_id',
                'fk_evp_reviews_reviewer'
            )->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_reviews');
    }
};
