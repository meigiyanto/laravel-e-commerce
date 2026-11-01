<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Refund;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Inventory;
use App\Models\InventoryMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
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
        [$product, $variant, $inventory] = $this->createMidtransOrderReservation($order, 'reserved');

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
        
        $this->assertSame(8, (int) $inventory->fresh()->quantity);
        $this->assertSame(0, (int) $inventory->fresh()->reserved_quantity);
        
        $this->assertDatabaseHas('inventory_reservations', [
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'product_variant_id' => $variant->id,
            'status' => 'committed',
        ]);
        
        $this->assertDatabaseHas('inventory_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'sale',
            'quantity' => -2,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
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

    public function test_midtrans_success_on_canceled_order_requests_only_one_refund(): void
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

        // Notifikasi pertama: pembayaran terlambat berhasil.
        $this->postJson(
            route('payment.midtrans.notification'),
            $payload
        )->assertSuccessful();

        $this->assertSame(
            'canceled',
            $order->fresh()->status
        );

        $this->assertSame(
            'succeeded',
            $payment->fresh()->status
        );

        $idempotencyKey =
            'late-canceled-order-payment-'.$payment->id;

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'status' => Refund::STATUS_REQUESTED,
            'idempotency_key' => $idempotencyKey,
        ]);

        $this->assertSame(
            1,
            Refund::query()
                ->where('idempotency_key', $idempotencyKey)
                ->count()
        );

        // Notifikasi kedua: tidak boleh membuat refund duplikat.
        $this->postJson(
            route('payment.midtrans.notification'),
            $payload
        )->assertSuccessful();

        $this->assertSame(
            'canceled',
            $order->fresh()->status
        );

        $this->assertSame(
            'succeeded',
            $payment->fresh()->status
        );

        $this->assertSame(
            1,
            Refund::query()
                ->where('idempotency_key', $idempotencyKey)
                ->count()
        );
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
    
    private function assertFailedOrExpiredNotificationRestoresStockOnce(
        string $providerStatus,
        string $expectedPaymentStatus
    ): void {
        [$order, $payment] = $this->createPendingMidtransPayment();
    
        $product = Product::factory()->create(['stock' => 8]);
    
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'MIDTRANS-RESTORE-'.strtoupper($providerStatus).'-'.uniqid(),
            'name' => 'Test variant',
            'price' => 50000,
            'is_default' => true,
            'status' => 'active',
        ]);
    
        $inventory = Inventory::create([
            'product_variant_id' => $variant->id,
            'quantity' => 8,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);
    
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'price' => 50000,
            'quantity' => 2,
            'subtotal' => 100000,
        ]);
    
        Config::set('midtrans.server_key', 'test-server-key');
    
        $this->mock(\App\Services\MidtransService::class, function ($mock) use ($order, $providerStatus) {
            $mock->shouldReceive('verifyNotification')
                ->twice()
                ->andReturn(true);
    
            $mock->shouldReceive('getStatus')
                ->twice()
                ->with($order->order_number)
                ->andReturn((object) [
                    'order_id' => $order->order_number,
                    'transaction_status' => $providerStatus,
                    'status_code' => '200',
                    'gross_amount' => '100000.00',
                    'payment_type' => 'bank_transfer',
                    'transaction_id' => 'MIDTRANS-TEST-123',
                ]);
        });
    
        $payload = [
            'order_id' => $order->order_number,
            'transaction_id' => 'MIDTRANS-TEST-123',
            'transaction_status' => $providerStatus,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'payment_type' => 'bank_transfer',
            'signature_key' => hash(
                'sha512',
                $order->order_number.'200'.'100000.00'.'test-server-key'
            ),
        ];
    
        $this->postJson(
            route('payment.midtrans.notification'),
            $payload
        )->assertSuccessful();
    
        $this->assertSame('canceled', $order->fresh()->status);
        $this->assertSame($expectedPaymentStatus, $payment->fresh()->status);
        $this->assertSame(10, (int) $inventory->fresh()->quantity);
        $this->assertSame(10, (int) $product->fresh()->stock);
    
        $movements = InventoryMovement::query()
            ->where('product_variant_id', $variant->id)
            ->where('reference_type', Order::class)
            ->where('reference_id', $order->id)
            ->where('type', 'return');
    
        $this->assertSame(1, (clone $movements)->count());
        $this->assertSame(2, (int) (clone $movements)->sum('quantity'));
    
        // Notifikasi duplikat tidak boleh memulihkan stok sekali lagi.
        $this->postJson(
            route('payment.midtrans.notification'),
            $payload
        )->assertSuccessful();
    
        $this->assertSame(10, (int) $inventory->fresh()->quantity);
        $this->assertSame(10, (int) $product->fresh()->stock);
        $this->assertSame(1, (clone $movements)->count());
        $this->assertSame(2, (int) (clone $movements)->sum('quantity'));
        $this->assertSame('canceled', $order->fresh()->status);
        $this->assertSame($expectedPaymentStatus, $payment->fresh()->status);
    }
    
    public function test_midtrans_failed_notification_restores_stock_only_once(): void
    {
        $this->assertFailedOrExpiredNotificationRestoresStockOnce(
            'deny',
            'failed'
        );
    }
    
    public function test_midtrans_expired_notification_restores_stock_only_once(): void
    {
        $this->assertFailedOrExpiredNotificationRestoresStockOnce(
            'expire',
            'expired'
        );
    }
    
    private function createMidtransOrderReservation(
        Order $order,
        string $reservationStatus
    ): array {
        $isCommitted = $reservationStatus === 'committed';
    
        $product = Product::factory()->create([
            'stock' => $isCommitted ? 8 : 10,
        ]);
    
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'MIDTRANS-CANCEL-'.uniqid(),
            'name' => 'Webhook test variant',
            'price' => 50000,
            'is_default' => true,
            'status' => 'active',
        ]);
    
        $inventory = Inventory::create([
            'product_variant_id' => $variant->id,
            'quantity' => $isCommitted ? 8 : 10,
            'reserved_quantity' => $reservationStatus === 'reserved' ? 2 : 0,
            'reorder_level' => 0,
        ]);
    
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'price' => 50000,
            'quantity' => 2,
            'subtotal' => 100000,
        ]);
    
        DB::table('inventory_reservations')->insert([
            'product_variant_id' => $variant->id,
            'idempotency_key' => 'midtrans-cancel-'.$order->id,
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'quantity' => 2,
            'status' => $reservationStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    
        return [$product, $variant, $inventory];
    }
    
    private function sendMidtransCancellationNotification(
        Order $order,
        string $providerStatus = 'deny'
    ): void {
        Config::set('midtrans.server_key', 'test-server-key');
    
        $this->mock(\App\Services\MidtransService::class, function ($mock) use ($order, $providerStatus) {
            $mock->shouldReceive('verifyNotification')
                ->twice()
                ->andReturn(true);
    
            $mock->shouldReceive('getStatus')
                ->twice()
                ->with($order->order_number)
                ->andReturn((object) [
                    'order_id' => $order->order_number,
                    'transaction_status' => $providerStatus,
                    'status_code' => '200',
                    'gross_amount' => '100000.00',
                    'payment_type' => 'bank_transfer',
                    'transaction_id' => 'MIDTRANS-CANCEL-TEST',
                ]);
        });
    
        $payload = [
            'order_id' => $order->order_number,
            'transaction_id' => 'MIDTRANS-CANCEL-TEST',
            'transaction_status' => $providerStatus,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'payment_type' => 'bank_transfer',
            'signature_key' => hash(
                'sha512',
                $order->order_number.'200'.'100000.00'.'test-server-key'
            ),
        ];
    
        $this->postJson(route('payment.midtrans.notification'), $payload)
            ->assertSuccessful();
    
        // Notifikasi kedua menguji bahwa stok tidak berubah lagi.
        $this->postJson(route('payment.midtrans.notification'), $payload)
            ->assertSuccessful();
    }
    
    public function test_midtrans_cancellation_releases_reserved_stock_without_restoring_quantity(): void
    {
        [$order, $payment] = $this->createPendingMidtransPayment();
        [$product, $variant, $inventory] =
            $this->createMidtransOrderReservation($order, 'reserved');
    
        $this->sendMidtransCancellationNotification($order);
    
        $this->assertSame('canceled', $order->fresh()->status);
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame(10, (int) $inventory->fresh()->quantity);
        $this->assertSame(0, (int) $inventory->fresh()->reserved_quantity);
        $this->assertSame(10, (int) $product->fresh()->stock);
    
        $this->assertDatabaseHas('inventory_reservations', [
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'status' => 'released',
        ]);
    
        $this->assertDatabaseMissing('inventory_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'return',
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);
    }
    
    public function test_midtrans_cancellation_restores_committed_stock_only_once(): void
    {
        [$order, $payment] = $this->createPendingMidtransPayment();
        [$product, $variant, $inventory] =
            $this->createMidtransOrderReservation($order, 'committed');
    
        $this->sendMidtransCancellationNotification($order);
    
        $this->assertSame('canceled', $order->fresh()->status);
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame(10, (int) $inventory->fresh()->quantity);
        $this->assertSame(0, (int) $inventory->fresh()->reserved_quantity);
        $this->assertSame(10, (int) $product->fresh()->stock);
    
        $this->assertDatabaseHas('inventory_reservations', [
            'reference_type' => Order::class,
            'reference_id' => $order->id,
            'status' => 'committed',
        ]);
    
        $this->assertSame(
            1,
            DB::table('inventory_movements')
                ->where('product_variant_id', $variant->id)
                ->where('type', 'return')
                ->where('reference_type', Order::class)
                ->where('reference_id', $order->id)
                ->count()
        );
    }
    
    public function test_midtrans_cancellation_does_not_restore_released_reservation(): void
    {
        [$order, $payment] = $this->createPendingMidtransPayment();
        [$product, $variant, $inventory] =
            $this->createMidtransOrderReservation($order, 'released');
    
        $this->sendMidtransCancellationNotification($order);
    
        $this->assertSame('canceled', $order->fresh()->status);
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame(10, (int) $inventory->fresh()->quantity);
        $this->assertSame(0, (int) $inventory->fresh()->reserved_quantity);
    
        $this->assertDatabaseMissing('inventory_movements', [
            'product_variant_id' => $variant->id,
            'type' => 'return',
            'reference_type' => Order::class,
            'reference_id' => $order->id,
        ]);
    }
    
    private function makeMidtransWebhookPayload(
        Order $order,
        string $transactionStatus
    ): array {
        return [
            'order_id' => $order->order_number,
            'transaction_id' => 'MIDTRANS-RACE-TEST',
            'transaction_status' => $transactionStatus,
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'payment_type' => 'bank_transfer',
            'signature_key' => hash(
                'sha512',
                $order->order_number.'200'.'100000.00'.'test-server-key'
            ),
        ];
    }
    
    public function test_midtrans_settlement_first_cannot_be_overwritten_by_cancel_notification(): void
    {
        [$order, $payment] = $this->createPendingMidtransPayment();
    
        Config::set('midtrans.server_key', 'test-server-key');
    
        $this->mock(\App\Services\MidtransService::class, function ($mock) use ($order) {
            $mock->shouldReceive('verifyNotification')
                ->twice()
                ->andReturn(true);
    
            // Notifikasi kedua berhenti sebelum getStatus karena payment
            // sudah succeeded.
            $mock->shouldReceive('getStatus')
                ->once()
                ->with($order->order_number)
                ->andReturn((object) [
                    'order_id' => $order->order_number,
                    'transaction_status' => 'settlement',
                    'status_code' => '200',
                    'gross_amount' => '100000.00',
                    'payment_type' => 'bank_transfer',
                    'transaction_id' => 'MIDTRANS-RACE-TEST',
                    'fraud_status' => 'accept',
                ]);
        });
    
        $this->postJson(
            route('payment.midtrans.notification'),
            $this->makeMidtransWebhookPayload($order, 'settlement')
        )->assertSuccessful();
    
        $this->assertSame('succeeded', $payment->fresh()->status);
        $this->assertSame('processing', $order->fresh()->status);
    
        // Webhook cancel datang terlambat.
        $this->postJson(
            route('payment.midtrans.notification'),
            $this->makeMidtransWebhookPayload($order, 'cancel')
        )->assertSuccessful();
    
        $this->assertSame('succeeded', $payment->fresh()->status);
        $this->assertSame('processing', $order->fresh()->status);
    
        $this->assertSame(
            0,
            Refund::query()
                ->where('order_id', $order->id)
                ->count()
        );
    }
    
    public function test_midtrans_late_settlement_after_cancel_keeps_order_canceled_and_requests_one_refund(): void
    {
        [$order, $payment] = $this->createPendingMidtransPayment();
    
        Config::set('midtrans.server_key', 'test-server-key');
    
        $this->mock(\App\Services\MidtransService::class, function ($mock) use ($order) {
            $mock->shouldReceive('verifyNotification')
                ->times(3)
                ->andReturn(true);
    
            $mock->shouldReceive('getStatus')
                ->twice()
                ->with($order->order_number)
                ->andReturn(
                    (object) [
                        'order_id' => $order->order_number,
                        'transaction_status' => 'cancel',
                        'status_code' => '200',
                        'gross_amount' => '100000.00',
                        'payment_type' => 'bank_transfer',
                        'transaction_id' => 'MIDTRANS-RACE-TEST',
                    ],
                    (object) [
                        'order_id' => $order->order_number,
                        'transaction_status' => 'settlement',
                        'status_code' => '200',
                        'gross_amount' => '100000.00',
                        'payment_type' => 'bank_transfer',
                        'transaction_id' => 'MIDTRANS-RACE-TEST',
                        'fraud_status' => 'accept',
                    ]
                );
        });
    
        // Tahap pertama: pembatalan diterima.
        $this->postJson(
            route('payment.midtrans.notification'),
            $this->makeMidtransWebhookPayload($order, 'cancel')
        )->assertSuccessful();
    
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('canceled', $order->fresh()->status);
    
        // Tahap kedua: gateway ternyata mengonfirmasi settlement.
        $this->postJson(
            route('payment.midtrans.notification'),
            $this->makeMidtransWebhookPayload($order, 'settlement')
        )->assertSuccessful();
    
        $this->assertSame('succeeded', $payment->fresh()->status);
    
        // Order tidak boleh diaktifkan kembali.
        $this->assertSame('canceled', $order->fresh()->status);
    
        $idempotencyKey = 'late-canceled-order-payment-'.$payment->id;
    
        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'status' => Refund::STATUS_REQUESTED,
            'idempotency_key' => $idempotencyKey,
        ]);
    
        $this->assertSame( 1, Refund::query()->where('idempotency_key', $idempotencyKey)->count());
    
        // Settlement duplikat tidak boleh membuat refund kedua.
        $this->postJson(
            route('payment.midtrans.notification'),
            $this->makeMidtransWebhookPayload($order, 'settlement')
        )->assertSuccessful();
    
        $this->assertSame('succeeded', $payment->fresh()->status);
        $this->assertSame('canceled', $order->fresh()->status);
    
        $this->assertSame(1,Refund::query()->where('idempotency_key', $idempotencyKey)->count());
    }
    
    public function test_midtrans_confirm_late_settlement_requests_only_one_refund(): void
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
            'status' => 'failed',
            'gross_amount' => 100000,
            'transaction_id' => 'MIDTRANS-CONFIRM-LATE',
        ]);
    
        $this->mock(
            \App\Services\MidtransService::class,
            function ($mock) use ($order) {
                $mock->shouldReceive('getStatus')
                    ->once()
                    ->with($order->order_number)
                    ->andReturn((object) [
                        'order_id' => $order->order_number,
                        'transaction_status' => 'settlement',
                        'status_code' => '200',
                        'gross_amount' => '100000.00',
                        'payment_type' => 'bank_transfer',
                        'transaction_id' => 'MIDTRANS-CONFIRM-LATE',
                        'fraud_status' => 'accept',
                    ]);
            }
        );
    
        $payload = [
            'transaction_id' => 'MIDTRANS-CONFIRM-LATE',
        ];
    
        $firstResponse = $this->postJson(
            route('payment.midtrans.confirm', $order),
            $payload
        );
    
        $firstResponse->assertSuccessful();
    
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'succeeded',
        ]);
    
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'canceled',
        ]);
    
        $idempotencyKey =
            'late-canceled-order-payment-'.$payment->id;
    
        $this->assertDatabaseHas('refunds', [
            'payment_id' => $payment->id,
            'idempotency_key' => $idempotencyKey,
            'status' => Refund::STATUS_REQUESTED,
        ]);
    
        // Confirm kedua masuk melalui jalur payment yang sudah succeeded.
        $secondResponse = $this->postJson(
            route('payment.midtrans.confirm', $order),
            $payload
        );
    
        $secondResponse->assertSuccessful();
    
        $this->assertDatabaseCount('refunds', 1);
    }
    
    public function test_midtrans_confirm_ignores_unknown_current_payment_status(): void
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
            'status' => 'manual_review',
            'gross_amount' => 100000,
            'transaction_id' => 'MIDTRANS-UNKNOWN-STATUS',
        ]);
    
        $this->mock(
            \App\Services\MidtransService::class,
            function ($mock) use ($order) {
                $mock->shouldReceive('getStatus')
                    ->once()
                    ->with($order->order_number)
                    ->andReturn((object) [
                        'order_id' => $order->order_number,
                        'transaction_status' => 'settlement',
                        'status_code' => '200',
                        'gross_amount' => '100000.00',
                        'payment_type' => 'bank_transfer',
                        'transaction_id' => 'MIDTRANS-UNKNOWN-STATUS',
                        'fraud_status' => 'accept',
                    ]);
            }
        );
    
        $response = $this->postJson(
            route('payment.midtrans.confirm', $order),
            [
                'transaction_id' => 'MIDTRANS-UNKNOWN-STATUS',
            ]
        );
    
        $response->assertStatus(422);
    
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'manual_review',
        ]);
    
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'pending',
        ]);
    
        $this->assertDatabaseCount('refunds', 0);
    }
}
