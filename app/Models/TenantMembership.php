<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantMembership extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'role',
        'status',
        'invited_at',
        'accepted_at',
    ];

    protected function casts(): array
    {
        return [
            'invited_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }
}