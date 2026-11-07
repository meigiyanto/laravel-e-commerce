<?php

namespace App\Models\Evolution;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Review extends Model
{
    protected $table = 'evolution_reviews';

    public const UPDATED_AT = null;

    public const OUTCOME_ACCEPTED_FOR_APPROVAL = 'accepted_for_approval';
    public const OUTCOME_CHANGES_REQUESTED = 'changes_requested';
    public const OUTCOME_REJECTED = 'rejected';

    protected $fillable = [
        'assessment_id',
        'reviewer_id',
        'outcome',
        'rationale',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function approval(): HasOne
    {
        return $this->hasOne(Approval::class, 'review_id');
    }
}