<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Store extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'status',
        'settings',
    ];

    protected function casts(): array
    {
        return ['settings' => 'array'];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(StoreDomain::class);
    }

    public function primaryDomain(): HasMany
    {
        return $this->domains()->where('is_primary', true);
    }
}