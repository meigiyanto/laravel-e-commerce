<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\RefundService;
use App\Services\WebhookEventService;
use App\Services\VariantInventoryService;
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
        if (
            ! isset(
                $paymentIntent->id,
                $paymentIntent->status,
                $paymentIntent->amount,
                $paymentIntent->currency
            )
            || ! is_string($paymentIntent->id)
            || ! is_string($paymentIntent->status)
            || $paymentIntent->status !== 'succeeded'
            || ! is_int($paymentIntent->amount)
            || $paymentIntent->amount <= 0
            || ! is_string($paymentIntent->currency)
            || trim($paymentIntent->currency) === ''
        ) {
            throw new \RuntimeException(
                'Stripe payment intent is invalid for success event.'
            );
        }
    
        DB::transaction(function () use ($paymentIntent): void {
            $context = $this->lockPaymentContext(
                $paymentIntent->id
            );
    
            if (! $context) {
                return;
            }
    
            $order = $context['order'];
            $payment = $context['payment'];
            
            // Order failed dan refunded tidak boleh menerima pembayaran baru.
            if (in_array($order->status, ['failed', 'refunded'], true)) {
                return;
            }
    
            $stripeCurrency = strtolower($paymentIntent->currency);
            $paymentCurrency = strtolower($payment->currency);
    
            if ($stripeCurrency !== $paymentCurrency) {
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
    
            // Pembayaran yang sudah canceled tidak boleh diaktifkan kembali.
            // PaymentIntent yang sama tidak boleh membalikkan transisi terminal.
            if ($payment->status === 'canceled') {
                return;
            }
            
            // Pembayaran succeeded tetap diproses secara idempoten.
            if ($payment->status !== 'succeeded') {    
                $payment->update([
                    'provider' => 'stripe',
                    'status' => 'succeeded',
                    'transaction_status' => 'succeeded',
                    'transaction_id' => $paymentIntent->id,
                    'reference_id' => $paymentIntent->id,
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'currency' => strtoupper($paymentIntent->currency),
                    'gross_amount' => $order->total,
                    'paid_at' => $payment->paid_at ?? now(),
                    'metadata' => array_merge(
                        $payment->metadata ?? [],
                        [
                            'stripe_webhook_event' => 'payment_intent.succeeded',
                            'stripe_payment_intent_status' => $paymentIntent->status,
                        ]
                    ),
                ]);
            }
    
            // Jangan aktifkan kembali order yang sudah dibatalkan.
            if ($order->status === 'pending') {
                app(VariantInventoryService::class)->commitReservationsForOrder($order);
    
                $order->update([
                    'status' => 'processing',
                ]);
            }
        });
    
        // Refund dilakukan setelah transaksi transisi selesai.
        $payment = Payment::query()
            ->where('provider', 'stripe')
            ->where('stripe_payment_intent_id', $paymentIntent->id)
            ->first();
    
        if (! $payment) {
            return;
        }
    
        $order = Order::query()->find($payment->order_id);
    
        if ($order&& $order->status === 'canceled'&& $payment->status === 'succeeded') {
            app(RefundService::class)->requestLateCanceledOrderRefund($order, $payment);
        }
    }

    private function handlePaymentIntentFailed(
        object $paymentIntent
    ): void {
        if ( ! isset($paymentIntent->id, $paymentIntent->status) || ! is_string($paymentIntent->id) || ! is_string($paymentIntent->status) || $paymentIntent->status !== 'requires_payment_method') {
            throw new \RuntimeException(
                'Stripe payment intent is invalid for payment_failed event.'
            );
        }
    
        DB::transaction(function () use ($paymentIntent): void {
            $context = $this->lockPaymentContext(
                $paymentIntent->id
            );
    
            if (! $context) {
                return;
            }
    
            $order = $context['order'];
            $payment = $context['payment'];
    
            // Jangan menurunkan pembayaran yang sudah sukses atau canceled.
            if (in_array($payment->status, ['succeeded', 'canceled'], true)) {
                return;
            }
    
            // Hanya pembayaran pada order pending yang bisa dicoba ulang.
            if ($order->status !== 'pending') {
                return;
            }
    
            $payment->update([
                'status' => 'failed',
                'transaction_status' => $paymentIntent->status,
                'transaction_id' => $paymentIntent->id,
                'reference_id' => $paymentIntent->id,
                'metadata' => array_merge(
                    $payment->metadata ?? [],
                    [
                        'stripe_webhook_event' => 'payment_intent.payment_failed',
                        'stripe_payment_intent_status' => $paymentIntent->status,
                        'stripe_last_payment_error' => isset(
                            $paymentIntent->last_payment_error
                        )
                            ? (array) $paymentIntent->last_payment_error
                            : null,
                    ]
                ),
            ]);
    
            // Jangan commit atau release reservasi pada kegagalan sementara.
        });
    }
    
    /**
     * Mengunci Order terlebih dahulu, baru Payment.
     *
     * Dipanggil hanya dari dalam DB transaction.
     */
    private function lockPaymentContext(string $paymentIntentId): ?array
    {
        // Ambil referensi awal untuk mengetahui order_id.
        $paymentReference = Payment::query()
            ->where('provider', 'stripe')
            ->where('stripe_payment_intent_id', $paymentIntentId)
            ->first();
    
        if (! $paymentReference) {
            return null;
        }
    
        $order = Order::query()
            ->whereKey($paymentReference->order_id)
            ->lockForUpdate()
            ->first();
    
        if (! $order) {
            return null;
        }
    
        $payment = Payment::query()
            ->whereKey($paymentReference->id)
            ->lockForUpdate()
            ->first();
    
        if (! $payment|| (int) $payment->order_id !== (int) $order->id|| $payment->stripe_payment_intent_id !== $paymentIntentId) {
            return null;
        }
    
        return [
            'order' => $order,
            'payment' => $payment,
        ];
    }

    private function handlePaymentIntentCanceled(
        object $paymentIntent
    ): void {
        if ( ! isset($paymentIntent->id, $paymentIntent->status) || ! is_string($paymentIntent->id) || ! is_string($paymentIntent->status) || $paymentIntent->status !== 'canceled') {
            throw new \RuntimeException('Stripe payment intent is invalid for cancellation.');
        }
    
        DB::transaction(function () use ($paymentIntent): void {
            $context = $this->lockPaymentContext($paymentIntent->id);
    
            if (! $context) {
                return;
            }
    
            $order = $context['order'];
            $payment = $context['payment'];
    
            // Pembayaran sukses tidak boleh dibatalkan.
            if ($payment->status === 'succeeded') {
                return;
            }
    
            // Pesanan yang sudah dikirim atau selesai tidak boleh dibatalkan.
            if (in_array($order->status, ['shipped', 'completed'], true)) {
                return;
            }
    
            // Jangan mengubah status order terminal selain canceled.
            if (in_array($order->status, ['failed', 'refunded'], true)) {
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
    
            if ($order->status === 'pending') {
                $order->update([
                    'status' => 'canceled',
                ]);
            }
    
            // Aman dipanggil ulang: reservasi released akan dilewati.
            app(VariantInventoryService::class)
                ->releaseReservationsForOrder($order);
        });
    }

    private function convertStripeAmount(int $amount): float
    {
        return $amount / 100;
    }
}
