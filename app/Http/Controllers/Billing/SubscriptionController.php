<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Billing\SubscriptionService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubscriptionController extends Controller
{
    public function index() {
        $tenant = app(TenantContext::class)->require();

        return response()->json([
            'subscription' => Subscription::query()
            ->with('plan.features')
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->first(),
            'plans' => SubscriptionPlan::query()
            ->where('is_active', true)
            ->with('features')
            ->orderBy('sort_order')
            ->get(),
        ]);
    }

    public function subscribe(
        Request $request,
        SubscriptionService $service,
        \App\Services\Billing\BillingService $billing
    ) {
        abort_unless($request->user()->hasTenantRole('owner', 'admin'), 403);

        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'billing_cycle' => ['required', Rule::in(['monthly', 'yearly'])],
        ]);

        $tenant = app(TenantContext::class)->require();

        $plan = SubscriptionPlan::query()
        ->where('is_active', true)
        ->findOrFail($data['plan_id']);

        $subscription = $service->subscribe(
            $tenant,
            $plan,
            $data['billing_cycle']
        );

        $invoice = $subscription->status === Subscription::STATUS_PENDING
        ? $billing->createRenewalInvoice($subscription)
        : null;

        return response()->json([
            'message' => 'Subscription berhasil dibuat.',
            'subscription' => $subscription->load('plan'),
            'invoice' => $invoice,
        ], 201);
    }
    
    public function cancel(
        Request $request,
        SubscriptionService $service
    ) {
        abort_unless($request->user()->hasTenantRole('owner', 'admin'), 403);

        $tenant = app(TenantContext::class)->require();

        $subscription = Subscription::query()
        ->where('tenant_id', $tenant->id)
        ->whereIn('status', [
            Subscription::STATUS_ACTIVE,
            Subscription::STATUS_TRIALING,
            Subscription::STATUS_PAST_DUE,
        ])
        ->latest()
        ->firstOrFail();

        return response()->json([
            'subscription' => $service->cancel($subscription),
        ]);
    }
}