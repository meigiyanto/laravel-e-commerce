<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private function isMultiTenant(): bool
    {
        return config('operational.mode', 'single_store') === 'multi_tenant';
    }

    private function tenantId(): ?int
    {
        return $this->isMultiTenant()
            ? (int) app(TenantContext::class)->require()->getKey()
            : null;
    }

    private function assertOrderAccess(Order $order): void
    {
        if (
            $this->isMultiTenant() &&
            (int) $order->tenant_id !== $this->tenantId()
        ) {
            abort(404);
        }

        abort_unless(
            (int) $order->user_id === (int) auth()->id(),
            404
        );
    }

    /**
     * Daftar order milik user yang sedang login.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $ordersQuery = Order::query()
            ->where('user_id', $user->id);

        if ($this->isMultiTenant()) {
            $ordersQuery->where('tenant_id', $this->tenantId());
        } else {
            $ordersQuery->whereNull('tenant_id');
        }

        $orders = $ordersQuery
            ->with('payment')
            ->latest()
            ->paginate(10);

        return view('storefront.account.orders.index', compact(
            'user',
            'orders'
        ));
    }

    /**
     * Detail satu order.
     */
    public function show(Order $order)
    {
        $this->assertOrderAccess($order);

        $order->load([
            'items.product',
            'payment',
            'cancellationRequests',
        ]);

        /*
         * Sinkronisasi Stripe jika PaymentIntent sudah tersedia
         * tetapi status lokal belum succeeded.
         */
        if (
            $order->payment &&
            $order->payment->payment_method === 'stripe' &&
            $order->payment->status !== 'succeeded' &&
            $order->payment->stripe_payment_intent_id
        ) {
            app(PaymentController::class)->syncPaymentFromStripe(
                $order,
                $order->payment->stripe_payment_intent_id
            );

            $order->refresh();
            $order->load(['items.product', 'payment']);
        }

        $canCancelOrder = $this->customerCanCancel($order);

        return view('storefront.account.orders.show', compact(
            'order',
            'canCancelOrder'
        ));
    }

    /**
     * Menentukan apakah customer masih boleh mengajukan
     * permintaan pembatalan untuk pesanan ini.
     */
    private function customerCanCancel(Order $order): bool
    {
        if ($order->status !== 'pending') {
            return false;
        }

        $requests = $order->relationLoaded('cancellationRequests')
            ? $order->cancellationRequests
            : $order->cancellationRequests()->get();

        return ! $requests->contains(
            fn ($request) => in_array(
                $request->status,
                ['pending', 'approved'],
                true
            )
        );
    }
}