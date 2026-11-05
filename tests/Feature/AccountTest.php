<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create([
            'name' => 'Default Tenant',
            'slug' => 'default',
            'status' => 'active',
        ]);
    }


    public function test_authenticated_user_can_view_account_overview(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.index'))
            ->assertOk()
            ->assertViewIs('storefront.account.index');
    }

    public function test_guest_cannot_view_account_overview(): void
    {
        $this->get(route('account.index'))
            ->assertRedirect(route('login'));
    }
}
