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
            'midtrans.server_key',
            'test-server-key'
        );

        $this->mock(\App\Services\MidtransService::class, function ($mock) use ($order) {
            $mock->shouldReceive('verifyNotification')
                ->once()
                ->andReturn(true);

            $mock->shouldReceive('getStatus')
                ->once()
                ->with($order->order_number)
                ->andReturn((object) [
                    'order_id' => $order->order_number,
                    'transaction_status' => 'settlement',
                    'status_code' => '200',
                    'gross_amount' => '100000.00',
                    'payment_type' => 'bank_transfer',
                    'transaction_id' => 'MIDTRANS-TEST-123',
                    'fraud_status' => 'accept',
                ]);
        });

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
                    .'200'
                    .'100000.00'
                    .'test-server-key'
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
            'status' => 'processing',
        ]);
    }

    public function test_midtrans_notification_rejects_invalid_signature(): void
    {
        [$order, $payment] = $this->createPendingMidtransPayment();

        Config::set(
            'midtrans.server_key',
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
            'midtrans.server_key',
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
                    .'200'
                    .$grossAmount
                    .'test-server-key'
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
            'midtrans.server_key',
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
                    .'200'
                    .'100000.00'
                    .'test-server-key'
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

    public function test_midtrans_confirm_does_not_downgrade_payment_if_it_becomes_succeeded_during_provider_check(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

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
            'transaction_id' => 'MIDTRANS-CONFIRM-RACE',
        ]);

        $this->mock(\App\Services\MidtransService::class, function ($mock) use ($payment) {
            $mock
                ->shouldReceive('getStatus')
                ->once()
                ->andReturnUsing(function () use ($payment) {
                    /*
                    * Simulasikan notification/webhook lain yang lebih dulu
                    * menyelesaikan payment ketika confirm() sedang berjalan.
                    */
                    $payment->refresh();
                    $payment->update([
                        'status' => 'succeeded',
                    ]);

                    return (object) [
                        'transaction_status' => 'pending',
                        'transaction_id' => 'MIDTRANS-CONFIRM-RACE',
                        'gross_amount' => '100000.00',
                        'payment_type' => 'bank_transfer',
                    ];
                });
        });

        $response = $this->postJson(
            route('payment.midtrans.confirm', $order),
            [
                'transaction_id' => 'MIDTRANS-CONFIRM-RACE',
            ]
        );

        // $response->assertStatus(422);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    }

    public function test_midtrans_duplicate_notification_is_idempotent(): void
    {
        [$order, $payment] = $this->createPendingMidtransPayment();

        Config::set(
            'midtrans.server_key',
            'test-server-key'
        );

        $payload = [
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
                .'200'
                .'100000.00'
                .'test-server-key'
            ),
        ];

        $this->mock(\App\Services\MidtransService::class, function ($mock) use ($order) {
            $mock->shouldReceive('verifyNotification')
                ->twice()
                ->andReturn(true);

            $mock->shouldReceive('getStatus')
                ->once()
                ->with($order->order_number)
                ->andReturn((object) [
                    'order_id' => $order->order_number,
                    'transaction_status' => 'settlement',
                    'status_code' => '200',
                    'gross_amount' => '100000.00',
                    'payment_type' => 'bank_transfer',
                    'transaction_id' => 'MIDTRANS-TEST-123',
                    'fraud_status' => 'accept',
                ]);
        });

        $firstResponse = $this->postJson(
            route('payment.midtrans.notification'),
            $payload
        );

        $firstResponse->assertSuccessful();

        $payment->refresh();
        $order->refresh();

        $this->assertSame('succeeded', $payment->status);
        $this->assertSame('processing', $order->status);

        $firstPaidAt = $payment->paid_at;

        $secondResponse = $this->postJson(
            route('payment.midtrans.notification'),
            $payload
        );

        $secondResponse->assertSuccessful();

        $payment->refresh();
        $order->refresh();

        $this->assertSame('succeeded', $payment->status);
        $this->assertSame('processing', $order->status);

        $this->assertSame(
            $firstPaidAt?->format('Y-m-d H:i:s'),
            $payment->paid_at?->format('Y-m-d H:i:s')
        );
    }

    public function test_midtrans_pending_notification_does_not_downgrade_expired_payment(): void
    {
        [$order, $payment] = $this->createPendingMidtransPayment();

        $payment->update([
            'status' => 'expired',
        ]);

        Config::set('midtrans.server_key', 'test-server-key');

        $this->mock(\App\Services\MidtransService::class, function ($mock) use ($order) {
            $mock->shouldReceive('verifyNotification')
                ->once()
                ->andReturn(true);

            $mock->shouldReceive('getStatus')
                ->once()
                ->with($order->order_number)
                ->andReturn((object) [
                    'order_id' => $order->order_number,
                    'transaction_status' => 'pending',
                    'status_code' => '201',
                    'gross_amount' => '100000.00',
                    'payment_type' => 'bank_transfer',
                    'transaction_id' => 'MIDTRANS-TEST-123',
                ]);
        });

        $response = $this->postJson(
            route('payment.midtrans.notification'),
            [
                'order_id' => $order->order_number,
                'status_code' => '201',
                'gross_amount' => '100000.00',
                'signature_key' => hash(
                    'sha512',
                    $order->order_number
                    .'201'
                    .'100000.00'
                    .'test-server-key'
                ),
            ]
        );

        $response->assertSuccessful();

        $this->assertSame('expired', $payment->fresh()->status);
    }

    public function test_midtrans_success_does_not_reactivate_canceled_order(): void
    {
        [$order, $payment] = $this->createPendingMidtransPayment();

        $order->update([
            'status' => 'canceled',
        ]);

        $payment->update([
            'status' => 'canceled',
        ]);

        Config::set('midtrans.server_key', 'test-server-key');

        $this->mock(\App\Services\MidtransService::class, function ($mock) use ($order) {
            $mock->shouldReceive('verifyNotification')
                ->once()
                ->andReturn(true);

            $mock->shouldReceive('getStatus')
                ->once()
                ->with($order->order_number)
                ->andReturn((object) [
                    'order_id' => $order->order_number,
                    'transaction_status' => 'settlement',
                    'status_code' => '200',
                    'gross_amount' => '100000.00',
                    'payment_type' => 'bank_transfer',
                    'transaction_id' => 'MIDTRANS-TEST-123',
                    'fraud_status' => 'accept',
                ]);
        });

        $response = $this->postJson(
            route('payment.midtrans.notification'),
            [
                'order_id' => $order->order_number,
                'status_code' => '200',
                'gross_amount' => '100000.00',
                'signature_key' => hash(
                    'sha512',
                    $order->order_number
                    .'200'
                    .'100000.00'
                    .'test-server-key'
                ),
            ]
        );

        $response->assertSuccessful();

        $this->assertSame('canceled', $order->fresh()->status);
    }


    public function test_midtrans_confirm_does_not_downgrade_expired_payment(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'expired',
            'gross_amount' => 100000,
        ]);

        $this->mock(\App\Services\MidtransService::class, function ($mock) use ($order) {
            $mock->shouldReceive('getStatus')
                ->once()
                ->with($order->order_number)
                ->andReturn((object) [
                    'order_id' => $order->order_number,
                    'transaction_status' => 'pending',
                    'status_code' => '201',
                    'gross_amount' => '100000.00',
                    'payment_type' => 'bank_transfer',
                    'transaction_id' => 'MIDTRANS-EXPIRED-TEST',
                ]);
        });

        $this->postJson(
            route('payment.midtrans.confirm', $order),
            []
        );

        $this->assertSame('expired', $payment->fresh()->status);
    }

    public function test_midtrans_confirm_does_not_reactivate_canceled_order(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'canceled',
            'total' => 100000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'canceled',
            'gross_amount' => 100000,
        ]);

        $this->mock(\App\Services\MidtransService::class, function ($mock) use ($order) {
            $mock->shouldReceive('getStatus')
                ->once()
                ->with($order->order_number)
                ->andReturn((object) [
                    'order_id' => $order->order_number,
                    'transaction_status' => 'settlement',
                    'status_code' => '200',
                    'gross_amount' => '100000.00',
                    'payment_type' => 'bank_transfer',
                    'transaction_id' => 'MIDTRANS-CANCELED-TEST',
                    'fraud_status' => 'accept',
                ]);
        });

        $this->postJson(
            route('payment.midtrans.confirm', $order),
            []
        );

        $this->assertSame('canceled', $order->fresh()->status);
    }
}
