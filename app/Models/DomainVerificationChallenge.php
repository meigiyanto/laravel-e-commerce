<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainVerificationChallenge extends Model
{
    protected $fillable = [
        'store_domain_id', 'method', 'record_name', 'record_value',
        'expires_at', 'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
        ];
    }

    public function storeDomain(): BelongsTo
    {
        return $this->belongsTo(StoreDomain::class);
    }
}
