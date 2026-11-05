<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantUsage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UsageLimitService
{
    public function limit(Tenant $tenant, string $feature): ?int
    {
        $subscription = $this->currentSubscription($tenant);

        if (!$subscription || !$subscription->isEntitled()) {
            return 0;
        }

        $value = $subscription->plan
        ->features()
        ->where('feature_key', $feature)
        ->first();

        if (!$value) {
            return 0;
        }

        if ($value->value_type === 'unlimited') {
            return null;
        }

        $parsed = $value->parsedValue();

        return is_numeric($parsed) ? (int) $parsed : 0;
    }

    public function used(Tenant $tenant, string $feature): int
    {
        $periodStart = now()->startOfMonth()->toDateString();
        $periodEnd = now()->endOfMonth()->toDateString();

        return (int) TenantUsage::query()
        ->where('tenant_id', $tenant->id)
        ->where('feature_key', $feature)
        ->where('period_start', $periodStart)
        ->where('period_end', $periodEnd)
        ->value('used');
    }

    public function assertCanUse(
        Tenant $tenant,
        string $feature,
        int $quantity = 1
    ): void {
        $limit = $this->limit($tenant, $feature);

        if ($limit === null) {
            return;
        }

        if ($limit <= 0 || $this->used($tenant, $feature) + $quantity > $limit) {
            throw ValidationException::withMessages([
                'usage' => "Batas penggunaan fitur {$feature} telah tercapai.",
            ]);
        }
    }

    public function consume(
        Tenant $tenant,
        string $feature,
        int $quantity = 1
    ): TenantUsage {
        if ($quantity < 1) {
            throw new \InvalidArgumentException('Quantity harus lebih besar dari nol.');
        }

        return DB::transaction(function () use ($tenant, $feature, $quantity) {
            $subscription = Subscription::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('status', [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIALING,
            ])
            ->with('plan.features')
            ->lockForUpdate()
            ->latest('id')
            ->first();

            if (!$subscription || !$subscription->isEntitled()) {
                throw ValidationException::withMessages([
                    'subscription' => 'Subscription tidak aktif.',
                ]);
            }

            $featurePlan = $subscription->plan->features
            ->firstWhere('feature_key', $feature);

            if (!$featurePlan) {
                throw ValidationException::withMessages([
                    'usage' => 'Fitur tidak tersedia pada paket ini.',
                ]);
            }

            $limit = $featurePlan->value_type === 'unlimited'
            ? null
            : (int) $featurePlan->parsedValue();

            $start = now()->startOfMonth()->toDateString();
            $end = now()->endOfMonth()->toDateString();

            TenantUsage::query()->firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'feature_key' => $feature,
                    'period_start' => $start,
                    'period_end' => $end,
                ],
                ['used' => 0]
            );

            $usage = TenantUsage::query()
            ->where('tenant_id', $tenant->id)
            ->where('feature_key', $feature)
            ->where('period_start', $start)
            ->where('period_end', $end)
            ->lockForUpdate()
            ->firstOrFail();

            if ($limit !== null && $usage->used + $quantity > $limit) {
                throw ValidationException::withMessages([
                    'usage' => "Batas penggunaan fitur {$feature} telah tercapai.",
                ]);
            }

            $usage->increment('used', $quantity);

            return $usage->fresh();
        });
    }

    private function currentSubscription(Tenant $tenant): ?Subscription
    {
        return Subscription::query()
        ->with(['plan.features'])
        ->where('tenant_id',
            $tenant->id)
        ->whereIn('status',
            [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIALING,
            ])
        ->latest('id')
        ->first();
    }
}