<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShipmentEvent extends Model
{
    protected $fillable = [
        'shipment_id',
        'provider_event_id',
        'provider_status',
        'status',
        'description',
        'location',
        'occurred_at',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'payload' => 'array',
        ];
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }
}
