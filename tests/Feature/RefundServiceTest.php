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
}