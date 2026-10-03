<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\MidtransService;
use App\Services\RefundService;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Stripe\Refund as StripeRefund;
use Tests\TestCase;

class RefundServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_payment_cannot_be_refunded(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 150000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'gross_amount' => 150000,
        ]);

        $service = app(RefundService::class);

        $this->expectException(ValidationException::class);

        $service->request(
            $order,
            150000,
            'Customer meminta refund'
        );

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_succeeded_payment_can_request_refund(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'stripe_payment_intent_id' => 'pi_test_123',
            'gross_amount' => 150000,
        ]);

        $refund = app(RefundService::class)->request(
            $order,
            150000,
            'Customer meminta refund'
        );

        $this->assertInstanceOf(
            Refund::class,
            $refund
        );

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 150000,
            'currency' => 'IDR',
            'reason' => 'Customer meminta refund',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
        ]);
    }

    public function test_partial_refund_is_allowed(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $refund = app(RefundService::class)->request(
            $order,
            50000,
            'Produk rusak sebagian'
        );

        $this->assertSame(
            '50000.00',
            $refund->amount
        );

        $this->assertDatabaseHas('refunds', [
            'payment_id' => $payment->id,
            'amount' => 50000,
            'status' => Refund::STATUS_REQUESTED,
        ]);
    }

    public function test_refund_cannot_exceed_payment_amount(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $service = app(RefundService::class);

        $this->expectException(ValidationException::class);

        $service->request(
            $order,
            150001,
            'Refund melebihi pembayaran'
        );

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_multiple_partial_refunds_cannot_exceed_payment_amount(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => Refund::STATUS_COMPLETED,
            'provider' => 'stripe',
            'requested_at' => now(),
            'processed_at' => now(),
        ]);

        $service = app(RefundService::class);

        $this->expectException(ValidationException::class);

        $service->request(
            $order,
            50001,
            'Refund kedua'
        );

        $this->assertDatabaseCount('refunds', 1);
    }

    public function test_remaining_amount_can_be_refunded_after_partial_refund(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'status' => Refund::STATUS_COMPLETED,
            'provider' => 'stripe',
            'requested_at' => now(),
            'processed_at' => now(),
        ]);

        $refund = app(RefundService::class)->request(
            $order,
            100000,
            'Refund sisa pembayaran'
        );

        $this->assertSame(
            '100000.00',
            $refund->amount
        );

        $this->assertDatabaseCount('refunds', 2);
    }

    public function test_zero_refund_amount_is_rejected(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $service = app(RefundService::class);

        $this->expectException(ValidationException::class);

        $service->request(
            $order,
            0,
            'Nominal tidak valid'
        );

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_stripe_refund_is_completed_when_provider_succeeds(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'stripe_payment_intent_id' => 'pi_test_123',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Produk rusak',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
            'requested_at' => now(),
        ]);

        /*
         * Gunakan objek Stripe Refund asli agar kompatibel
         * dengan StripeObject::toArray() dan magic properties.
         */
        $stripeRefund = StripeRefund::constructFrom([
            'id' => 're_test_123',
            'object' => 'refund',
            'amount' => 5000000,
            'currency' => 'idr',
            'status' => 'succeeded',
            'payment_intent' => 'pi_test_123',
        ]);

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $stripe
            ->shouldReceive('refund')
            ->once()
            ->with(
                'pi_test_123',
                5000000
            )
            ->andReturn($stripeRefund);

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $result = $service->process($refund);

        $this->assertSame(
            Refund::STATUS_COMPLETED,
            $result->status
        );

        $this->assertSame(
            're_test_123',
            $result->reference_id
        );

        $this->assertNotNull(
            $result->processed_at
        );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_COMPLETED,
            'reference_id' => 're_test_123',
        ]);
    }

    public function test_midtrans_refund_is_completed_when_provider_succeeds(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'succeeded',
            'transaction_id' => 'MIDTRANS-ORDER-123',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Produk rusak',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'midtrans',
            'requested_at' => now(),
        ]);

        $midtransResponse = (object) [
            'transaction_status' => 'refund',
            'refund_key' => 'refund-1',
            'refund_chargeback_id' => null,
        ];

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $midtrans
            ->shouldReceive('refund')
            ->once()
            ->with(
                'MIDTRANS-ORDER-123',
                50000,
                'refund-' . $refund->id,
                'Produk rusak'
            )
            ->andReturn($midtransResponse);

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $result = $service->process($refund);

        $this->assertSame(
            Refund::STATUS_COMPLETED,
            $result->status
        );

        $this->assertSame(
            'refund-1',
            $result->reference_id
        );

        $this->assertNotNull(
            $result->processed_at
        );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_COMPLETED,
            'reference_id' => 'refund-1',
        ]);
    }

    public function test_midtrans_partial_refund_is_completed(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'succeeded',
            'transaction_id' => 'MIDTRANS-ORDER-456',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Refund sebagian',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'midtrans',
            'requested_at' => now(),
        ]);

        $midtransResponse = (object) [
            'transaction_status' => 'partial_refund',
            'refund_key' => 'refund-partial-1',
            'refund_chargeback_id' => null,
        ];

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $midtrans
            ->shouldReceive('refund')
            ->once()
            ->with(
                'MIDTRANS-ORDER-456',
                50000,
                'refund-' . $refund->id,
                'Refund sebagian'
            )
            ->andReturn($midtransResponse);

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $result = $service->process($refund);

        $this->assertSame(
            Refund::STATUS_COMPLETED,
            $result->status
        );

        $this->assertSame(
            'refund-partial-1',
            $result->reference_id
        );
    }

    public function test_midtrans_refund_is_failed_when_provider_throws_exception(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'succeeded',
            'transaction_id' => 'MIDTRANS-ORDER-789',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Provider error',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'midtrans',
            'requested_at' => now(),
        ]);

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $midtrans
            ->shouldReceive('refund')
            ->once()
            ->andThrow(
                new \RuntimeException(
                    'Midtrans refund failed'
                )
            );

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $result = $service->process($refund);

        $this->assertSame(
            Refund::STATUS_FAILED,
            $result->status
        );

        $this->assertNotNull(
            $result->processed_at
        );

        $this->assertSame(
            'Midtrans refund failed',
            $result->metadata['error']
        );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_FAILED,
        ]);
    }

    public function test_multiple_requested_refunds_cannot_exceed_payment_amount(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $service = app(RefundService::class);

        $firstRefund = $service->request(
            $order,
            100000,
            'Refund pertama'
        );

        $this->assertSame(
            Refund::STATUS_REQUESTED,
            $firstRefund->status
        );

        $this->expectException(ValidationException::class);

        $service->request(
            $order,
            50001,
            'Refund kedua'
        );
    }

    public function test_cod_refund_is_approved(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'cod',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Produk dikembalikan',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'cod',
            'requested_at' => now(),
        ]);

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $result = $service->process($refund);

        $this->assertSame(
            Refund::STATUS_APPROVED,
            $result->status
        );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_APPROVED,
        ]);
    }

    public function test_order_without_payment_cannot_request_refund(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $service = app(RefundService::class);

        $this->expectException(ValidationException::class);

        $service->request(
            $order,
            50000,
            'Refund tanpa payment'
        );

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_refund_cannot_be_processed_when_payment_is_no_longer_succeeded(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $service = app(RefundService::class);

        $refund = $service->request(
            $order,
            50000,
            'Payment berubah sebelum diproses'
        );

        // Simulasikan payment berubah setelah refund dibuat.
        $payment->update([
            'status' => 'failed',
        ]);

        $this->expectException(ValidationException::class);

        try {
            $service->process($refund);
        } finally {
            $this->assertDatabaseHas('refunds', [
                'id' => $refund->id,
                'status' => Refund::STATUS_REQUESTED,
            ]);
        }
    }

    public function test_stripe_refund_fails_when_payment_intent_id_is_missing(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
            'stripe_payment_intent_id' => null,
        ]);

        $service = app(RefundService::class);

        $refund = $service->request(
            $order,
            50000,
            'Stripe PaymentIntent ID tidak tersedia'
        );

        $processedRefund = $service->process($refund);



        $this->assertSame(
            Refund::STATUS_FAILED,
            $processedRefund->status
        );

        $this->assertSame(
            'Stripe PaymentIntent ID tidak tersedia.',
            $processedRefund->metadata['error']
        );

        $this->assertNotNull($processedRefund->processed_at);
    }

    public function test_stripe_refund_remains_processing_when_provider_returns_pending(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'stripe_payment_intent_id' => 'pi_test_123',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Produk rusak',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
            'requested_at' => now(),
        ]);

        $stripeRefund = StripeRefund::constructFrom([
            'id' => 're_pending_123',
            'object' => 'refund',
            'amount' => 5000000,
            'currency' => 'idr',
            'status' => 'pending',
            'payment_intent' => 'pi_test_123',
        ]);

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $stripe
            ->shouldReceive('refund')
            ->once()
            ->with(
                'pi_test_123',
                5000000
            )
            ->andReturn($stripeRefund);

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $result = $service->process($refund);

        $this->assertSame(
            Refund::STATUS_PROCESSING,
            $result->status
        );

        $this->assertSame(
            're_pending_123',
            $result->reference_id
        );

        $this->assertNull(
            $result->processed_at
        );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_PROCESSING,
            'reference_id' => 're_pending_123',
        ]);
    }

    public function test_stripe_refund_is_failed_when_provider_returns_failed(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'stripe_payment_intent_id' => 'pi_test_123',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Produk bermasalah',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
            'requested_at' => now(),
        ]);

        $stripeRefund = StripeRefund::constructFrom([
            'id' => 're_failed_123',
            'object' => 'refund',
            'amount' => 5000000,
            'currency' => 'idr',
            'status' => 'failed',
            'payment_intent' => 'pi_test_123',
        ]);

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $stripe
            ->shouldReceive('refund')
            ->once()
            ->with(
                'pi_test_123',
                5000000
            )
            ->andReturn($stripeRefund);

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $result = $service->process($refund);

        $this->assertSame(
            Refund::STATUS_FAILED,
            $result->status
        );

        $this->assertSame(
            're_failed_123',
            $result->reference_id
        );

        $this->assertNull(
            $result->processed_at
        );

        $this->assertNotNull(
            $result->metadata
        );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_FAILED,
            'reference_id' => 're_failed_123',
        ]);
    }

    public function test_stripe_refund_is_rejected_when_provider_returns_canceled(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'stripe_payment_intent_id' => 'pi_test_123',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Refund dibatalkan',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
            'requested_at' => now(),
        ]);

        $stripeRefund = StripeRefund::constructFrom([
            'id' => 're_canceled_123',
            'object' => 'refund',
            'amount' => 5000000,
            'currency' => 'idr',
            'status' => 'canceled',
            'payment_intent' => 'pi_test_123',
        ]);

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $stripe
            ->shouldReceive('refund')
            ->once()
            ->with(
                'pi_test_123',
                5000000
            )
            ->andReturn($stripeRefund);

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $result = $service->process($refund);

        $this->assertSame(
            Refund::STATUS_REJECTED,
            $result->status
        );

        $this->assertSame(
            're_canceled_123',
            $result->reference_id
        );

        $this->assertNull(
            $result->processed_at
        );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_REJECTED,
            'reference_id' => 're_canceled_123',
        ]);
    }

    public function test_stripe_refund_remains_processing_when_provider_requires_action(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'stripe_payment_intent_id' => 'pi_test_123',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Memerlukan tindakan tambahan',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
            'requested_at' => now(),
        ]);

        $stripeRefund = StripeRefund::constructFrom([
            'id' => 're_action_123',
            'object' => 'refund',
            'amount' => 5000000,
            'currency' => 'idr',
            'status' => 'requires_action',
            'payment_intent' => 'pi_test_123',
        ]);

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $stripe
            ->shouldReceive('refund')
            ->once()
            ->with(
                'pi_test_123',
                5000000
            )
            ->andReturn($stripeRefund);

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $result = $service->process($refund);

        $this->assertSame(
            Refund::STATUS_PROCESSING,
            $result->status
        );

        $this->assertSame(
            're_action_123',
            $result->reference_id
        );

        $this->assertNull(
            $result->processed_at
        );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_PROCESSING,
            'reference_id' => 're_action_123',
        ]);
    }

    public function test_midtrans_refund_uses_order_number_when_transaction_id_is_missing(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'succeeded',
            'transaction_id' => null,
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Refund menggunakan order number',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'midtrans',
            'requested_at' => now(),
        ]);

        $midtransResponse = (object) [
            'transaction_status' => 'refund',
            'refund_key' => 'refund_test_123',
        ];

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $midtrans
            ->shouldReceive('refund')
            ->once()
            ->with(
                $order->order_number,
                50000,
                'refund-' . $refund->id,
                'Refund menggunakan order number'
            )
            ->andReturn($midtransResponse);

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $result = $service->process($refund);

        $this->assertSame(
            Refund::STATUS_COMPLETED,
            $result->status
        );

        $this->assertSame(
            'refund_test_123',
            $result->reference_id
        );

        $this->assertNotNull(
            $result->processed_at
        );
    }

    public function test_refund_rejects_unsupported_payment_provider(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'unknown_provider',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Provider tidak dikenal',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'unknown_provider',
            'requested_at' => now(),
        ]);

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $stripe->shouldNotReceive('refund');

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $midtrans->shouldNotReceive('refund');

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $this->expectException(ValidationException::class);

        try {
            $service->process($refund);
        } finally {
            $this->assertDatabaseHas('refunds', [
                'id' => $refund->id,
                'status' => Refund::STATUS_REQUESTED,
            ]);
        }
    }

    public function test_completed_refund_cannot_be_processed_again(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'stripe_payment_intent_id' => 'pi_test_123',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Refund sudah selesai',
            'status' => Refund::STATUS_COMPLETED,
            'provider' => 'stripe',
            'reference_id' => 're_existing_123',
            'requested_at' => now()->subMinutes(10),
            'processed_at' => now(),
        ]);

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $stripe->shouldNotReceive('refund');

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $midtrans->shouldNotReceive('refund');

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $this->expectException(ValidationException::class);

        try {
            $service->process($refund);
        } finally {
            $this->assertDatabaseHas('refunds', [
                'id' => $refund->id,
                'status' => Refund::STATUS_COMPLETED,
                'reference_id' => 're_existing_123',
            ]);
        }
    }

    public function test_processing_refund_cannot_be_processed_again(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'stripe_payment_intent_id' => 'pi_test_123',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Refund masih diproses',
            'status' => Refund::STATUS_PROCESSING,
            'provider' => 'stripe',
            'reference_id' => 're_processing_123',
            'requested_at' => now()->subMinutes(10),
        ]);

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $stripe->shouldNotReceive('refund');

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $midtrans->shouldNotReceive('refund');

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $this->expectException(ValidationException::class);

        try {
            $service->process($refund);
        } finally {
            $this->assertDatabaseHas('refunds', [
                'id' => $refund->id,
                'status' => Refund::STATUS_PROCESSING,
                'reference_id' => 're_processing_123',
            ]);
        }
    }

    public function test_failed_refund_cannot_be_processed_again(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'stripe_payment_intent_id' => 'pi_test_123',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Refund gagal',
            'status' => Refund::STATUS_FAILED,
            'provider' => 'stripe',
            'reference_id' => 're_failed_123',
            'requested_at' => now()->subMinutes(10),
            'processed_at' => now()->subMinutes(5),
        ]);

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $stripe->shouldNotReceive('refund');

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $midtrans->shouldNotReceive('refund');

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $this->expectException(ValidationException::class);

        try {
            $service->process($refund);
        } finally {
            $this->assertDatabaseHas('refunds', [
                'id' => $refund->id,
                'status' => Refund::STATUS_FAILED,
                'reference_id' => 're_failed_123',
            ]);
        }
    }

    public function test_rejected_refund_cannot_be_processed_again(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'stripe_payment_intent_id' => 'pi_test_123',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Refund ditolak',
            'status' => Refund::STATUS_REJECTED,
            'provider' => 'stripe',
            'reference_id' => 're_rejected_123',
            'requested_at' => now()->subMinutes(10),
            'processed_at' => now()->subMinutes(5),
        ]);

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $stripe->shouldNotReceive('refund');

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $midtrans->shouldNotReceive('refund');

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $this->expectException(ValidationException::class);

        try {
            $service->process($refund);
        } finally {
            $this->assertDatabaseHas('refunds', [
                'id' => $refund->id,
                'status' => Refund::STATUS_REJECTED,
                'reference_id' => 're_rejected_123',
            ]);
        }
    }

    public function test_cod_refund_is_approved_without_calling_payment_provider(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'cod',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Refund COD',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'cod',
            'requested_at' => now(),
        ]);

        $stripe = Mockery::mock(
            StripePaymentService::class
        );

        $stripe->shouldNotReceive('refund');

        $midtrans = Mockery::mock(
            MidtransService::class
        );

        $midtrans->shouldNotReceive('refund');

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $result = $service->process($refund);

        $this->assertSame(
            Refund::STATUS_APPROVED,
            $result->status
        );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_APPROVED,
        ]);
    }

    public function test_refund_rejects_non_numeric_amount(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'cod',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $stripe = Mockery::mock(StripePaymentService::class);
        $midtrans = Mockery::mock(MidtransService::class);

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $this->expectException(ValidationException::class);

        $service->request(
            $order,
            'abc',
            'Invalid amount'
        );
    }

    public function test_refund_rejects_amount_that_exceeds_remaining_amount(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'cod',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => Refund::STATUS_COMPLETED,
            'provider' => 'cod',
            'requested_at' => now()->subMinutes(10),
            'processed_at' => now()->subMinutes(5),
        ]);

        $stripe = Mockery::mock(StripePaymentService::class);
        $midtrans = Mockery::mock(MidtransService::class);

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $this->expectException(ValidationException::class);

        try {
            $service->request(
                $order,
                50001,
                'Melebihi sisa refundable amount'
            );
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Nominal refund melebihi sisa dana yang dapat direfund.',
                $exception->errors()['amount'][0]
            );

            throw $exception;
        }
    }

    public function test_refund_allows_amount_equal_to_remaining_amount(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'cod',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => Refund::STATUS_COMPLETED,
            'provider' => 'cod',
            'requested_at' => now()->subMinutes(10),
            'processed_at' => now()->subMinutes(5),
        ]);

        $stripe = Mockery::mock(StripePaymentService::class);
        $midtrans = Mockery::mock(MidtransService::class);

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $refund = $service->request(
            $order,
            50000,
            'Refund seluruh sisa dana'
        );

        $this->assertSame(
            50000.0,
            (float) $refund->amount
        );

        $this->assertSame(
            Refund::STATUS_REQUESTED,
            $refund->status
        );
    }

    public function test_refund_request_rejects_payment_that_is_not_succeeded(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'cod',
            'status' => 'pending',
            'gross_amount' => 150000,
        ]);

        $stripe = Mockery::mock(StripePaymentService::class);
        $midtrans = Mockery::mock(MidtransService::class);

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $this->expectException(ValidationException::class);

        try {
            $service->request(
                $order,
                50000,
                'Refund payment pending'
            );
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Pembayaran belum berhasil sehingga belum dapat direfund.',
                $exception->errors()['payment'][0]
            );

            $this->assertDatabaseCount('refunds', 0);

            throw $exception;
        }
    }

    public function test_midtrans_unknown_status_keeps_refund_processing(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'succeeded',
            'transaction_id' => 'transaction-123',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Test unknown status',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'midtrans',
            'requested_at' => now(),
        ]);

        $stripe = Mockery::mock(StripePaymentService::class);
        $stripe->shouldNotReceive('refund');

        $midtrans = Mockery::mock(MidtransService::class);

        $midtrans->shouldReceive('refund')
            ->once()
            ->andReturn((object) [
                'transaction_status' => 'deny',
                'refund_key' => 'refund-key-123',
            ]);

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $result = $service->process($refund);

        $this->assertSame(
            Refund::STATUS_PROCESSING,
            $result->status
        );

        $this->assertSame(
            'refund-key-123',
            $result->reference_id
        );
    }

    public function test_midtrans_completed_refund_without_reference_id(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'succeeded',
            'transaction_id' => 'transaction-123',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Test missing reference',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'midtrans',
            'requested_at' => now(),
        ]);

        $stripe = Mockery::mock(StripePaymentService::class);
        $stripe->shouldNotReceive('refund');

        $midtrans = Mockery::mock(MidtransService::class);

        $midtrans->shouldReceive('refund')
            ->once()
            ->andReturn((object) [
                'transaction_status' => 'refund',
            ]);

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $result = $service->process($refund);

        $this->assertSame(
            Refund::STATUS_COMPLETED,
            $result->status
        );

        $this->assertNull(
            $result->reference_id
        );

        $this->assertNotNull(
            $result->processed_at
        );
    }

    public function test_stripe_refund_is_failed_when_provider_throws_exception(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'stripe_payment_intent_id' => 'pi_test_123',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Stripe error',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
            'requested_at' => now(),
        ]);

        $stripe = Mockery::mock(StripePaymentService::class);

        $stripe->shouldReceive('refund')
            ->once()
            ->andThrow(new \RuntimeException('Stripe refund failed'));

        $midtrans = Mockery::mock(MidtransService::class);
        $midtrans->shouldNotReceive('refund');

        $service = new RefundService(
            $stripe,
            $midtrans
        );

        $result = $service->process($refund);

        $this->assertSame(
            Refund::STATUS_FAILED,
            $result->status
        );

        $this->assertNotNull(
            $result->processed_at
        );

        $this->assertSame(
            'Stripe refund failed',
            $result->metadata['error']
        );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_FAILED,
        ]);
    }
}