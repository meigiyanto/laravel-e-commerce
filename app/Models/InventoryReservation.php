<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryReservation extends Model
{
    public const STATUS_RESERVED = 'reserved';
    public const STATUS_COMMITTED = 'committed';
    public const STATUS_RELEASED = 'released';

    protected $fillable = [
        'product_variant_id',
        'idempotency_key',
        'reference_type',
        'reference_id',
        'quantity',
        'status',
        'committed_at',
        'released_at',
    ];

    protected function casts(): array
    {
        return [
            'reference_id' => 'integer',
            'quantity' => 'integer',
            'committed_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(
            ProductVariant::class,
            'product_variant_id'
        );
    }
}
