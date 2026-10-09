<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Throwable;

class PaymentController extends Controller
{
    /**
     * Menampilkan halaman pembayaran.
     */
    public function show(Order $order)
    {
        abort_unless(
            $order->user_id === auth()->id(),
            403
        );

        $order->load('payment');

        if (! $order->payment) {
            abort(404, 'Payment data not found.');
        }

        $payment = $order->payment;

        /*
         * Jika payment sudah berhasil,
         * langsung ke halaman success.
         */
        if ($payment->status === 'succeeded') {
            return redirect()->route(
                'checkout.success',
                $order
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
                    'Order has been created successfully. Payment is made upon receipt of the order.'
                );
        }

        /*
         * Jika sebelumnya sudah mempunyai PaymentIntent,
         * sinkronkan terlebih dahulu.
         *
         * Ini penting apabila:
         *
         * Stripe = succeeded
         * Database = pending
         */
        if ($payment->stripe_payment_intent_id) {
            $this->syncPaymentFromStripe(
                $order,
                $payment->stripe_payment_intent_id
            );

            $payment->refresh();

            if ($payment->status === 'succeeded') {
                return redirect()->route(
                    'checkout.success',
                    $order
                );
            }

            if ($payment->status === 'canceled') {
                return redirect()
                    ->route('orders.show', $order)
                    ->with(
                        'error',
                        'This order payment has been cancelled'
                    );
            }
        }

        Stripe::setApiKey(
            config('services.stripe.secret')
        );

        $expectedAmount = $this->stripeAmount(
            $order->total
        );

        try {
            /*
             * Gunakan PaymentIntent lama jika tersedia.
             */
            if ($payment->stripe_payment_intent_id) {
                $paymentIntent = PaymentIntent::retrieve(
                    $payment->stripe_payment_intent_id
                );

                $this->validatePaymentIntent(
                    $order,
                    $paymentIntent
                );
            } else {
                /*
                 * Buat PaymentIntent baru.
                 */
                $paymentIntent = PaymentIntent::create([
                    'amount' => $expectedAmount,
                    'currency' => 'idr',
                    'payment_method_types' => ['card'],
                    'description' => config('app.name') . " order {$order->order_number}",
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
                'Stripe payments could not be set up'
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
                'Stripe payments could not be set up.'
            );
        }

        return view(
            'storefront.payment',
            [
                'order' => $order,
                'payment' => $payment->fresh(),
                'clientSecret' => $paymentIntent->client_secret,
                'publishableKey' => config('services.stripe.key'),
            ]
        );
    }

    /**
     * Konfirmasi pembayaran dari browser.
     *
     * Browser hanya mengirim PaymentIntent ID.
     * Semua data pembayaran diverifikasi langsung
     * melalui Stripe API menggunakan secret key.
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

        if (! $order->payment) {
            return response()->json([
                'message' => ' Payment data could not be found ',
            ], 404);
        }

        $payment = $order->payment;

        /*
         * Idempotent:
         * jika sudah berhasil, jangan diproses ulang.
         */
        if ($payment->status === 'succeeded') {
            return response()->json([
                'success' => true,
                'status' => 'succeeded',
                'redirect_url' => route(
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
             * Ambil PaymentIntent langsung dari Stripe.
             */
            $paymentIntent =
                PaymentIntent::retrieve(
                    $validated['payment_intent_id']
                );

            /*
             * PaymentIntent harus sesuai dengan
             * Payment record milik order.
             */
            if (
                $payment->stripe_payment_intent_id
                !== $paymentIntent->id
            ) {
                return response()->json([
                    'message' => 'PaymentIntent does not match the order',
                ], 409);
            }

            /*
             * Validasi metadata, amount dan currency.
             */
            $this->validatePaymentIntent(
                $order,
                $paymentIntent
            );

            /*
             * Sinkronisasi status.
             */
            $this->syncPaymentIntent(
                $order,
                $payment,
                $paymentIntent
            );

            $payment->refresh();
            $order->refresh();

            if ($payment->status === 'succeeded') {
                return response()->json([
                    'success' => true,
                    'status' => 'succeeded',
                    'redirect_url' => route(
                        'checkout.success',
                        $order
                    ),
                ]);
            }

            if ($payment->status === 'canceled') {
                return response()->json([
                    'success' => false,
                    'status' => 'canceled',
                    'message' => 'Payment canceled .',
                ]);
            }

            return response()->json([
                'success' => false,
                'status' => $payment->status,
                'payment_intent_status' => $paymentIntent->status,
                'message' => 'Payment has not been completed successfully ',
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
                'message' => 'Payment could not be verified '
            ], 502);
        } catch (Throwable $e) {
            Log::error(
                'Stripe confirmation unexpected error.',
                [
                    'order_id' => $order->id,
                    'message' => $e->getMessage(),
                ]
            );

            return response()->json([
                'message' => 'Payment could not be verified ',
            ], 500);
        }
    }

    /**
     * Sinkronisasi Payment lokal berdasarkan PaymentIntent Stripe.
     *
     * Method ini digunakan ketika:
     *
     * Stripe sudah succeeded
     * tetapi database masih pending.
     */
    public function syncPaymentFromStripe(
        Order $order,
        ?string $paymentIntentId = null
    ): bool {
        $order->load('payment');

        $payment = $order->payment;

        if (! $payment) {
            return false;
        }

        /*
         * COD tidak perlu Stripe.
         */
        if ($payment->payment_method === 'cod') {
            return false;
        }

        $paymentIntentId ??=
            $payment->stripe_payment_intent_id;

        if (! $paymentIntentId) {
            return false;
        }

        /*
         * Jangan melakukan downgrade status
         * yang sudah berhasil.
         */
        if ($payment->status === 'succeeded') {
            return true;
        }

        Stripe::setApiKey(
            config('services.stripe.secret')
        );

        try {
            $paymentIntent =
                PaymentIntent::retrieve(
                    $paymentIntentId
                );

            /*
             * Verifikasi seluruh data sebelum
             * mengubah database.
             */
            if (
                $payment->stripe_payment_intent_id
                !== $paymentIntent->id
            ) {
                Log::critical(
                    'Stripe PaymentIntent ID mismatch.',
                    [
                        'order_id' => $order->id,
                        'database_payment_intent_id' => $payment->stripe_payment_intent_id,
                        'stripe_payment_intent_id' => $paymentIntent->id,
                    ]
                );

                return false;
            }

            $this->validatePaymentIntent(
                $order,
                $paymentIntent
            );

            $this->syncPaymentIntent(
                $order,
                $payment,
                $paymentIntent
            );

            return $payment->fresh()->status === 'succeeded';

        } catch (Throwable $e) {
            Log::error(
                'Stripe payment synchronization failed.',
                [
                    'order_id' => $order->id,
                    'payment_intent_id' => $paymentIntentId,
                    'message' => $e->getMessage(),
                ]
            );

            return false;
        }
    }

    /**
     * Memvalidasi PaymentIntent yang berasal
     * dari Stripe.
     */
    private function validatePaymentIntent(
        Order $order,
        PaymentIntent $paymentIntent
    ): void {
        /*
         * PaymentIntent harus mempunyai metadata
         * order_id yang sesuai.
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
                    'payment_intent_id' => $paymentIntent->id,
                ]
            );

            abort(
                409, 'PaymentIntent does not match the order'
            );
        }

        /*
         * Validasi nominal dan currency.
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
                    'payment_intent_id' => $paymentIntent->id,
                    'expected_amount' => $expectedAmount,
                    'stripe_amount' => $paymentIntent->amount,
                    'stripe_currency' => $paymentIntent->currency,
                ]
            );

            abort(
                409,'The payment amount does not match the order.'
            );
        }
    }

    /**
     * Menyimpan status PaymentIntent Stripe
     * ke database lokal.
     */
    private function syncPaymentIntent(
        Order $order,
        $payment,
        PaymentIntent $paymentIntent
    ): void {
        DB::transaction(function () use (
            $order,
            $payment,
            $paymentIntent
        ) {
            // Kunci baris database yang sebenarnya.
            $lockedPayment = Payment::query()
                ->whereKey($payment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedOrder = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Pastikan pembayaran memang milik pesanan ini.
            if (
                (int) $lockedPayment->order_id
                !== (int) $lockedOrder->id
            ) {
                throw new \RuntimeException(
                    'Payment tidak sesuai dengan pesanan.'
                );
            }

            // Jangan menurunkan status pembayaran yang sudah berhasil.
            if ($lockedPayment->status === 'succeeded') {
                return;
            }

            // SUCCESS
            if ($paymentIntent->status === 'succeeded') {
                $lockedPayment->update([
                    'provider' => 'stripe',
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'transaction_id' => $paymentIntent->id,
                    'currency' => strtoupper(
                        $paymentIntent->currency
                    ),
                    'status' => 'succeeded',
                    'transaction_status' => $paymentIntent->status,
                    'gross_amount' => $lockedOrder->total,
                    'paid_at' => $lockedPayment->paid_at ?? now(),
                    'metadata' => array_merge(
                        $lockedPayment->metadata ?? [],
                        [
                            'stripe_payment_intent_status'
                                => $paymentIntent->status,
                        ]
                    ),
                ]);

                // Hanya pesanan pending yang berubah menjadi processing.
                if ($lockedOrder->status === 'pending') {
                    $lockedOrder->update([
                        'status' => 'processing',
                    ]);
                }

                return;
            }

            // CANCELED
            if ($paymentIntent->status === 'canceled') {
                $this->cancelOrder(
                    $lockedOrder,
                    $lockedPayment,
                    'canceled',
                    $paymentIntent
                );

                return;
            }

            // Status PaymentIntent lainnya tidak menurunkan status payment.
            $lockedPayment->update([
                'transaction_status' => $paymentIntent->status,
                'metadata' => array_merge(
                    $lockedPayment->metadata ?? [],
                    [
                        'stripe_payment_intent_status'
                            => $paymentIntent->status,
                    ]
                ),
            ]);
        });
    }

    private function cancelOrder(
        Order $order,
        $payment,
        string $status,
        PaymentIntent $paymentIntent
    ): void {
        /*
         * Method ini dipanggil ketika sudah berada
         * di dalam transaksi syncPaymentIntent().
         *
         * Karena lock sudah diperoleh di caller,
         * kita tidak membuka transaksi kedua.
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
            'status' => $status,

            'transaction_status' => $paymentIntent->status,

            'metadata' => [
                'stripe_payment_intent_status' => $paymentIntent->status,
            ],
        ]);

        $order->update([
            'status' => 'canceled',
        ]);

        $order->load('items');

        foreach ($order->items as $item) {
            $product =
                Product::whereKey(
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
    }

    private function stripeAmount($amount): int
    {
        /*
         * Stripe memperlakukan IDR dengan
         * dua digit minor unit untuk request amount.
         */
        return (int) round((float) $amount * 100);
    }
}
