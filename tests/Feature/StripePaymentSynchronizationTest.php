<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Stripe\PaymentIntent;
use Tests\TestCase;

class StripePaymentSynchronizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_payment_moves_pending_order_to_processing(): void
    {
        [$order, $payment] = $this->createPaymentAndOrder();

        $this->syncPaymentIntent($order, $payment, [
            'id' => 'pi_test_success',
            'status' => 'succeeded',
            'amount' => 10000000,
            'currency' => 'idr',
        ]);

        $this->assertSame(
            'succeeded',
            $payment->fresh()->status
        );

        $this->assertSame(
            'processing',
            $order->fresh()->status
        );
    }

    public function test_successful_payment_does_not_move_shipped_order_back_to_processing(): void
    {
        [$order, $payment] = $this->createPaymentAndOrder(
            orderStatus: 'shipped'
        );

        $this->syncPaymentIntent($order, $payment, [
            'id' => 'pi_test_shipped',
            'status' => 'succeeded',
            'amount' => 10000000,
            'currency' => 'idr',
        ]);

        $this->assertSame(
            'succeeded',
            $payment->fresh()->status
        );

        $this->assertSame(
            'shipped',
            $order->fresh()->status
        );
    }

    public function test_successful_payment_does_not_change_completed_order(): void
    {
        [$order, $payment] = $this->createPaymentAndOrder(
            orderStatus: 'completed'
        );

        $this->syncPaymentIntent($order, $payment, [
            'id' => 'pi_test_completed',
            'status' => 'succeeded',
            'amount' => 10000000,
            'currency' => 'idr',
        ]);

        $this->assertSame(
            'succeeded',
            $payment->fresh()->status
        );

        $this->assertSame(
            'completed',
            $order->fresh()->status
        );
    }

    public function test_already_succeeded_payment_is_not_downgraded(): void
    {
        [$order, $payment] = $this->createPaymentAndOrder(
            orderStatus: 'shipped',
            paymentStatus: 'succeeded'
        );

        $this->syncPaymentIntent($order, $payment, [
            'id' => 'pi_test_already_paid',
            'status' => 'canceled',
            'amount' => 10000000,
            'currency' => 'idr',
        ]);

        $this->assertSame(
            'succeeded',
            $payment->fresh()->status
        );

        $this->assertSame(
            'shipped',
            $order->fresh()->status
        );
    }

    private function createPaymentAndOrder(
        string $orderStatus = 'pending',
        string $paymentStatus = 'pending'
    ): array {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => $orderStatus,
            'total' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => $paymentStatus,
            'transaction_status' => $paymentStatus,
            'stripe_payment_intent_id' => 'pi_test_'.uniqid(),
            'gross_amount' => 100000,
            'currency' => 'IDR',
        ]);

        return [$order, $payment];
    }

    private function syncPaymentIntent(
        Order $order,
        Payment $payment,
        array $intentData
    ): void {
        $intentData['metadata'] = [
            'order_id' => (string) $order->id,
        ];

        $paymentIntent = PaymentIntent::constructFrom(
            $intentData
        );

        $method = new ReflectionMethod(
            PaymentController::class,
            'syncPaymentIntent'
        );

        $method->invoke(
            app(PaymentController::class),
            $order,
            $payment,
            $paymentIntent
        );
    }
}
