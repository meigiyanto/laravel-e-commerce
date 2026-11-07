<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolution_finding_resolutions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('finding_id');
            $table->string('resolution', 32);
            $table->text('rationale');
            $table->timestamp('review_by')->nullable();
            $table->json('evidence_reference')->nullable();
            $table->unsignedBigInteger('recorded_by');
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                'finding_id',
                'idx_evp_resolutions_finding'
            );

            $table->index(
                'resolution',
                'idx_evp_resolutions_type'
            );

            $table->index(
                'review_by',
                'idx_evp_resolutions_review'
            );

            $table->index(
                'recorded_by',
                'idx_evp_resolutions_actor'
            );

            $table->foreign(
                'finding_id',
                'fk_evp_resolutions_finding'
            )->references('id')
                ->on('evolution_findings')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreign(
                'recorded_by',
                'fk_evp_resolutions_actor'
            )->references('id')
                ->on('users')
                ->restrictOnDelete()
                ->restrictOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_finding_resolutions');
    }
};
