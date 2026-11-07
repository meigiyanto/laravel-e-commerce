<?php

namespace App\Models\Evolution;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CriterionVersion extends Model
{
    protected $table = 'evolution_criteria_versions';

    public const UPDATED_AT = null;

    protected $fillable = [
        'criterion_id',
        'version',
        'name',
        'description',
        'severity',
        'evaluation_method',
        'definition',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'definition' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criterion_id');
    }

    public function assessmentCriteria(): HasMany
    {
        return $this->hasMany(AssessmentCriterion::class, 'criterion_version_id');
    }
}