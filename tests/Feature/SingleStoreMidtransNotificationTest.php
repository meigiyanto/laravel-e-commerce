<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\MidtransService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleStoreMidtransNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'single-store-server-key';
    private const ORDER_TOTAL = 100000;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'operational.mode' => 'single_store',
            'midtrans.server_key' => self::SERVER_KEY,
        ]);

        app(TenantContext::class)->clear();
    }

    protected function tearDown(): void
    {
        app(TenantContext::class)->clear();

        parent::tearDown();
    }

    private function createPendingMidtransOrder(): array
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'tenant_id' => null,
            'user_id' => $user->id,
            'status' => 'pending',
            'subtotal' => self::ORDER_TOTAL,
            'shipping_cost' => 0,
            'total' => self::ORDER_TOTAL,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'pending',
            'gross_amount' => self::ORDER_TOTAL,
            'transaction_id' => 'MIDTRANS-SINGLE-STORE-TEST',
            'payment_type' => 'bank_transfer',
            'transaction_status' => 'pending',
        ]);

        return [$order, $payment];
    }

    private function makePayload(
        Order $order,
        string $grossAmount = '100000.00'
    ): array {
        return [
            'order_id' => $order->order_number,
            'transaction_id' => 'MIDTRANS-SINGLE-STORE-TEST',
            'transaction_status' => 'settlement',
            'status_code' => '200',
            'gross_amount' => $grossAmount,
            'payment_type' => 'bank_transfer',
            'signature_key' => hash(
                'sha512',
                $order->order_number
                    .'200'
                    .$grossAmount
                    .self::SERVER_KEY
            ),
        ];
    }

    public function test_single_store_midtrans_settlement_completes_payment_and_order(): void
    {
        [$order, $payment] = $this->createPendingMidtransOrder();

        $this->assertNull($order->tenant_id);

        $this->mock(MidtransService::class, function ($mock) use ($order) {
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
                    'transaction_id' => 'MIDTRANS-SINGLE-STORE-TEST',
                    'fraud_status' => 'accept',
                ]);
        });

        $response = $this->postJson(
            route('payment.midtrans.notification'),
            $this->makePayload($order)
        );

        $response->assertSuccessful()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'tenant_id' => null,
            'status' => 'processing',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
            'provider' => 'midtrans',
            'transaction_status' => 'settlement',
        ]);

        $this->assertFalse(
            app(TenantContext::class)->hasTenant()
        );
    }

    public function test_single_store_midtrans_notification_rejects_invalid_signature(): void
    {
        [$order, $payment] = $this->createPendingMidtransOrder();

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldReceive('verifyNotification')
                ->once()
                ->andReturn(false);

            $mock->shouldNotReceive('getStatus');
        });

        $payload = $this->makePayload($order);
        $payload['signature_key'] = 'invalid-signature';

        $this->postJson(
            route('payment.midtrans.notification'),
            $payload
        )->assertStatus(403);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'tenant_id' => null,
            'status' => 'pending',
        ]);

        $this->assertFalse(
            app(TenantContext::class)->hasTenant()
        );
    }

    public function test_single_store_midtrans_notification_rejects_wrong_payload_amount(): void
    {
        [$order, $payment] = $this->createPendingMidtransOrder();

        $this->mock(MidtransService::class, function ($mock) {
            $mock->shouldReceive('verifyNotification')
                ->once()
                ->andReturn(true);

            $mock->shouldNotReceive('getStatus');
        });

        $response = $this->postJson(
            route('payment.midtrans.notification'),
            $this->makePayload($order, '99999.00')
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

        $this->assertFalse(
            app(TenantContext::class)->hasTenant()
        );
    }

    public function test_single_store_midtrans_notification_rejects_gateway_amount_mismatch(): void
    {
        [$order, $payment] = $this->createPendingMidtransOrder();

        $this->mock(MidtransService::class, function ($mock) use ($order) {
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
                    'gross_amount' => '99999.00',
                    'payment_type' => 'bank_transfer',
                    'transaction_id' => 'MIDTRANS-SINGLE-STORE-TEST',
                    'fraud_status' => 'accept',
                ]);
        });

        $response = $this->postJson(
            route('payment.midtrans.notification'),
            $this->makePayload($order)
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

        $this->assertFalse(
            app(TenantContext::class)->hasTenant()
        );
    }

    public function test_single_store_midtrans_duplicate_notification_is_idempotent(): void
    {
        [$order, $payment] = $this->createPendingMidtransOrder();

        $this->mock(MidtransService::class, function ($mock) use ($order) {
            $mock->shouldReceive('verifyNotification')
                ->twice()
                ->andReturn(true);

            // Get Status hanya dipanggil pada notifikasi pertama.
            $mock->shouldReceive('getStatus')
                ->once()
                ->with($order->order_number)
                ->andReturn((object) [
                    'order_id' => $order->order_number,
                    'transaction_status' => 'settlement',
                    'status_code' => '200',
                    'gross_amount' => '100000.00',
                    'payment_type' => 'bank_transfer',
                    'transaction_id' => 'MIDTRANS-SINGLE-STORE-TEST',
                    'fraud_status' => 'accept',
                ]);
        });

        $payload = $this->makePayload($order);

        $this->postJson(
            route('payment.midtrans.notification'),
            $payload
        )->assertSuccessful();

        $this->postJson(
            route('payment.midtrans.notification'),
            $payload
        )->assertSuccessful();

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'tenant_id' => null,
            'status' => 'processing',
        ]);

        $this->assertDatabaseCount('payments', 1);

        $this->assertFalse(
            app(TenantContext::class)->hasTenant()
        );
    }
}
