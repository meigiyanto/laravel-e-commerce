<?php

namespace App\Models\Evolution;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Finding extends Model
{
    protected $table = 'evolution_findings';

    public const UPDATED_AT = null;

    public const RESULT_PASS = 'pass';
    public const RESULT_FAIL = 'fail';
    public const RESULT_PARTIAL = 'partial';
    public const RESULT_NOT_APPLICABLE = 'not_applicable';
    public const RESULT_ERROR = 'error';

    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_LOW = 'low';
    public const SEVERITY_INFO = 'info';

    protected $fillable = [
        'assessment_id',
        'criterion_version_id',
        'result',
        'severity',
        'summary',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'details' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(
            Assessment::class,
            'assessment_id'
        );
    }

    public function resolutions(): HasMany
    {
        return $this->hasMany(
            FindingResolution::class,
            'finding_id'
        )->orderBy('created_at')->orderBy('id');
    }

    public function evidenceAssets(): HasMany
    {
        return $this->hasMany(
            EvidenceAsset::class,
            'finding_id'
        )->orderBy('created_at')->orderBy('id');
    }

    public function criterionVersion(): BelongsTo
    {
        return $this->belongsTo(CriterionVersion::class, 'criterion_version_id');
    }
}