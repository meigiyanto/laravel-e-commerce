<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Stripe\PaymentIntent;
use Tests\TestCase;

class StockRestorationIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_stripe_cancellation_does_not_restore_stock_twice_after_admin_cancellation(): void
    {
        $user = User::factory()->create();

        // Stok sudah dikurangi saat checkout:
        // 10 unit awal - 2 unit yang dipesan = 8.
        $product = Product::factory()->create([
            'stock' => 8,
        ]);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 200000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 100000,
            'quantity' => 2,
            'subtotal' => 200000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'transaction_status' => 'requires_payment_method',
            'gross_amount' => 200000,
            'currency' => 'IDR',
            'stripe_payment_intent_id' => 'pi_stock_cancel_test',
        ]);

        // Tahap 1: admin membatalkan pesanan.
        $this->actingAs(
            User::factory()->create(['role' => 'admin'])
        )->patch(
            route('admin.orders.status', $order),
            ['status' => 'canceled']
        );

        $this->assertSame(
            'canceled',
            $order->fresh()->status
        );

        $this->assertSame(
            10,
            $product->fresh()->stock
        );

        // Tahap 2: Stripe kemudian melaporkan canceled.
        $paymentIntent = PaymentIntent::constructFrom([
            'id' => 'pi_stock_cancel_test',
            'object' => 'payment_intent',
            'status' => 'canceled',
            'amount' => 20000000,
            'currency' => 'idr',
            'metadata' => [
                'order_id' => (string) $order->id,
            ],
        ]);

        $method = new ReflectionMethod(
            PaymentController::class,
            'cancelOrder'
        );

        $method->invoke(
            app(PaymentController::class),
            $order->fresh(),
            $payment->fresh(),
            'canceled',
            $paymentIntent
        );

        // Stok seharusnya tetap 10, bukan menjadi 12.
        $this->assertSame(
            10,
            $product->fresh()->stock
        );

        $this->assertSame(
            'canceled',
            $order->fresh()->status
        );

        $this->assertSame(
            'canceled',
            $payment->fresh()->status
        );
    }
}
