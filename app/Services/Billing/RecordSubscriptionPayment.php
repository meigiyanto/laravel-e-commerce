<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordSubscriptionPayment
{
    public function handle(
        Invoice $invoice,
        string $provider,
        string $providerReference,
        int $amount,
        string $currency
    ): SubscriptionPayment {
        return DB::transaction(function () use (
            $invoice,
            $provider,
            $providerReference,
            $amount,
            $currency
        ) {
            $lockedInvoice = Invoice::query()
                ->lockForUpdate()
                ->findOrFail($invoice->id);

            if ($lockedInvoice->status === 'paid') {
                return SubscriptionPayment::query()
                    ->where('invoice_id', $lockedInvoice->id)
                    ->where('status', 'paid')
                    ->firstOrFail();
            }

            if (
                $lockedInvoice->total !== $amount
                || $lockedInvoice->currency !== strtoupper($currency)
            ) {
                throw ValidationException::withMessages([
                    'payment' => 'Nominal atau mata uang pembayaran tidak cocok.',
                ]);
            }

            $subscription = Subscription::query()
                ->lockForUpdate()
                ->findOrFail($lockedInvoice->subscription_id);

            if ((int) $subscription->tenant_id !== (int) $lockedInvoice->tenant_id) {
                throw ValidationException::withMessages([
                    'payment' => 'Invoice dan subscription berbeda tenant.',
                ]);
            }

            $existing = SubscriptionPayment::query()
                ->where('provider', $provider)
                ->where('provider_reference', $providerReference)
                ->first();

            if ($existing && $existing->status === 'paid') {
                return $existing;
            }

            $payment = SubscriptionPayment::updateOrCreate(
                [
                    'provider' => $provider,
                    'provider_reference' => $providerReference,
                ],
                [
                    'tenant_id' => $lockedInvoice->tenant_id,
                    'invoice_id' => $lockedInvoice->id,
                    'status' => 'paid',
                    'currency' => strtoupper($currency),
                    'amount' => $amount,
                    'paid_at' => now(),
                ]
            );

            $lockedInvoice->update([
                'status' => 'paid',
                'paid_at' => now(),
            ]);

            $periodStart = $subscription->current_period_ends_at
                && $subscription->current_period_ends_at->isFuture()
                    ? $subscription->current_period_ends_at
                    : now();

            $periodEnd = $subscription->billing_cycle === 'yearly'
                ? $periodStart->copy()->addYear()
                : $periodStart->copy()->addMonth();

            $subscription->update([
                'status' => Subscription::STATUS_ACTIVE,
                'current_period_starts_at' => $periodStart,
                'current_period_ends_at' => $periodEnd,
                'trial_ends_at' => null,
                'ends_at' => null,
            ]);

            return $payment;
        });
    }
}
