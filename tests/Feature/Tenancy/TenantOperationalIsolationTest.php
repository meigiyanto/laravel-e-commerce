<?php

namespace Tests\Feature\Tenancy;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantOperationalIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['operational.mode' => 'multi_tenant']);
        app(TenantContext::class)->clear();
    }

    private function createTenantStore(string $name): array
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $store = Store::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => str($name)->slug().'-'.$tenant->id,
            'status' => 'active',
        ]);

        $domain = 'operational-'.$tenant->id.'.example.test';

        StoreDomain::updateOrCreate(
            ['domain' => $domain],
            [
                'store_id' => $store->id,
                'verification_status' => 'verified',
                'is_primary' => true,
                'verified_at' => now(),
            ]
        );

        return [$tenant,
            $store,
            $domain];
    }

    private function tenantUrl(
        string $domain,
        string $routeName,
        mixed $parameters = []
    ): string {
        return 'http://'.$domain.route(
            $routeName,
            $parameters,
            false
        );
    }

    private function createAdmin(Tenant $tenant): User
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        TenantMembership::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'role' => TenantMembership::ROLE_ADMIN,
            'status' => 'active',
            'accepted_at' => now(),
        ]);

        return $admin;
    }

    public function test_customer_cannot_update_or_remove_another_tenants_cart_item(): void
    {
        [$tenantA,,
            $domainA] = $this->createTenantStore('Store A');
        [$tenantB] = $this->createTenantStore('Store B');

        $user = User::factory()->create();

        $productB = app(TenantContext::class)->run(
            $tenantB,
            fn () => Product::factory()->create([
                'stock' => 10,
            ])
        );

        // User hanya memiliki cart di tenant B.
        $cartB = Cart::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $user->id,
        ]);

        $itemB = CartItem::create([
            'cart_id' => $cartB->id,
            'product_id' => $productB->id,
            'quantity' => 2,
        ]);

        $this->actingAs($user);

        $this->patchJson(
            $this->tenantUrl(
                $domainA,
                'cart.items.update',
                $itemB->id
            ),
            ['quantity' => 5]
        )->assertNotFound();

        $this->deleteJson(
            $this->tenantUrl(
                $domainA,
                'cart.items.destroy',
                $itemB->id
            )
        )->assertNotFound();

        $this->assertDatabaseHas('cart_items', [
            'id' => $itemB->id,
            'cart_id' => $cartB->id,
            'quantity' => 2,
        ]);
    }

    public function test_customer_cannot_checkout_another_tenants_cart(): void
    {
        [$tenantA,,
            $domainA] = $this->createTenantStore('Store A');
        [$tenantB] = $this->createTenantStore('Store B');

        $user = User::factory()->create();

        $productB = app(TenantContext::class)->run(
            $tenantB,
            fn () => Product::factory()->create([
                'stock' => 10,
            ])
        );

        $cartB = Cart::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $user->id,
        ]);

        $itemB = CartItem::create([
            'cart_id' => $cartB->id,
            'product_id' => $productB->id,
            'quantity' => 2,
        ]);

        $ordersBefore = Order::count();
        $paymentsBefore = Payment::count();

        $response = $this->actingAs($user)->post(
            $this->tenantUrl($domainA, 'checkout.store'),
            [
                'customer_name' => 'Test Customer',
                'phone' => '08123456789',
                'shipping_address' => 'Test Address',
                'payment_method' => 'cod',
            ]
        );

        $response->assertNotFound();

        $this->assertSame($ordersBefore, Order::count());
        $this->assertSame($paymentsBefore, Payment::count());

        $this->assertDatabaseHas('cart_items', [
            'id' => $itemB->id,
            'cart_id' => $cartB->id,
            'quantity' => 2,
        ]);
    }

    public function test_customer_cannot_open_another_tenants_order(): void
    {
        [,,
            $domainA] = $this->createTenantStore('Store A');
        [$tenantB] = $this->createTenantStore('Store B');

        $user = User::factory()->create();

        $orderB = app(TenantContext::class)->run(
            $tenantB,
            fn () => Order::factory()->create([
                'tenant_id' => $tenantB->id,
                'user_id' => $user->id,
                'status' => 'pending',
            ])
        );

        $this->actingAs($user)->get(
            $this->tenantUrl(
                $domainA,
                'orders.show',
                $orderB->id
            )
        )->assertNotFound();
    }

    public function test_admin_cannot_change_another_tenants_order(): void
    {
        [$tenantA,,
            $domainA] = $this->createTenantStore('Store A');
        [$tenantB] = $this->createTenantStore('Store B');

        $adminA = $this->createAdmin($tenantA);

        $userB = User::factory()->create();

        $orderB = app(TenantContext::class)->run(
            $tenantB,
            fn () => Order::factory()->create([
                'tenant_id' => $tenantB->id,
                'user_id' => $userB->id,
                'status' => 'pending',
            ])
        );

        $this->actingAs($adminA)->patch(
            $this->tenantUrl(
                $domainA,
                'admin.orders.status',
                $orderB->id
            ),
            ['status' => 'canceled']
        )->assertNotFound();

        $this->assertSame(
            'pending',
            $orderB->fresh()->status
        );
    }

    public function test_admin_cannot_update_or_adjust_another_tenants_inventory(): void
    {
        [$tenantA,,
            $domainA] = $this->createTenantStore('Store A');
        [$tenantB] = $this->createTenantStore('Store B');

        $adminA = $this->createAdmin($tenantA);

        $productB = app(TenantContext::class)->run(
            $tenantB,
            fn () => Product::factory()->create([
                'stock' => 10,
            ])
        );

        $this->actingAs($adminA)->patch(
            $this->tenantUrl(
                $domainA,
                'admin.inventory.update-stock',
                $productB->id
            ),
            ['stock' => 50]
        )->assertNotFound();

        $this->actingAs($adminA)->post(
            $this->tenantUrl(
                $domainA,
                'admin.inventory.adjust-stock',
                $productB->id
            ),
            [
                'quantity' => 5,
                'type' => 'add',
            ]
        )->assertNotFound();

        $this->assertSame(
            10,
            (int) $productB->fresh()->stock
        );
    }

    public function test_customer_on_tenant_a_cannot_confirm_tenant_b_payment_by_order_id(): void
    {
        [,,
            $domainA] = $this->createTenantStore('Store A');
        [$tenantB] = $this->createTenantStore('Store B');

        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $fixtures = app(TenantContext::class)->run($tenantB, function () use ($tenantB, $user) {
            $stripeOrder = Order::factory()->create([
                'tenant_id' => $tenantB->id,
                'user_id' => $user->id,
                'status' => 'pending',
            ]);

            $stripePayment = Payment::factory()->create([
                'order_id' => $stripeOrder->id,
                'provider' => 'stripe',
                'status' => 'pending',
                'stripe_payment_intent_id' => 'pi_tenant_b_test',
            ]);

            $midtransOrder = Order::factory()->create([
                'tenant_id' => $tenantB->id,
                'user_id' => $user->id,
                'status' => 'pending',
            ]);

            $midtransPayment = Payment::factory()->create([
                'order_id' => $midtransOrder->id,
                'provider' => 'midtrans',
                'status' => 'pending',
                'transaction_id' => 'midtrans-tenant-b-test',
            ]);

            return compact(
                'stripeOrder',
                'stripePayment',
                'midtransOrder',
                'midtransPayment'
            );
        });

        $this->actingAs($user)->postJson(
            $this->tenantUrl(
                $domainA,
                'payment.confirm',
                $fixtures['stripeOrder']->id
            ),
            [
                'payment_intent_id' => 'pi_tenant_b_test',
                'payment_id' => $fixtures['stripePayment']->id,
            ]
        )->assertNotFound();

        $this->actingAs($user)->postJson(
            $this->tenantUrl(
                $domainA,
                'payment.midtrans.confirm',
                $fixtures['midtransOrder']->id
            ),
            [
                'transaction_id' => 'midtrans-tenant-b-test',
                'payment_id' => $fixtures['midtransPayment']->id,
            ]
        )->assertNotFound();

        $this->assertDatabaseHas('orders', [
            'id' => $fixtures['stripeOrder']->id,
            'tenant_id' => $tenantB->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $fixtures['stripePayment']->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $fixtures['midtransOrder']->id,
            'tenant_id' => $tenantB->id,
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('payments', [
            'id' => $fixtures['midtransPayment']->id,
            'status' => 'pending',
        ]);
    }
}
