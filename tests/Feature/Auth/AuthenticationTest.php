<?php

namespace Tests\Feature\Auth;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    
    protected function setUp(): void
    {
        parent::setUp();
    
        config(['operational.mode' => 'multi_tenant']);
    
        $this->tenant = Tenant::factory()->create([
            'name' => 'Default Tenant',
            'slug' => 'default',
            'status' => 'active',
        ]);
    }

    private function createMembership(
        User $user,
        string $role,
        string $status = 'active'
    ): TenantMembership {
        return TenantMembership::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $user->id,
            'role' => $role,
            'status' => $status,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Storefront Authentication
    |--------------------------------------------------------------------------
    */

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_customers_can_authenticate_using_the_login_screen(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $response = $this->post('/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($customer);

        $response->assertRedirect(
            route('account.index', absolute: false)
        );
    }

    public function test_users_cannot_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_platform_admin_cannot_login_through_storefront_login(): void
    {
        $platformAdmin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => $platformAdmin->email,
            'password' => 'password',
        ]);

        $this->assertGuest();

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_staff_without_tenant_membership_can_login_to_storefront(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $response = $this->post('/login', [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($staff);

        $response->assertRedirect(
            route('account.index', absolute: false)
        );
    }

    public function test_storefront_users_can_logout(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $response = $this->actingAs($customer)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('login'));
    }

    /*
    |--------------------------------------------------------------------------
    | Tenant Admin Authentication
    |--------------------------------------------------------------------------
    */

    public function test_admin_login_screen_can_be_rendered(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_admin_with_active_membership_can_login(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->createMembership(
            $admin,
            TenantMembership::ROLE_ADMIN
        );

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);

        $response->assertRedirect(
            route('admin.dashboard', absolute: false)
        );
    }

    public function test_admin_without_active_membership_cannot_login(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->createMembership(
            $admin,
            TenantMembership::ROLE_ADMIN,
            'inactive'
        );

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertGuest();

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_customer_cannot_login_through_admin_login_screen(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $this->assertGuest();

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_platform_admin_cannot_login_through_admin_login_screen(): void
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

    public function test_guest_is_redirected_to_admin_login_when_accessing_admin_dashboard(): void
    {
        $this->get('/admin/dashboard')
            ->assertRedirect(route('admin.login'));
    }

    public function test_customer_is_forbidden_from_accessing_admin_dashboard(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $this->actingAs($customer)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    /*
    |--------------------------------------------------------------------------
    | Tenant Staff Authorization
    |--------------------------------------------------------------------------
    */

    public function test_staff_with_active_membership_can_access_operational_dashboard(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $this->createMembership(
            $staff,
            TenantMembership::ROLE_STAFF
        );

        $this->actingAs($staff)
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_staff_without_active_membership_cannot_access_operational_dashboard(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $this->createMembership(
            $staff,
            TenantMembership::ROLE_STAFF,
            'inactive'
        );

        $this->actingAs($staff)
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_staff_cannot_access_user_management_or_admin_profile(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $this->createMembership(
            $staff,
            TenantMembership::ROLE_STAFF
        );

        $this->actingAs($staff)
            ->get('/admin/users')
            ->assertForbidden();

        $this->actingAs($staff)
            ->get('/admin/profile')
            ->assertForbidden();
    }

    public function test_staff_with_active_membership_can_login_through_admin_login(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $this->createMembership(
            $staff,
            TenantMembership::ROLE_STAFF
        );

        $response = $this->post('/admin/login', [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($staff);

        $response->assertRedirect(
            route('admin.dashboard', absolute: false)
        );
    }

    public function test_staff_without_membership_is_rejected_from_admin_login(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $response = $this->from('/admin/login')->post('/admin/login', [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $this->assertGuest();

        $response->assertRedirect('/admin/login');
        $response->assertSessionHasErrors('email');
    }

    /*
    |--------------------------------------------------------------------------
    | Tenant Admin Logout
    |--------------------------------------------------------------------------
    */

    public function test_admin_logout_clears_authentication_and_redirects_to_admin_login(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $this->createMembership(
            $admin,
            TenantMembership::ROLE_ADMIN
        );

        $response = $this->actingAs($admin)->post('/admin/logout');

        $this->assertGuest();

        $response->assertRedirect(route('admin.login'));
    }

    public function test_staff_logout_redirects_to_admin_login(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $this->createMembership(
            $staff,
            TenantMembership::ROLE_STAFF
        );

        $response = $this->actingAs($staff)->post('/admin/logout');

        $this->assertGuest();

        $response->assertRedirect(route('admin.login'));
    }

    /*
    |--------------------------------------------------------------------------
    | Platform Authentication
    |--------------------------------------------------------------------------
    */

    public function test_platform_login_screen_can_be_rendered(): void
    {
        $this->get('/platform/login')->assertOk();
    }

    public function test_platform_admin_can_login_through_platform_login_screen(): void
    {
        $platformAdmin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $response = $this->post('/platform/login', [
            'email' => $platformAdmin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($platformAdmin);

        $response->assertRedirect(
            route('platform.dashboard', absolute: false)
        );
    }

    public function test_regular_user_cannot_login_through_platform_login_screen(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $response = $this->from('/platform/login')->post('/platform/login', [
            'email' => $customer->email,
            'password' => 'password',
        ]);

        $this->assertGuest();

        $response->assertRedirect('/platform/login');
        $response->assertSessionHasErrors('email');
    }

    public function test_platform_admin_can_access_platform_dashboard(): void
    {
        $platformAdmin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $this->actingAs($platformAdmin)
            ->get('/platform/dashboard')
            ->assertOk();
    }

    public function test_regular_user_cannot_access_platform_dashboard(): void
    {
        $customer = User::factory()->create([
            'role' => User::ROLE_CUSTOMER,
        ]);

        $this->actingAs($customer)
            ->get('/platform/dashboard')
            ->assertForbidden();
    }

    public function test_platform_admin_logout_redirects_to_platform_login(): void
    {
        $platformAdmin = User::factory()->create([
            'role' => User::ROLE_PLATFORM_ADMIN,
        ]);

        $response = $this->actingAs($platformAdmin)->post('/platform/logout');

        $this->assertGuest();

        $response->assertRedirect(route('platform.login'));
    }
}
