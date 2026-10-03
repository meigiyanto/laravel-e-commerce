<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MidtransNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function createPendingMidtransPayment(): array
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'pending',
            'gross_amount' => 100000,
            'transaction_id' => 'MIDTRANS-TEST-123',
        ]);

        return [$order, $payment];
    }

    public function test_midtrans_settlement_completes_payment_and_order(): void
    {
        [$order, $payment] = $this->createPendingMidtransPayment();

        Config::set(
            'services.midtrans.server_key',
            'test-server-key'
        );

        $response = $this->postJson(
            route('payment.midtrans.notification'),
            [
                'order_id' => $order->order_number,
                'transaction_id' => 'MIDTRANS-TEST-123',
                'transaction_status' => 'settlement',
                'fraud_status' => 'accept',
                'status_code' => '200',
                'gross_amount' => '100000.00',
                'payment_type' => 'bank_transfer',
                'signature_key' => hash(
                    'sha512',
                    $order->order_number
                    . '200'
                    . '100000.00'
                    . 'test-server-key'
                ),
            ]
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);
    }

    public function test_midtrans_notification_rejects_invalid_signature(): void
    {
        [$order, $payment] = $this->createPendingMidtransPayment();

        Config::set(
            'services.midtrans.server_key',
            'test-server-key'
        );

        $response = $this->postJson(
            route('payment.midtrans.notification'),
            [
                'order_id' => $order->order_number,
                'transaction_id' => 'MIDTRANS-TEST-123',
                'transaction_status' => 'settlement',
                'fraud_status' => 'accept',
                'status_code' => '200',
                'gross_amount' => '100000.00',
                'payment_type' => 'bank_transfer',
                'signature_key' => 'invalid-signature',
            ]
        );

        $response->assertStatus(403);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_midtrans_notification_rejects_wrong_gross_amount(): void
    {
        [$order, $payment] = $this->createPendingMidtransPayment();

        Config::set(
            'services.midtrans.server_key',
            'test-server-key'
        );

        $grossAmount = '99999.00';

        $response = $this->postJson(
            route('payment.midtrans.notification'),
            [
                'order_id' => $order->order_number,
                'transaction_id' => 'MIDTRANS-TEST-123',
                'transaction_status' => 'settlement',
                'fraud_status' => 'accept',
                'status_code' => '200',
                'gross_amount' => $grossAmount,
                'payment_type' => 'bank_transfer',
                'signature_key' => hash(
                    'sha512',
                    $order->order_number
                    . '200'
                    . $grossAmount
                    . 'test-server-key'
                ),
            ]
        );

        $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_midtrans_notification_does_not_downgrade_succeeded_payment(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'succeeded',
            'gross_amount' => 100000,
            'transaction_id' => 'MIDTRANS-ALREADY-SUCCESS',
        ]);

        Config::set(
            'services.midtrans.server_key',
            'test-server-key'
        );

        $response = $this->postJson(
            route('payment.midtrans.notification'),
            [
                'order_id' => $order->order_number,
                'transaction_id' => 'MIDTRANS-ALREADY-SUCCESS',
                'transaction_status' => 'cancel',
                'fraud_status' => 'accept',
                'status_code' => '200',
                'gross_amount' => '100000.00',
                'payment_type' => 'bank_transfer',
                'signature_key' => hash(
                    'sha512',
                    $order->order_number
                    . '200'
                    . '100000.00'
                    . 'test-server-key'
                ),
            ]
        );

        $response->assertSuccessful();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'completed',
        ]);
    }
}
