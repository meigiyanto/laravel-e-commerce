<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'tenant_id', 'invoice_id', 'provider', 'provider_reference',
        'status', 'currency', 'amount', 'paid_at', 'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
