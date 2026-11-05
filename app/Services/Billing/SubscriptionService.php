<?php

namespace App\Services\Billing;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    public const STATUS_PENDING = 'pending';
    
    public function subscribe(
        Tenant $tenant,
        SubscriptionPlan $plan,
        string $cycle = 'monthly'
    ): Subscription {
        if (!$plan->is_active) {
            throw ValidationException::withMessages([
                'plan' => 'Paket langganan tidak tersedia.',
            ]);
        }

        if (!in_array($cycle, ['monthly', 'yearly'], true)) {
            throw ValidationException::withMessages([
                'billing_cycle' => 'Siklus pembayaran tidak valid.',
            ]);
        }

        return DB::transaction(function () use ($tenant, $plan, $cycle) {
            $existing = Subscription::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('status', [
                    Subscription::STATUS_PENDING,
                    Subscription::STATUS_TRIALING,
                    Subscription::STATUS_ACTIVE,
                    Subscription::STATUS_PAST_DUE,
                ])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'subscription' => 'Tenant sudah memiliki langganan berjalan.',
                ]);
            }

            $now = now();
            $trialDays = (int) $plan->trial_days;
            $amount = $cycle === 'yearly' ? $plan->price_yearly : $plan->price_monthly;
            $status = $trialEnd ? Subscription::STATUS_TRIALING : ($amount === 0 ? Subscription::STATUS_ACTIVE : Subscription::STATUS_PENDING);

            return Subscription::create([
                'tenant_id' => $tenant->id,
                'subscription_plan_id' => $plan->id,
                'status' => $status,
                'billing_cycle' => $cycle,
                'currency' => $plan->currency,
                'amount' => $amount,
                'trial_ends_at' => $trialEnd,
                'starts_at' => $now,
                'current_period_starts_at' => $now,
                'current_period_ends_at' => $trialEnd ?? $this->periodEnd($now, $cycle),
            ]);
        });
    }

    public function cancel(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $locked = Subscription::query()
                ->lockForUpdate()
                ->findOrFail($subscription->id);

            if (in_array($locked->status, [
                Subscription::STATUS_CANCELLED,
                Subscription::STATUS_EXPIRED,
            ], true)) {
                return $locked;
            }

            $locked->update([
                'status' => Subscription::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'ends_at' => $locked->current_period_ends_at ?? now(),
            ]);

            return $locked->fresh();
        });
    }

    public function renewPeriod(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            $locked = Subscription::query()
                ->lockForUpdate()
                ->findOrFail($subscription->id);

            if (!in_array($locked->status, [
                Subscription::STATUS_ACTIVE,
                Subscription::STATUS_TRIALING,
            ], true)) {
                throw ValidationException::withMessages([
                    'subscription' => 'Langganan tidak dapat diperpanjang.',
                ]);
            }

            $start = $locked->current_period_ends_at ?? now();

            $locked->update([
                'status' => Subscription::STATUS_ACTIVE,
                'current_period_starts_at' => $start,
                'current_period_ends_at' => $this->periodEnd(
                    $start,
                    $locked->billing_cycle
                ),
                'trial_ends_at' => null,
            ]);

            return $locked->fresh();
        });
    }

    private function periodEnd($start, string $cycle)
    {
        return $cycle === 'yearly' ? $start->copy()->addYear() : $start->copy()->addMonth();
    }
}
