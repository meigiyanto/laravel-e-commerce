<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Throwable;

class PaymentController extends Controller
{
    public function show(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);

        $order->load('payment');

        if (!$order->payment) {
            abort(404, 'Data pembayaran tidak ditemukan.');
        }

        if ($order->payment->status === 'succeeded') {
            return redirect()->route('checkout.success', $order);
        }

        if ($order->payment->status === 'failed') {
            return redirect()->route('orders.show', $order)->with('error', 'Pembayaran sebelumnya gagal. Silakan buat pesanan baru.');
        }

        if ($order->payment->status === 'canceled') {
            return redirect()->route('orders.show', $order)->with('error', 'Pembayaran pesanan ini telah dibatalkan.');
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        Log::debug('Stripe configuration check.', [
            'secret_configured' => !empty(config('services.stripe.secret')),
            'stripe_api_key_configured' => !empty(Stripe::getApiKey()),
        ]);

        $payment = $order->payment;

        /*
         * COD tidak membutuhkan Stripe PaymentIntent.
         */
        if ($payment->payment_method === 'cod') {
            return redirect()->route('orders.show', $order)->with('success', 'Pesanan COD berhasil dibuat. Pembayaran dilakukan saat pesanan diterima.');
        }

        $expectedAmount = $this->stripeAmount($order->total);

        try {
            /*
             * Jika PaymentIntent sudah pernah dibuat,
             * gunakan kembali.
             */
            if ($payment->stripe_payment_intent_id) {
                $paymentIntent = PaymentIntent::retrieve($payment->stripe_payment_intent_id);
                /*
                 * Security check:
                 * PaymentIntent Stripe harus sama persis
                 * dengan order server.
                 */
                if ((int) $paymentIntent->amount !== $expectedAmount || strtolower($paymentIntent->currency) !== 'idr') {
                    Log::critical('Stripe amount mismatch.',
                        [
                            'order_id' => $order->id,
                            'payment_intent_id' => $paymentIntent->id,
                            'expected_amount' => $expectedAmount,
                            'stripe_amount' => $paymentIntent->amount,
                            'stripe_currency' => $paymentIntent->currency,
                        ]
                    );
                    abort(409, 'Nominal pembayaran tidak sesuai dengan pesanan.');
                }
            } else {
                /*
                 * PaymentIntent hanya dibuat berdasarkan
                 * nilai server-side.
                 */
                $paymentIntent = PaymentIntent::create([
                        'amount' => $expectedAmount,
                        'currency' => 'idr',
                        'automatic_payment_methods' => [
                            'enabled' => true,
                        ],
                        'description' => "MeiStore order {$order->order_number}",
                        'metadata' => [
                            'order_id' => (string) $order->id,
                            'order_number' => $order->order_number,
                        ],
                        'receipt_email' => $order->user?->email,
                    ]);

                $payment->update([
                    'provider' => 'stripe',
                    'currency' => 'IDR',
                    'status' => 'pending',
                    'transaction_status' => $paymentIntent->status,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'transaction_id' => $paymentIntent->id,
                    'gross_amount' => $order->total,
                    'metadata' => [
                        'stripe_payment_intent_status' => $paymentIntent->status,
                    ],
                ]);
            }
        } catch (Throwable $e) {
            Log::error('Stripe PaymentIntent error.', [
                    'order_id' => $order->id,
                    'exception' => $e->getMessage(),
                ]
            );
            abort(502, 'Pembayaran Stripe tidak dapat disiapkan.');
        }

        return view('storefront.payment', [
                'order' => $order,
                'payment' => $payment->fresh(),
                'clientSecret' => $paymentIntent->client_secret,
                'publishableKey' => config('services.stripe.key'),
            ]
        );
    }

    private function stripeAmount($amount): int
    {
        /*
         * IDR pada Stripe menggunakan minor unit.
         * Contoh:
         *
         * Rp100.000
         * => 10000000
         */
        return (int) round(((float) $amount) * 100);
    }
}
