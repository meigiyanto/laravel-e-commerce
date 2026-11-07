<?php

namespace App\Models\Evolution;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Criterion extends Model
{
    protected $table = 'evolution_criteria';

    protected $fillable = [
        'criterion_key',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(
            EvolutionCriterionVersion::class,
            'criterion_id'
        )->orderBy('version');
    }
}
