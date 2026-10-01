<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'provider',
        'reference_id',
        'payment_session_id',
        'payment_request_id',
        'xendit_payment_id',
        'payment_link_url',
        'currency',
        'status',
        'transaction_id',
        'payment_type',
        'transaction_status',
        'fraud_status',
        'status_code',
        'gross_amount',
        'paid_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
