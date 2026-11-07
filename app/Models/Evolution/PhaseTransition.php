<?php

namespace App\Models\Evolution;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhaseTransition extends Model
{
    protected $table = 'evolution_phase_transitions';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'program_id',
        'from_phase_id',
        'to_phase_id',
        'assessment_id',
        'requested_by',
        'decided_by',
        'status',
        'rationale',
        'decision_rationale',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function fromPhase(): BelongsTo
    {
        return $this->belongsTo(ProgramPhase::class, 'from_phase_id');
    }

    public function toPhase(): BelongsTo
    {
        return $this->belongsTo(ProgramPhase::class, 'to_phase_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}