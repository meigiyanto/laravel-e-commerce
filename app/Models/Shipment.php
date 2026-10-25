<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_LABEL_CREATED = 'label_created';
    public const STATUS_READY_TO_SHIP = 'ready_to_ship';
    public const STATUS_PICKED_UP = 'picked_up';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_OUT_FOR_DELIVERY = 'out_for_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_RETURNED = 'returned';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_id',
        'provider_code',
        'service_code',
        'service_name',
        'provider_shipment_id',
        'tracking_number',
        'status',
        'shipping_cost',
        'weight_grams',
        'length_cm',
        'width_cm',
        'height_cm',
        'recipient_name',
        'recipient_phone',
        'recipient_address',
        'idempotency_key',
        'label_created_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'shipping_cost' => 'decimal:2',
            'weight_grams' => 'integer',
            'length_cm' => 'integer',
            'width_cm' => 'integer',
            'height_cm' => 'integer',
            'recipient_address' => 'array',
            'label_created_at' => 'datetime',
            'shipped_at' => 'datetime',
            'delivered_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class)
            ->orderBy('occurred_at');
    }

    public function latestEvent(): HasMany
    {
        return $this->hasMany(ShipmentEvent::class)
            ->latestOfMany('occurred_at');
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }
}
