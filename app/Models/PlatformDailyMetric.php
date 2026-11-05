<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformDailyMetric extends Model
{
    protected $fillable = [
        'metric_date', 'total_tenants', 'active_tenants',
        'new_tenants', 'total_subscriptions',
        'active_subscriptions', 'revenue', 'currency',
    ];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'total_tenants' => 'integer',
            'active_tenants' => 'integer',
            'new_tenants' => 'integer',
            'total_subscriptions' => 'integer',
            'active_subscriptions' => 'integer',
            'revenue' => 'integer',
        ];
    }
}
