<?php

namespace App\Models\Evolution;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assessment extends Model
{
    protected $table = 'evolution_assessments';

    protected $fillable = [
        'program_id',
        'phase_id',
        'assessment_number',
        'status',
        'assessor_id',
        'supersedes_assessment_id',
        'baseline_commit',
        'environment',
        'ruleset_version',
        'started_at',
        'finished_at',
        'cancelled_at',
        'cancelled_by',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'assessment_number' => 'integer',
            'environment' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProgramPhase::class, 'phase_id');
    }

    public function assessor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessor_id');
    }

    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function supersededAssessment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_assessment_id');
    }

    public function supersedingAssessments(): HasMany
    {
        return $this->hasMany(self::class, 'supersedes_assessment_id');
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(AssessmentCriterion::class, 'assessment_id')->orderBy('criterion_key');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(Finding::class, 'assessment_id')
        ->orderBy('created_at')
        ->orderBy('id');
    }

    public function evidenceAssets(): HasMany
    {
        return $this->hasMany(
            EvidenceAsset::class,
            'assessment_id'
        )->orderBy('created_at')->orderBy('id');
    }
}