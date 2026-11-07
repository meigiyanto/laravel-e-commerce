<?php

namespace App\Models\Evolution;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvidenceAsset extends Model
{
    protected $table = 'evolution_evidence_assets';

    public const UPDATED_AT = null;

    public const TYPE_ASSESSMENT_RESULT = 'assessment_result';
    public const TYPE_REVIEW_SUPPORT = 'review_support';
    public const TYPE_REMEDIATION = 'remediation';

    protected $fillable = [
        'assessment_id',
        'finding_id',
        'evidence_type',
        'storage_disk',
        'storage_path',
        'original_filename',
        'mime_type',
        'size_bytes',
        'sha256',
        'metadata',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function finding(): BelongsTo
    {
        return $this->belongsTo(Finding::class, 'finding_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}