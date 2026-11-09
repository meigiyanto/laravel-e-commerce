<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleStoreAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['operational.mode' => 'single_store']);
    }

    public function test_admin_can_login_without_tenant_membership(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_staff_can_login_without_tenant_membership(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $response = $this->post('/admin/login', [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($staff);
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_staff_without_tenant_membership_can_access_operational_dashboard(): void
    {
        $staff = User::factory()->create(['role' => User::ROLE_STAFF]);

        $this->actingAs($staff)
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_customer_cannot_login_through_admin_login_in_single_store_mode(): void
    {
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_platform_admin_cannot_login_through_admin_login_in_single_store_mode(): void
    {
        $platformAdmin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => $platformAdmin->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('email');
    }
}