<?php

namespace App\Http\Controllers;

use App\Models\Order;
// use app\Services\RefundService;

class OrderController extends Controller
{
    /**
     * Daftar order milik user yang sedang login.
     */
    public function index()
    {
        $orders = Order::query()
            ->where('user_id', auth()->id())
            ->with('payment')
            ->latest()
            ->paginate(10);

        return view('storefront.orders.index', compact('orders'));
    }

    /**
     * Detail satu order.
     */
    public function show(Order $order)
    {
        /*
         * User hanya boleh melihat order miliknya sendiri.
         */
        abort_unless(
            $order->user_id === auth()->id(),
            403
        );

        $order->load([
            'items.product',
            'payment',
        ]);

        /*
         * Jika pembayaran Stripe masih pending
         * tetapi PaymentIntent sudah ada,
         * sinkronkan status dari Stripe.
         *
         * Dengan ini halaman /orders/{order}
         * tidak akan terus menampilkan pending
         * jika Stripe sebenarnya sudah succeeded.
         */
        if (
            $order->payment &&
            $order->payment->payment_method === 'stripe' &&
            $order->payment->status !== 'succeeded' &&
            $order->payment->stripe_payment_intent_id
        ) {
            app(PaymentController::class)
                ->syncPaymentFromStripe(
                    $order,
                    $order->payment->stripe_payment_intent_id
                );

            /*
             * Ambil ulang data terbaru setelah
             * sinkronisasi.
             */
            $order->refresh();

            $order->load([
                'items.product',
                'payment',
            ]);
        }

        return view('storefront.orders.show', compact('order')
        );
    }
}
