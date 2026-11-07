<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evolution_evidence_assets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('assessment_id');
            $table->unsignedBigInteger('finding_id')->nullable();
            $table->string('evidence_type', 32);
            $table->string('storage_disk', 64);
            $table->string('storage_path', 1024);
            $table->string('original_filename', 255);
            $table->string('mime_type', 127)->nullable();
            $table->unsignedBigInteger('size_bytes');
            $table->char('sha256', 64);
            $table->json('metadata')->nullable();
            $table->foreignId('uploaded_by');
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['assessment_id', 'created_at'],
                'idx_evp_evidence_assessment_created'
            );
            $table->index(
                ['finding_id', 'created_at'],
                'idx_evp_evidence_finding_created'
            );
            $table->index('evidence_type', 'idx_evp_evidence_type');
            $table->index('sha256', 'idx_evp_evidence_sha256');
            $table->index('uploaded_by', 'idx_evp_evidence_uploader');

            $table->foreign(
                'assessment_id',
                'fk_evp_evidence_assessment'
            )->references('id')
                ->on('evolution_assessments')
                ->restrictOnDelete();

            $table->foreign(
                ['assessment_id', 'finding_id'],
                'fk_evp_evidence_finding'
            )->references(['assessment_id', 'id'])
                ->on('evolution_findings')
                ->restrictOnDelete();

            $table->foreign(
                'uploaded_by',
                'fk_evp_evidence_uploader'
            )->references('id')
                ->on('users')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evolution_evidence_assets');
    }
};