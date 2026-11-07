<?php

namespace App\Models\Evolution;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Program extends Model
{
    protected $table = 'evolution_programs';

    protected $fillable = [
        'code',
        'name',
        'description',
        'status',
        'created_by',
        'started_at',
        'completed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function phases(): HasMany
    {
        return $this->hasMany(ProgramPhase::class, 'program_id')->orderBy('sequence');
    }

    public function initialPhase(): HasOne
    {
        return $this->hasOne(ProgramPhase::class, 'program_id')->where('is_initial', true);
    }

    public function targetPhase(): HasOne
    {
        return $this->hasOne(ProgramPhase::class, 'program_id')->where('is_target', true);
    }

    public function phaseTransitions(): HasMany
    {
        return $this->hasMany(PhaseTransition::class, 'program_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(EvolutionEvent::class, 'program_id');
    }
    
    public function assessments(): HasMany
    {
        return $this->hasMany(Assessment::class, 'program_id');
    }
}