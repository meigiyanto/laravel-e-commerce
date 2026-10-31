<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_account_overview(): void
    {
        $this->get(route('account.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_account_overview(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('account.index'))
            ->assertOk()
            ->assertViewIs('storefront.account.index')
            ->assertViewHas('user')
            ->assertViewHas('recentOrders');
    }

    public function test_account_overview_displays_user_statistics(): void
    {
        $user = User::factory()->create();

        Order::factory()->count(3)->create([
            'user_id' => $user->id,
        ]);

        UserAddress::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('account.index'))
            ->assertOk()
            ->assertViewHas('user', function ($accountUser) {
                return $accountUser->orders_count === 3
                    && $accountUser->addresses_count === 2;
            });
    }

    public function test_account_overview_only_displays_authenticated_users_orders(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
        ]);

        Order::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $this->actingAs($user)
            ->get(route('account.index'))
            ->assertOk()
            ->assertViewHas('recentOrders', function ($orders) use ($order) {
                return $orders->count() === 1
                    && $orders->first()->id === $order->id;
            });
    }
}