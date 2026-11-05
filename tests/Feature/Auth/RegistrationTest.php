<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
    
        $tenant = Tenant::factory()->create([
            'name' => 'Default Tenant',
            'slug' => 'default',
            'status' => 'active',
        ]);
        
        $this->tenant = $tenant;

    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_new_users_register_as_customers_and_are_sent_to_email_verification(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'role' => User::ROLE_CUSTOMER,
        ]);

        $response->assertRedirect(route('verification.notice', absolute: false));
    }
}
