<?php

namespace App\Services\Platform;

use App\Models\PlatformDailyMetric;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class PlatformAnalyticsService
{
    public function snapshot(): array
    {
        return [
            'tenants' => [
                'total' => Tenant::query()->count(),
                'active' => Tenant::query()->where('status', 'active')->count(),
                'suspended' => Tenant::query()->where('status', 'suspended')->count(),
                'new_today' => Tenant::query()
                    ->whereDate('created_at', today())
                    ->count(),
            ],
            'subscriptions' => [
                'total' => Subscription::query()->count(),
                'active' => Subscription::query()
                    ->where('status', Subscription::STATUS_ACTIVE)
                    ->count(),
                'trialing' => Subscription::query()
                    ->where('status', Subscription::STATUS_TRIALING)
                    ->count(),
                'expired' => Subscription::query()
                    ->where('status', Subscription::STATUS_EXPIRED)
                    ->count(),
            ],
            'revenue' => [
                'today' => SubscriptionPayment::query()
                    ->where('status', 'paid')
                    ->whereDate('paid_at', today())
                    ->sum('amount'),
                'this_month' => SubscriptionPayment::query()
                    ->where('status', 'paid')
                    ->whereBetween('paid_at', [
                        now()->startOfMonth(),
                        now()->endOfMonth(),
                    ])
                    ->sum('amount'),
            ],
        ];
    }

    public function recordDailySnapshot(): PlatformDailyMetric
    {
        $payments = SubscriptionPayment::query()
            ->where('status', 'paid')
            ->whereDate('paid_at', today());

        return PlatformDailyMetric::updateOrCreate(
            ['metric_date' => today()->toDateString()],
            [
                'total_tenants' => Tenant::query()->count(),
                'active_tenants' => Tenant::query()
                    ->where('status', 'active')->count(),
                'new_tenants' => Tenant::query()
                    ->whereDate('created_at', today())->count(),
                'total_subscriptions' => Subscription::query()->count(),
                'active_subscriptions' => Subscription::query()
                    ->where('status', Subscription::STATUS_ACTIVE)->count(),
                'revenue' => (int) $payments->sum('amount'),
                'currency' => 'IDR',
            ]
        );
    }
}
