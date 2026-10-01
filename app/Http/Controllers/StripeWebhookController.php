<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();

        $signature =
            $request->header('Stripe-Signature');

        $webhookSecret =
            config('services.stripe.webhook_secret');

        if (!$signature || !$webhookSecret) {

            return response()->json([
                'message' =>
                    'Webhook configuration incomplete.',
            ], 400);
        }

        try {

            $event = Webhook::constructEvent(
                $payload,
                $signature,
                $webhookSecret
            );

        } catch (\UnexpectedValueException $e) {

            Log::warning(
                'Invalid Stripe webhook payload.',
                [
                    'message' =>
                        $e->getMessage(),
                ]
            );

            return response()->json([
                'message' =>
                    'Invalid payload.',
            ], 400);

        } catch (
            SignatureVerificationException $e
        ) {

            Log::warning(
                'Invalid Stripe webhook signature.',
                [
                    'message' =>
                        $e->getMessage(),
                ]
            );

            return response()->json([
                'message' =>
                    'Invalid signature.',
            ], 400);
        }

        switch ($event->type) {

            case 'payment_intent.succeeded':

                $this->handleSucceeded(
                    $event
                );

                break;

            case 'payment_intent.processing':

                $this->handleProcessing(
                    $event
                );

                break;

            case 'payment_intent.payment_failed':

                $this->handleFailed(
                    $event
                );

                break;

            case 'payment_intent.canceled':

                $this->handleCanceled(
                    $event
                );

                break;

            default:

                return response()->json([
                    'received' => true,
                ]);
        }

        return response()->json([
            'received' => true,
        ]);
    }

    private function handleSucceeded($event): void
    {
        $intent = $event->data->object;

        DB::transaction(function () use (
            $intent,
            $event
        ) {

            $payment = Payment::where(
                'stripe_payment_intent_id',
                $intent->id
            )
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return;
            }

            $order = Order::whereKey(
                $payment->order_id
            )
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return;
            }

            /*
             * Security validation.
             *
             * Stripe amount harus sama dengan
             * total order di database.
             */
            if (!$this->amountMatches(
                $intent,
                $order
            )) {

                Log::critical(
                    'Stripe amount mismatch on success.',
                    [
                        'order_id' =>
                            $order->id,

                        'payment_intent_id' =>
                            $intent->id,

                        'stripe_amount' =>
                            $intent->amount,

                        'expected_amount' =>
                            $this->stripeAmount(
                                $order->total
                            ),
                    ]
                );

                return;
            }

            /*
             * Idempotency.
             */
            if (
                $payment->status === 'succeeded'
            ) {
                return;
            }

            $payment->update([
                'provider' => 'stripe',

                'stripe_payment_intent_id' =>
                    $intent->id,

                'transaction_id' =>
                    $intent->id,

                'currency' =>
                    strtoupper(
                        $intent->currency
                    ),

                'status' =>
                    'succeeded',

                'transaction_status' =>
                    $intent->status,

                'gross_amount' =>
                    $order->total,

                'paid_at' =>
                    $payment->paid_at ?? now(),

                'metadata' => [
                    'stripe_event_id' =>
                        $event->id,
                ],
            ]);

            $order->update([
                'status' => 'processing',
            ]);
        });
    }

    private function handleProcessing($event): void
    {
        $intent = $event->data->object;

        DB::transaction(function () use ($intent) {

            $payment = Payment::where(
                'stripe_payment_intent_id',
                $intent->id
            )
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return;
            }

            $order = $payment->order;

            if (!$order) {
                return;
            }

            if (!$this->amountMatches(
                $intent,
                $order
            )) {
                return;
            }

            if (
                $payment->status === 'succeeded'
            ) {
                return;
            }

            $payment->update([
                'status' => 'processing',

                'transaction_status' =>
                    $intent->status,

                'metadata' => [
                    'stripe_event_id' =>
                        $event->id,
                ],
            ]);

            $order->update([
                'status' => 'pending',
            ]);
        });
    }

    private function handleFailed($event): void
    {
        $intent = $event->data->object;

        $this->cancelOrderPayment(
            $intent,
            $event->id,
            'failed'
        );
    }

    private function handleCanceled($event): void
    {
        $intent = $event->data->object;

        $this->cancelOrderPayment(
            $intent,
            $event->id,
            'canceled'
        );
    }

    private function cancelOrderPayment(
        $intent,
        string $eventId,
        string $paymentStatus
    ): void {

        DB::transaction(function () use (
            $intent,
            $eventId,
            $paymentStatus
        ) {

            $payment = Payment::where(
                'stripe_payment_intent_id',
                $intent->id
            )
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return;
            }

            $order = Order::whereKey(
                $payment->order_id
            )
                ->lockForUpdate()
                ->first();

            if (!$order) {
                return;
            }

            if (!$this->amountMatches(
                $intent,
                $order
            )) {
                return;
            }

            /*
             * Jangan mengembalikan stok dua kali
             * jika Stripe mengirim webhook yang sama
             * lebih dari sekali.
             */
            $alreadyFailed =
                in_array(
                    $payment->status,
                    [
                        'failed',
                        'canceled',
                    ],
                    true
                );

            $payment->update([
                'status' =>
                    $paymentStatus,

                'transaction_status' =>
                    $intent->status,

                'metadata' => [
                    'stripe_event_id' =>
                        $eventId,

                    'last_payment_error' =>
                        $intent
                            ->last_payment_error
                            ?->message,
                ],
            ]);

            $order->update([
                'status' => 'cancelled',
            ]);

            if ($alreadyFailed) {
                return;
            }

            /*
             * Kembalikan stok karena order
             * dibatalkan sebelum pembayaran sukses.
             */
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

    private function amountMatches(
        $intent,
        Order $order
    ): bool {

        return
            (int) $intent->amount ===
                $this->stripeAmount(
                    $order->total
                )
            &&
            strtolower(
                (string) $intent->currency
            ) === 'idr';
    }

    private function stripeAmount(
        $amount
    ): int {

        return (int) round(
            ((float) $amount) * 100
        );
    }
}
