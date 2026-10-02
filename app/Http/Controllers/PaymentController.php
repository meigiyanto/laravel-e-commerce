<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Throwable;

class PaymentController extends Controller
{
    public function show(Order $order)
    {
        abort_unless(
            $order->user_id === auth()->id(),
            403
        );

        $order->load('payment');

        if (!$order->payment) {
            abort(404, 'Data pembayaran tidak ditemukan.');
        }

        $payment = $order->payment;

        if ($payment->status === 'succeeded') {
            return redirect()->route(
                'checkout.success',
                $order
            );
        }

        if ($payment->status === 'failed') {
            return redirect()
                ->route('orders.show', $order)
                ->with(
                    'error',
                    'Pembayaran sebelumnya gagal. Silakan buat pesanan baru.'
                );
        }

        if ($payment->status === 'canceled') {
            return redirect()
                ->route('orders.show', $order)
                ->with(
                    'error',
                    'Pembayaran pesanan ini telah dibatalkan.'
                );
        }

        /*
         * COD tidak membutuhkan Stripe.
         */
        if ($payment->payment_method === 'cod') {
            return redirect()
                ->route('orders.show', $order)
                ->with(
                    'success',
                    'Pesanan COD berhasil dibuat. Pembayaran dilakukan saat pesanan diterima.'
                );
        }

        Stripe::setApiKey(
            config('services.stripe.secret')
        );

        $expectedAmount = $this->stripeAmount(
            $order->total
        );

        try {
            /*
             * Gunakan PaymentIntent yang sudah ada
             * jika sebelumnya pernah dibuat.
             */
            if ($payment->stripe_payment_intent_id) {

                $paymentIntent = PaymentIntent::retrieve(
                    $payment->stripe_payment_intent_id
                );

                /*
                 * PaymentIntent harus benar-benar
                 * milik order ini.
                 */
                if (
                    (string) ($paymentIntent->metadata->order_id ?? '')
                    !== (string) $order->id
                ) {
                    Log::critical(
                        'Stripe PaymentIntent order mismatch.',
                        [
                            'order_id' => $order->id,
                            'payment_intent_id' => $paymentIntent->id,
                        ]
                    );

                    abort(
                        409,
                        'PaymentIntent tidak sesuai dengan pesanan.'
                    );
                }

                /*
                 * Validasi nominal dan currency.
                 */
                if (
                    (int) $paymentIntent->amount !== $expectedAmount
                    ||
                    strtolower(
                        (string) $paymentIntent->currency
                    ) !== 'idr'
                ) {
                    Log::critical(
                        'Stripe amount mismatch.',
                        [
                            'order_id' => $order->id,
                            'payment_intent_id' => $paymentIntent->id,
                            'expected_amount' => $expectedAmount,
                            'stripe_amount' => $paymentIntent->amount,
                            'stripe_currency' => $paymentIntent->currency,
                        ]
                    );

                    abort(
                        409,
                        'Nominal pembayaran tidak sesuai dengan pesanan.'
                    );
                }
            } else {

                /*
                 * Untuk flow tanpa webhook,
                 * gunakan card sebagai metode pembayaran.
                 *
                 * Ini membuat proses pembayaran
                 * bersifat synchronous dan lebih sederhana.
                 */
                $paymentIntent = PaymentIntent::create([
                    'amount' => $expectedAmount,
                    'currency' => 'idr',

                    'payment_method_types' => [
                        'card',
                    ],

                    'description' =>
                        "MeiStore order {$order->order_number}",

                    'metadata' => [
                        'order_id' =>
                            (string) $order->id,

                        'order_number' =>
                            $order->order_number,
                    ],

                    'receipt_email' =>
                        $order->user?->email,
                ]);

                $payment->update([
                    'provider' => 'stripe',

                    'currency' => 'IDR',

                    'status' => 'pending',

                    'transaction_status' =>
                        $paymentIntent->status,

                    'stripe_payment_intent_id' =>
                        $paymentIntent->id,

                    'transaction_id' =>
                        $paymentIntent->id,

                    'gross_amount' =>
                        $order->total,

                    'metadata' => [
                        'stripe_payment_intent_status' =>
                            $paymentIntent->status,
                    ],
                ]);
            }
        } catch (ApiErrorException $e) {

            Log::error(
                'Stripe PaymentIntent API error.',
                [
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ]
            );

            abort(
                502,
                'Pembayaran Stripe tidak dapat disiapkan.'
            );
        } catch (Throwable $e) {

            Log::error(
                'Stripe PaymentIntent error.',
                [
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ]
            );

            abort(
                502,
                'Pembayaran Stripe tidak dapat disiapkan.'
            );
        }

        return view(
            'storefront.payment',
            [
                'order' => $order,
                'payment' => $payment->fresh(),
                'clientSecret' =>
                    $paymentIntent->client_secret,
                'publishableKey' =>
                    config('services.stripe.key'),
            ]
        );
    }

