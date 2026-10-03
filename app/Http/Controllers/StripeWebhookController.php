<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;
use UnexpectedValueException;

class StripeWebhookController extends Controller
{
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

        return response('Webhook received', 200);
    }

    private function handlePaymentIntentSucceeded(object $paymentIntent): void
    {
        DB::transaction(function () use ($paymentIntent) {
            $payment = Payment::query()
                ->where('stripe_payment_intent_id', $paymentIntent->id)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return;
            }

            $order = $payment->order;

            if (!$order) {
                return;
            }

            /*
             * PaymentIntent dari Stripe menjadi sumber kebenaran
             * untuk status pembayaran.
             */
            $payment->update([
                'status' => 'succeeded',
                'transaction_status' => 'succeeded',
                'transaction_id' => $paymentIntent->id,
                'reference_id' => $paymentIntent->id,
                'gross_amount' => $this->convertStripeAmount(
                    $paymentIntent->amount
                ),
                'paid_at' => $payment->paid_at ?? now(),
                'metadata' => array_merge(
                    $payment->metadata ?? [],
                    [
                        'stripe_webhook_event' => 'payment_intent.succeeded',
                        'stripe_payment_intent_status' => $paymentIntent->status,
                    ]
                ),
            ]);

            /*
             * Pertahankan rule yang sudah dipakai
             * pada flow payment saat ini.
             */
            $order->update([
                'status' => 'completed',
            ]);
        });
    }

    private function handlePaymentIntentFailed(object $paymentIntent): void
    {
        DB::transaction(function () use ($paymentIntent) {
            $payment = Payment::query()
                ->where('stripe_payment_intent_id', $paymentIntent->id)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return;
            }

            /*
             * Jangan pernah menurunkan payment yang sudah sukses.
             */
            if ($payment->status === 'succeeded') {
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
                        'stripe_last_payment_error' => $paymentIntent->last_payment_error
                            ? (array) $paymentIntent->last_payment_error
                            : null,
                    ]
                ),
            ]);
        });
    }

    private function handlePaymentIntentCanceled(object $paymentIntent): void
    {
        DB::transaction(function () use ($paymentIntent) {
            $payment = Payment::query()
                ->where('stripe_payment_intent_id', $paymentIntent->id)
                ->lockForUpdate()
                ->first();

            if (!$payment) {
                return;
            }

            /*
             * Jangan menurunkan status pembayaran yang sudah sukses.
             */
            if ($payment->status === 'succeeded') {
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
                    ]
                ),
            ]);
        });
    }

    private function convertStripeAmount(int $amount): float
    {
        return $amount / 100;
    }
}
