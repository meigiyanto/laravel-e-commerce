<?php

namespace App\Models\Evolution;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssessmentCriterion extends Model
{
    protected $table = 'evolution_assessment_criteria';

    public const UPDATED_AT = null;

    protected $fillable = [
        'assessment_id',
        'criterion_version_id',
        'criterion_key',
        'criterion_version',
        'name',
        'description',
        'severity',
        'evaluation_method',
        'definition',
    ];

    protected function casts(): array
    {
        return [
            'criterion_version' => 'integer',
            'definition' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class,'assessment_id');
    }

    public function criterionVersion(): BelongsTo
    {
        return $this->belongsTo(CriterionVersion::class,'criterion_version_id');
    }
}
