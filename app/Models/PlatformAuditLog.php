<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class PlatformAuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'tenant_id',
        'action',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Platform audit logs are immutable.');
        });

        static::deleting(function () {
            throw new LogicException('Platform audit logs cannot be deleted.');
        });
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}