<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantMembership extends Model
{
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';
    public const ROLE_PRODUCT_MANAGER = 'product_manager';
    public const ROLE_INVENTORY_MANAGER = 'inventory_manager';
    public const ROLE_ORDER_MANAGER = 'order_manager';
    public const ROLE_STAFF = 'staff';

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
        return $this->role === self::ROLE_OWNER;
    }
}