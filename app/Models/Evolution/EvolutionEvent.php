<?php

namespace App\Models\Evolution;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EvolutionEvent extends Model
{
    protected $table = 'evolution_events';

    public const UPDATED_AT = null;

    public const ACTOR_PLATFORM_ADMIN = 'platform_admin';
    public const ACTOR_SYSTEM = 'system';

    protected $fillable = [
        'program_id',
        'assessment_id',
        'actor_type',
        'actor_id',
        'event_key',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    public function assessment(): BelongsTo
    {
        return $this->belongsTo(Assessment::class, 'assessment_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}