<?php

namespace Tests\Feature\Tenancy;

use App\Models\Inventory;
use App\Models\InventoryMovement;
use App\Models\InventoryReservation;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use App\Services\VariantInventoryService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantInventoryServiceIsolationTest extends TestCase
{
    use RefreshDatabase;
    
    private function inTenant(Tenant $tenant, callable $callback): mixed
    {
        return app(TenantContext::class)->run($tenant, $callback);
    }

    private function createFixture(Tenant $tenant): array
    {
        return app(TenantContext::class)->run(
            $tenant,
            function () use ($tenant) {
                $product = Product::factory()->create([
                    'stock' => 10,
                ]);

                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    'sku' => 'ISO-'.$tenant->id.'-'.uniqid(),
                    'name' => 'Isolation Variant',
                    'price' => 100000,
                    'is_default' => true,
                    'status' => 'active',
                ]);

                $inventory = Inventory::create([
                    'product_variant_id' => $variant->id,
                    'quantity' => 10,
                    'reserved_quantity' => 0,
                    'reorder_level' => 0,
                ]);

                $order = Order::factory()->create([
                    'tenant_id' => $tenant->id,
                    'status' => 'pending',
                ]);

                return compact(
                    'product',
                    'variant',
                    'inventory',
                    'order'
                );
            }
        );
    }

    private function assertForeignRecordIsHidden(
        callable $callback
    ): void {
        try {
            $callback();
            $this->fail(
                'Record milik tenant lain seharusnya tidak ditemukan.'
            );
        } catch (ModelNotFoundException) {
            $this->assertTrue(true);
        }
    }

    protected function setUp(): void
    {
        parent::setUp();

        config(['operational.mode' => 'multi_tenant']);
        app(TenantContext::class)->clear();
    }

    public function test_sync_legacy_stock_cannot_update_another_tenants_product(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $fixtureA = $this->createFixture($tenantA);
        $fixtureB = $this->createFixture($tenantB);

        app(TenantContext::class)->set($tenantA);

        $this->assertForeignRecordIsHidden(
            fn () => app(VariantInventoryService::class)
                ->syncLegacyStock($fixtureB['product'])
        );
        
        $this->inTenant($tenantB, function () use ($fixtureB) {
            $this->assertSame(10, (int) $fixtureB['product']->fresh()->stock);
            $this->assertSame(10, (int) $fixtureB['inventory']->fresh()->quantity);
        });
        
        /*
        $this->assertSame(
            10,
            (int) $fixtureB['product']->fresh()->stock
        );

        $this->assertSame(
            10,
            (int) $fixtureB['inventory']->fresh()->quantity
        );
        */
    }

    public function test_reserve_cannot_use_another_tenants_variant_or_order(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $this->createFixture($tenantA);
        $fixtureB = $this->createFixture($tenantB);

        app(TenantContext::class)->set($tenantA);

        $this->assertForeignRecordIsHidden(
            fn () => app(VariantInventoryService::class)->reserve(
                $fixtureB['variant'],
                2,
                'foreign-reserve-'.$fixtureB['order']->id,
                Order::class,
                (int) $fixtureB['order']->id
            )
        );
        
        $this->inTenant($tenantB, function () use ($fixtureB) {
            $this->assertSame(10, (int) $fixtureB['inventory']->fresh()->quantity);
            $this->assertSame(0, (int) $fixtureB['inventory']->fresh()->reserved_quantity);
        });

        /*
        $this->assertSame(
            10,
            (int) $fixtureB['inventory']->fresh()->quantity
        );

        $this->assertSame(
            0,
            (int) $fixtureB['inventory']->fresh()->reserved_quantity
        );
        */

        $this->assertDatabaseMissing('inventory_reservations', [
            'idempotency_key' => 'foreign-reserve-'.$fixtureB['order']->id,
        ]);
    }

    public function test_deduct_and_restore_cannot_mutate_another_tenants_order_inventory(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $this->createFixture($tenantA);
        $fixtureB = $this->createFixture($tenantB);

        app(TenantContext::class)->set($tenantA);

        $service = app(VariantInventoryService::class);

        $this->assertForeignRecordIsHidden(
            fn () => $service->deductForOrder(
                $fixtureB['product'],
                $fixtureB['variant'],
                $fixtureB['inventory'],
                2,
                $fixtureB['order']
            )
        );

        $this->assertForeignRecordIsHidden(
            fn () => $service->restoreForOrder(
                $fixtureB['product'],
                (int) $fixtureB['variant']->id,
                2,
                $fixtureB['order']
            )
        );
        
        $this->inTenant($tenantB, function () use ($fixtureB) {
            $this->assertSame(10, (int) $fixtureB['inventory']->fresh()->quantity);
        });
        
        /*
        $this->assertSame(
            10,
            (int) $fixtureB['inventory']->fresh()->quantity
        );
        */
        
        $this->assertSame(
            0,
            InventoryMovement::query()
                ->where('product_variant_id', $fixtureB['variant']->id)
                ->count()
        );
    }

    public function test_foreign_reservation_cannot_be_committed_or_released(): void
    {
        $tenantA = Tenant::factory()->create();
        $tenantB = Tenant::factory()->create();

        $this->createFixture($tenantA);
        $fixtureB = $this->createFixture($tenantB);

        $key = 'foreign-reservation-'.$fixtureB['order']->id;

        app(TenantContext::class)->run(
            $tenantB,
            fn () => app(VariantInventoryService::class)->reserve(
                $fixtureB['variant'],
                2,
                $key,
                Order::class,
                (int) $fixtureB['order']->id
            )
        );

        app(TenantContext::class)->set($tenantA);

        $service = app(VariantInventoryService::class);

        $this->assertForeignRecordIsHidden(
            fn () => $service->commitReservation($key)
        );

        $this->assertForeignRecordIsHidden(
            fn () => $service->releaseReservation($key)
        );

        $this->assertForeignRecordIsHidden(
            fn () => $service->commitReservationsForOrder(
                $fixtureB['order']
            )
        );

        $this->assertForeignRecordIsHidden(
            fn () => $service->releaseReservationsForOrder(
                $fixtureB['order']
            )
        );

        $reservation = InventoryReservation::withoutGlobalScope('tenant')
            ->where('idempotency_key', $key)
            ->firstOrFail();

        $this->assertSame(
            InventoryReservation::STATUS_RESERVED,
            $reservation->status
        );
        
        $this->inTenant($tenantB, function () use ($fixtureB) {
            $this->assertSame(10, (int) $fixtureB['inventory']->fresh()->quantity);
            $this->assertSame(2, (int) $fixtureB['inventory']->fresh()->reserved_quantity);
        });
        
        /*
        $this->assertSame(
            10,
            (int) $fixtureB['inventory']->fresh()->quantity
        );

        $this->assertSame(
            2,
            (int) $fixtureB['inventory']->fresh()->reserved_quantity
        );
        */
    }
}
