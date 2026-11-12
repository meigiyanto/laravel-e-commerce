<?php

namespace Tests\Unit\Policies;

use App\Models\Product;
use App\Models\User;
use App\Policies\InventoryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_store_admin_can_view_inventory(): void
    {
        config(['operational.mode' => 'single_store']);

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->assertTrue(
            app(InventoryPolicy::class)->viewAny($admin)
        );
    }

    public function test_single_store_staff_can_view_inventory(): void
    {
        config(['operational.mode' => 'single_store']);

        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $this->assertTrue(
            app(InventoryPolicy::class)->viewAny($staff)
        );
    }

    public function test_single_store_customer_cannot_view_inventory(): void
    {
        config(['operational.mode' => 'single_store']);

        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $this->assertFalse(
            app(InventoryPolicy::class)->viewAny($customer)
        );
    }

    public function test_single_store_staff_cannot_manage_inventory(): void
    {
        config(['operational.mode' => 'single_store']);

        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $product = new Product();
        $product->tenant_id = null;

        $this->assertFalse(
            app(InventoryPolicy::class)->updateStock($staff, $product)
        );
    }
}