    /**
     * Server-side confirmation.
     *
     * Browser hanya mengirim PaymentIntent ID.
     * Nominal pembayaran TIDAK pernah dipercaya
     * dari browser.
     */
    public function confirm(
        Request $request,
        Order $order
    ) {
        abort_unless(
            $order->user_id === auth()->id(),
            403
        );

        $validated = $request->validate([
            'payment_intent_id' => [
                'required',
                'string',
            ],
        ]);

        $order->load('payment');

        if (!$order->payment) {
            return response()->json([
                'message' =>
                    'Data pembayaran tidak ditemukan.',
            ], 404);
        }

        $payment = $order->payment;

        if (
            $payment->status === 'succeeded'
        ) {
            return response()->json([
                'success' => true,
                'status' => 'succeeded',
                'redirect_url' =>
                    route(
                        'checkout.success',
                        $order
                    ),
            ]);
        }

        Stripe::setApiKey(
            config('services.stripe.secret')
        );

        try {

            /*
             * Jangan pernah mempercayai data
             * pembayaran dari browser.
             *
             * Browser hanya memberikan ID.
             * Data sebenarnya diambil langsung
             * dari Stripe menggunakan secret key.
             */
            $paymentIntent =
                PaymentIntent::retrieve(
                    $validated['payment_intent_id']
                );

            /*
             * PaymentIntent harus cocok dengan
             * Payment record milik order.
             */
            if (
                $payment->stripe_payment_intent_id
                !== $paymentIntent->id
            ) {
                return response()->json([
                    'message' =>
                        'PaymentIntent tidak sesuai dengan pesanan.',
                ], 409);
            }

            /*
             * Validasi metadata.
             */
            if (
                (string) (
                    $paymentIntent->metadata->order_id ?? ''
                )
                !== (string) $order->id
            ) {
                Log::critical(
                    'Stripe metadata order mismatch.',
                    [
                        'order_id' => $order->id,
                        'payment_intent_id' =>
                            $paymentIntent->id,
                    ]
                );

                return response()->json([
                    'message' =>
                        'PaymentIntent tidak sesuai dengan pesanan.',
                ], 409);
            }

            /*
             * Validasi amount.
             */
            $expectedAmount =
                $this->stripeAmount(
                    $order->total
                );

            if (
                (int) $paymentIntent->amount
                !== $expectedAmount
                ||
                strtolower(
                    (string) $paymentIntent->currency
                ) !== 'idr'
            ) {
                Log::critical(
                    'Stripe payment verification failed.',
                    [
                        'order_id' => $order->id,
                        'payment_intent_id' =>
                            $paymentIntent->id,
                        'expected_amount' =>
                            $expectedAmount,
                        'stripe_amount' =>
                            $paymentIntent->amount,
                        'stripe_currency' =>
                            $paymentIntent->currency,
                    ]
                );

                return response()->json([
                    'message' =>
                        'Nominal pembayaran tidak sesuai dengan pesanan.',
                ], 409);
            }

            /*
             * Payment berhasil.
             */
            if (
                $paymentIntent->status === 'succeeded'
            ) {

                DB::transaction(function () use (
                    $payment,
                    $order,
                    $paymentIntent
                ) {

                    $payment->lockForUpdate();
                    $order->lockForUpdate();

                    /*
                     * Idempotent.
                     */
                    if (
                        $payment->status === 'succeeded'
                    ) {
                        return;
                    }

                    $payment->update([
                        'provider' => 'stripe',

                        'stripe_payment_intent_id' =>
                            $paymentIntent->id,

                        'transaction_id' =>
                            $paymentIntent->id,

                        'currency' =>
                            strtoupper(
                                $paymentIntent->currency
                            ),

                        'status' =>
                            'succeeded',

                        'transaction_status' =>
                            $paymentIntent->status,

                        'gross_amount' =>
                            $order->total,

                        'paid_at' =>
                            $payment->paid_at ?? now(),

                        'metadata' => [
                            'stripe_payment_intent_status' =>
                                $paymentIntent->status,
                        ],
                    ]);

                    $order->update([
                        'status' => 'processing',
                    ]);
                });

                return response()->json([
                    'success' => true,
                    'status' => 'succeeded',
                    'redirect_url' =>
                        route(
                            'checkout.success',
                            $order
                        ),
                ]);
            }

            /*
             * Payment gagal.
             */
            if (
                $paymentIntent->status === 'canceled'
            ) {

                $this->cancelOrder(
                    $order,
                    $payment,
                    'canceled',
                    $paymentIntent
                );

                return response()->json([
                    'success' => false,
                    'status' => 'canceled',
                    'message' =>
                        'Pembayaran dibatalkan.',
                ]);
            }

            return response()->json([
                'success' => false,
                'status' =>
                    $paymentIntent->status,
                'message' =>
                    'Pembayaran belum berhasil diselesaikan.',
            ], 422);

        } catch (ApiErrorException $e) {

            Log::error(
                'Stripe confirmation error.',
                [
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'message' =>
                    'Pembayaran tidak dapat diverifikasi.',
            ], 502);
        }
    }

    private function cancelOrder(
        Order $order,
        $payment,
        string $status,
        PaymentIntent $paymentIntent
    ): void {
        DB::transaction(function () use (
            $order,
            $payment,
            $status,
            $paymentIntent
        ) {

            $payment->lockForUpdate();
            $order->lockForUpdate();

            /*
             * Jangan restore stock dua kali.
             */
            if (
                in_array(
                    $payment->status,
                    [
                        'failed',
                        'canceled',
                    ],
                    true
                )
            ) {
                return;
            }

            $payment->update([
                'status' =>
                    $status,

                'transaction_status' =>
                    $paymentIntent->status,

                'metadata' => [
                    'stripe_payment_intent_status' =>
                        $paymentIntent->status,
                ],
            ]);

            $order->update([
                'status' => 'canceled',
            ]);

            $order->load('items');

            foreach ($order->items as $item) {

                $product =
                    \App\Models\Product::whereKey(
                        $item->product_id
                    )
                    ->lockForUpdate()
                    ->first();

                if ($product) {
                    $product->increment(
                        'stock',
                        $item->quantity
                    );
                }
            }
        });
    }

    private function stripeAmount($amount): int
    {
        /*
         * IDR adalah zero-decimal currency
         * di Stripe.
         *
         * Rp449.000
         * menjadi:
         *
         * 449000
         */
        return (int) round(
            (float) $amount
        );
    }
}