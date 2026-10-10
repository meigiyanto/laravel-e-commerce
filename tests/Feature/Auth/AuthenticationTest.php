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
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create();

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticated();
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
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_admin_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/admin/login');

        $response->assertOk();
    }

    public function test_admin_can_login_through_admin_login_screen(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->post('/admin/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin);

        $response->assertRedirect(
            route('admin.dashboard', absolute: false)
        );
    }

    public function test_customer_cannot_login_through_admin_login_screen(): void
    {
        $customer = User::factory()->create([
            'role' => 'user',
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
        $response = $this->get('/admin/dashboard');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_customer_is_forbidden_from_accessing_admin_dashboard(): void
    {
        $customer = User::factory()->create([
            'role' => 'user',
        ]);

        $response = $this->actingAs($customer)
            ->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_admin_logout_clears_authentication_and_redirects_to_admin_login(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)
            ->post('/logout');

        $this->assertGuest();

        $response->assertRedirect(route('admin.login'));
    }
}
