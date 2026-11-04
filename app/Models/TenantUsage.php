<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantUsage extends Model
{
    protected $fillable = [
        'tenant_id', 'feature_key', 'used', 'period_start', 'period_end',
    ];

    protected function casts(): array
    {
        return [
            'used' => 'integer',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
