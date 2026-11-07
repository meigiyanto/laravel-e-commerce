<?php

namespace Tests\Feature\Refund;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Support\Tenancy\TenantContext;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use RuntimeException;

class ControllerTest extends TestCase
{
    use RefreshDatabase;
    
    private function createTenantDomain(Order $order): string
    {
        $tenantId = DB::table('order_items')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('order_items.order_id', $order->id)
            ->value('products.tenant_id');
    
        if (! $tenantId) {
            throw new \LogicException(
                "Tenant pemilik order {$order->id} tidak dapat ditemukan melalui order items."
            );
        }
    
        $tenant = Tenant::query()->findOrFail($tenantId);
    
        $store = Store::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => 'active',
            ]
        );
    
        $domain = 'refund-test-'.$tenant->id.'.example.test';
    
        StoreDomain::updateOrCreate(
            ['domain' => $domain],
            [
                'store_id' => $store->id,
                'verification_status' => 'verified',
                'is_primary' => true,
            ]
        );
    
        return $domain;
    }
    
    private function tenantUrl(
        Order $order,
        string $routeName,
        mixed $parameters = []
    ): string {
        $domain = $this->createTenantDomain($order);
    
        return 'http://'.$domain.route($routeName, $parameters, false);
    }
    
    private function createOrderFixture(User $user, array $attributes = []): Order
    {
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->set($tenant);
    
        $category = Category::create([
            'tenant_id' => $tenant->id,
            'name' => 'Category '.Str::uuid(),
            'slug' => 'category-'.Str::uuid(),
        ]);
    
        $subCategory = SubCategory::create([
            'category_id' => $category->id,
            'name' => 'Subcategory '.Str::uuid(),
            'slug' => 'subcategory-'.Str::uuid(),
        ]);
    
        $product = Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => 'Product '.Str::uuid(),
            'slug' => 'product-'.Str::uuid(),
            'description' => 'Refund test product',
            'price' => 150000,
            'stock' => 10,
        ]);
    
        $order = Order::factory()->create(array_merge([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
        ], $attributes));
    
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'price' => 150000,
            'quantity' => 1,
            'subtotal' => 150000,
        ]);
    
        return $order;
    }

    public function test_guest_cannot_request_refund(): void
    {
        $user = User::factory()->create();
        
        $order = $this->createOrderFixture($user, [
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $response = $this->post(
            $this->tenantUrl($order, 'orders.refund.store', $order),
            [
                'amount' => 50000,
            ]
        );

        $response->assertRedirect(
            $this->tenantUrl($order, 'login')
        );

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_customer_can_request_refund_for_own_order(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrderFixture($user, [
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

        $response = $this
            ->actingAs($user)
            ->post(
                $this->tenantUrl($order, 'orders.refund.store', $order),
                [
                    'amount' => 50000,
                    'reason' => 'Produk rusak',
                ]
            );

        $response
            ->assertRedirect(
                $this->tenantUrl($order, 'orders.show', $order)
            )
            ->assertSessionHas(
                'success',
                'Permintaan refund berhasil diajukan dan menunggu proses admin.'
            );

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'payment_id' => $order->payment->id,
            'amount' => 50000,
            'reason' => 'Produk rusak',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
        ]);
    }

    public function test_customer_cannot_request_refund_for_another_customer_order(): void
    {
        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $order = $this->createOrderFixture($owner, [
            'user_id' => $owner->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $response = $this
            ->actingAs($otherUser)
            ->post(
                $this->tenantUrl($order, 'orders.refund.store', $order),
                [
                    'amount' => 50000,
                    'reason' => 'Refund',
                ]
            );

        $response->assertForbidden();

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_customer_cannot_request_refund_without_payment(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrderFixture($user, [
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                $this->tenantUrl($order, 'orders.refund.store', $order),
                [
                    'amount' => 50000,
                    'reason' => 'Tidak sesuai',
                ]
            );

        $response->assertSessionHasErrors([
            'payment',
        ]);

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_customer_cannot_request_refund_when_payment_is_not_succeeded(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrderFixture($user, [
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'pending',
            'gross_amount' => 150000,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                $this->tenantUrl($order, 'orders.refund.store', $order),
                [
                    'amount' => 50000,
                    'reason' => 'Payment belum selesai',
                ]
            );

        $response->assertSessionHasErrors([
            'payment',
        ]);

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_customer_cannot_request_refund_above_refundable_amount(): void
    {
        $user = User::factory()->create();

        $order = $this->createOrderFixture($user, [
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

        $response = $this
            ->actingAs($user)
            ->post(
                $this->tenantUrl($order, 'orders.refund.store', $order),
                [
                    'amount' => 200000,
                    'reason' => 'Terlalu besar',
                ]
            );

        $response->assertSessionHasErrors([
            'amount',
        ]);

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_customer_cannot_request_zero_refund(): void
    {
        $user = User::factory()->create();
        
        $order = $this->createOrderFixture($user, [
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

        $response = $this
            ->actingAs($user)
            ->post(
                $this->tenantUrl($order, 'orders.refund.store', $order),
                [
                    'amount' => 0,
                ]
            );

        $response->assertSessionHasErrors([
            'amount',
        ]);

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_customer_cannot_request_negative_refund(): void
    {
        $user = User::factory()->create();
        
        $order = $this->createOrderFixture($user, [
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

        $response = $this
            ->actingAs($user)
            ->post(
                $this->tenantUrl($order, 'orders.refund.store', $order),
                [
                    'amount' => -10000,
                ]
            );

        $response->assertSessionHasErrors([
            'amount',
        ]);

        $this->assertDatabaseCount('refunds', 0);
    }

    public function test_customer_can_request_partial_refund(): void
    {
        $user = User::factory()->create();
        
        $order = $this->createOrderFixture($user, [
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'midtrans',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(
                $this->tenantUrl($order, 'orders.refund.store', $order),
                [
                    'amount' => 25000,
                    'reason' => 'Satu produk rusak',
                ]
            );

        $response->assertRedirect(
            $this->tenantUrl($order, 'orders.show', $order)
        );

        $this->assertDatabaseHas('refunds', [
            'order_id' => $order->id,
            'amount' => 25000,
            'provider' => 'midtrans',
            'status' => Refund::STATUS_REQUESTED,
        ]);
    }

    public function test_customer_refund_controller_delegates_to_refund_service(): void
    {
        $user = User::factory()->create();
        
        $order = $this->createOrderFixture($user, [
            'user_id' => $user->id,
            'status' => 'completed',
        ]);

        $refund = new Refund([
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Produk rusak',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
        ]);

        $this->mock(
            RefundService::class,
            function ($mock) use ($order, $refund) {
                $mock
                    ->shouldReceive('request')
                    ->once()
                    ->withArgs(function (
                        Order $receivedOrder,
                        int|float|string $amount,
                        ?string $reason
                    ) use ($order) {
                        return $receivedOrder->is($order)
                            && (float) $amount === 50000.0
                            && $reason === 'Produk rusak';
                    })
                    ->andReturn($refund);
            }
        );

        $response = $this
            ->actingAs($user)
            ->post(
                $this->tenantUrl($order, 'orders.refund.store', $order),
                [
                    'amount' => 50000,
                    'reason' => 'Produk rusak',
                ]
            );

        $response
            ->assertRedirect($this->tenantUrl($order, 'orders.show', $order))
            ->assertSessionHas(
                'success',
                'Permintaan refund berhasil diajukan dan menunggu proses admin.'
            );
    }
}
