<?php

namespace App\Models\Evolution;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FindingResolution extends Model
{
    protected $table = 'evolution_finding_resolutions';

    public const UPDATED_AT = null;

    public const ACCEPTED = 'accepted';
    public const REMEDIATION_PLANNED = 'remediation_planned';
    public const REMEDIATION_COMPLETED = 'remediation_completed';
    public const DEFERRED = 'deferred';
    public const WAIVED = 'waived';

    protected $fillable = [
        'finding_id',
        'resolution',
        'rationale',
        'review_by',
        'evidence_reference',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'review_by' => 'datetime',
            'evidence_reference' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(
            Finding::class,
            'finding_id'
        );
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'recorded_by'
        );
    }
}
