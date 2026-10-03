<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\WebhookEventService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
    public function __construct(
        protected WebhookEventService $webhookEvents
    ) {}

    public function handle(Request $request): Response
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (UnexpectedValueException $e) {
            return response('Invalid payload', 400);
        } catch (SignatureVerificationException $e) {
            return response('Invalid signature', 400);
        }

        try {
            $this->webhookEvents->process(
                provider: 'stripe',
                eventId: $event->id,
                eventType: $event->type,
                payload: json_decode(
                    $payload,
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                ),
                handler: function () use ($event): void {
                    switch ($event->type) {
                        case 'payment_intent.succeeded':
                            $this->handlePaymentIntentSucceeded(
                                $event->data->object
                            );
                            break;

                        case 'payment_intent.payment_failed':
                            $this->handlePaymentIntentFailed(
                                $event->data->object
                            );
                            break;

                        case 'payment_intent.canceled':
                            $this->handlePaymentIntentCanceled(
                                $event->data->object
                            );
                            break;
                    }
                }
            );
        } catch (\RuntimeException $e) {
            return response($e->getMessage(), 422);
        } catch (\JsonException $e) {
            return response('Invalid webhook payload.', 400);
        }

        return response('Webhook received', 200);
    }

    private function handlePaymentIntentSucceeded(
        object $paymentIntent
    ): void {
        if (! isset($paymentIntent->status)) {
            throw new \RuntimeException(
                'Stripe payment intent status is missing.'
            );
        }

        if (! is_string($paymentIntent->status)) {
            throw new \RuntimeException(
                'Stripe payment intent status must be a string.'
            );
        }

        if ($paymentIntent->status !== 'succeeded') {
            throw new \RuntimeException(
                'Stripe payment intent has an invalid status for payment_intent.succeeded event.'
            );
        }

        if (! isset($paymentIntent->id)) {
            throw new \RuntimeException(
                'Stripe payment intent ID is missing.'
            );
        }

        if (! is_string($paymentIntent->id)) {
            throw new \RuntimeException(
                'Stripe payment intent ID must be a string.'
            );
        }

        if (! isset($paymentIntent->amount)) {
            throw new \RuntimeException(
                'Stripe payment amount is missing.'
            );
        }

        if (! is_int($paymentIntent->amount)) {
            throw new \RuntimeException(
                'Stripe payment amount must be an integer.'
            );
        }

        if ($paymentIntent->amount <= 0) {
            throw new \RuntimeException(
                'Stripe payment amount must be greater than zero.'
            );
        }

        if (! isset($paymentIntent->currency)) {
            throw new \RuntimeException(
                'Stripe payment currency is missing.'
            );
        }

        if (! is_string($paymentIntent->currency)) {
            throw new \RuntimeException(
                'Stripe payment currency must be a string.'
            );
        }

        if (trim($paymentIntent->currency) === '') {
            throw new \RuntimeException(
                'Stripe payment currency cannot be empty.'
            );
        }

        DB::transaction(function () use ($paymentIntent): void {
            $payment = Payment::query()
                ->where('provider', 'stripe')
                ->where(
                    'stripe_payment_intent_id',
                    $paymentIntent->id
                )
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return;
            }

            $order = $payment->order;

            if (! $order) {
                return;
            }

            if (in_array(
                $order->status,
                ['canceled', 'failed', 'refunded'],
                true
            )) {
                return;
            }

            $stripeCurrency = strtolower(
                $paymentIntent->currency
            );

            $paymentCurrency = strtolower(
                $payment->currency
            );

            if ($paymentCurrency !== $stripeCurrency) {
                throw new \RuntimeException(
                    'Stripe payment currency does not match payment currency.'
                );
            }

            $stripeAmount = $this->convertStripeAmount(
                $paymentIntent->amount
            );

            if ((float) $payment->gross_amount !== $stripeAmount) {
                throw new \RuntimeException(
                    'Stripe payment amount does not match payment gross amount.'
                );
            }

            /*
             * Jangan pernah mengubah payment yang sudah
             * succeeded atau canceled menjadi succeeded kembali.
             */
            if (in_array(
                $payment->status,
                ['succeeded', 'canceled'],
                true
            )) {
                return;
            }

            $payment->update([
                'status' => 'succeeded',
                'transaction_status' => 'succeeded',
                'transaction_id' => $paymentIntent->id,
                'reference_id' => $paymentIntent->id,
                'paid_at' => $payment->paid_at ?? now(),
                'metadata' => array_merge(
                    $payment->metadata ?? [],
                    [
                        'stripe_webhook_event' => 'payment_intent.succeeded',
                        'stripe_payment_intent_status' => $paymentIntent->status,
                    ],
                ),
            ]);

            $order->update([
                'status' => 'completed',
            ]);
        });
    }

    private function handlePaymentIntentFailed(
        object $paymentIntent
    ): void {
        if (! isset($paymentIntent->id)) {
            throw new \RuntimeException(
                'Stripe payment intent ID is missing.'
            );
        }

        if (! is_string($paymentIntent->id)) {
            throw new \RuntimeException(
                'Stripe payment intent ID must be a string.'
            );
        }

        if (! isset($paymentIntent->status)) {
            throw new \RuntimeException(
                'Stripe payment intent status is missing.'
            );
        }

        if (! is_string($paymentIntent->status)) {
            throw new \RuntimeException(
                'Stripe payment intent status must be a string.'
            );
        }

        if ($paymentIntent->status !== 'requires_payment_method') {
            throw new \RuntimeException(
                'Stripe payment intent has an invalid status for payment_failed event.'
            );
        }

        DB::transaction(function () use ($paymentIntent): void {
            $payment = Payment::query()
                ->where('provider', 'stripe')
                ->where(
                    'stripe_payment_intent_id',
                    $paymentIntent->id
                )
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return;
            }

            /*
             * Jangan pernah menurunkan payment yang sudah
             * succeeded atau canceled.
             */
            if (in_array(
                $payment->status,
                ['succeeded', 'canceled'],
                true
            )) {
                return;
            }

            $order = $payment->order;

            if (! $order) {
                return;
            }

            if (in_array(
                $order->status,
                ['completed', 'canceled', 'failed', 'refunded'],
                true
            )) {
                return;
            }

            $payment->update([
                'status' => 'failed',
                'transaction_status' => 'failed',
                'transaction_id' => $paymentIntent->id,
                'reference_id' => $paymentIntent->id,
                'metadata' => array_merge(
                    $payment->metadata ?? [],
                    [
                        'stripe_webhook_event' => 'payment_intent.payment_failed',
                        'stripe_payment_intent_status' => $paymentIntent->status,
                        'stripe_last_payment_error' => isset($paymentIntent->last_payment_error)
                                ? (array) $paymentIntent->last_payment_error
                                : null,
                    ],
                ),
            ]);
        });
    }

    private function handlePaymentIntentCanceled(
        object $paymentIntent
    ): void {
        if (! isset($paymentIntent->id)) {
            throw new \RuntimeException(
                'Stripe payment intent ID is missing.'
            );
        }

        if (! is_string($paymentIntent->id)) {
            throw new \RuntimeException(
                'Stripe payment intent ID must be a string.'
            );
        }

        if (! isset($paymentIntent->status)) {
            throw new \RuntimeException(
                'Stripe payment intent status is missing.'
            );
        }

        if (! is_string($paymentIntent->status)) {
            throw new \RuntimeException(
                'Stripe payment intent status must be a string.'
            );
        }

        if ($paymentIntent->status !== 'canceled') {
            throw new \RuntimeException(
                'Stripe payment intent has an invalid status for payment_intent.canceled event.'
            );
        }

        DB::transaction(function () use ($paymentIntent): void {
            $payment = Payment::query()
                ->where('provider', 'stripe')
                ->where(
                    'stripe_payment_intent_id',
                    $paymentIntent->id
                )
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                return;
            }

            /*
             * Jangan pernah menurunkan payment yang sudah
             * succeeded, failed, atau canceled.
             */
            if (in_array(
                $payment->status,
                ['succeeded', 'failed', 'canceled'],
                true
            )) {
                return;
            }

            $order = $payment->order;

            if (! $order) {
                return;
            }

            if (in_array(
                $order->status,
                ['completed', 'canceled', 'failed', 'refunded'],
                true
            )) {
                return;
            }

            $payment->update([
                'status' => 'canceled',
                'transaction_status' => 'canceled',
                'transaction_id' => $paymentIntent->id,
                'reference_id' => $paymentIntent->id,
                'metadata' => array_merge(
                    $payment->metadata ?? [],
                    [
                        'stripe_webhook_event' => 'payment_intent.canceled',
                        'stripe_payment_intent_status' => $paymentIntent->status,
                    ],
                ),
            ]);
        });
    }

    private function convertStripeAmount(int $amount): float
    {
        return $amount / 100;
    }
}
