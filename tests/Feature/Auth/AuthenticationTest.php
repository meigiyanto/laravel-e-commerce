<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

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
        $response->assertRedirect(route('account.index', absolute: false));
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $customer = User::factory()->create();

        $response = $this->actingAs($customer)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_admin_login_screen_can_be_rendered(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_admin_can_login_through_admin_login_screen(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);
        $response->assertRedirect(route('admin.dashboard', absolute: false));
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

    public function test_staff_can_access_the_operational_dashboard(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $this->actingAs($staff)
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_staff_cannot_access_user_management_or_admin_profile(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $this->actingAs($staff)
            ->get('/admin/users')
            ->assertForbidden();

        $this->actingAs($staff)
            ->get('/admin/profile')
            ->assertForbidden();
    }

    public function test_staff_login_redirects_to_the_operational_dashboard(): void
    {
        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $response = $this->post('/login', [
            'email' => $staff->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($staff);
        $response->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_admin_logout_clears_authentication_and_redirects_to_admin_login(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('admin.login'));
    }
}
