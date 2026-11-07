<?php

namespace App\Models\Evolution;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramPhase extends Model
{
    protected $table = 'evolution_program_phases';

    public const UPDATED_AT = null;

    protected $fillable = [
        'program_id',
        'phase_key',
        'name',
        'description',
        'sequence',
        'entry_requirements',
        'exit_requirements',
        'is_initial',
        'is_terminal',
        'is_target',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'entry_requirements' => 'array',
            'exit_requirements' => 'array',
            'is_initial' => 'boolean',
            'is_terminal' => 'boolean',
            'is_target' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function outgoingTransitions(): HasMany
    {
        return $this->hasMany(PhaseTransition::class, 'from_phase_id');
    }

    public function incomingTransitions(): HasMany
    {
        return $this->hasMany(PhaseTransition::class, 'to_phase_id');
    }

    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'phase_id');
    }
}