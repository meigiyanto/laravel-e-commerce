<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAddressTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_many_addresses_and_address_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $addresses = UserAddress::factory()->count(2)->for($user)->create();

        $this->assertCount(2, $user->addresses);
        $this->assertTrue($addresses->first()->user->is($user));
    }

    public function test_user_can_only_see_their_own_addresses(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $ownedAddress = UserAddress::factory()->for($owner)->create();
        $otherAddress = UserAddress::factory()->for($other)->create();

        $this->actingAs($owner)
            ->get(route('account.addresses.index'))
            ->assertOk()
            ->assertSee($ownedAddress->label)
            ->assertDontSee($otherAddress->recipient_name);
    }

    public function test_user_cannot_access_another_users_address(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $address = UserAddress::factory()->for($other)->create();

        $this->actingAs($owner)
            ->get(route('account.addresses.edit', $address))
            ->assertNotFound();

        $this->actingAs($owner)
            ->patch(
                route('account.addresses.update', $address),
                $this->validAddressData()
            )
            ->assertNotFound();

        $this->actingAs($owner)
            ->delete(route('account.addresses.destroy', $address))
            ->assertNotFound();

        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'user_id' => $other->id,
        ]);
    }

    public function test_first_address_becomes_default_automatically(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(
                route('account.addresses.store'),
                $this->validAddressData()
            )
            ->assertRedirect(route('account.addresses.index'));

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'label' => 'Rumah',
            'is_default' => true,
        ]);
    }

    public function test_setting_default_address_unsets_previous_default(): void
    {
        $user = User::factory()->create();

        $first = UserAddress::factory()->for($user)->default()->create();
        $second = UserAddress::factory()->for($user)->create();

        $this->actingAs($user)
            ->patch(route('account.addresses.default', $second))
            ->assertRedirect(route('account.addresses.index'));

        $this->assertDatabaseHas('user_addresses', [
            'id' => $first->id,
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('user_addresses', [
            'id' => $second->id,
            'is_default' => true,
        ]);
    }

    public function test_deleting_default_address_promotes_another_address(): void
    {
        $user = User::factory()->create();

        $default = UserAddress::factory()->for($user)->default()->create();
        $next = UserAddress::factory()->for($user)->create();

        $this->actingAs($user)
            ->delete(route('account.addresses.destroy', $default))
            ->assertRedirect(route('account.addresses.index'));

        $this->assertDatabaseMissing('user_addresses', [
            'id' => $default->id,
        ]);

        $this->assertDatabaseHas('user_addresses', [
            'id' => $next->id,
            'is_default' => true,
        ]);
    }

    private function validAddressData(): array
    {
        return [
            'label' => 'Rumah',
            'recipient_name' => 'Test Customer',
            'phone' => '081234567890',
            'address_line' => 'Jl. Contoh No. 10',
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => 'Kebayoran Baru',
            'village' => 'Melawai',
            'postal_code' => '12160',
            'country' => 'Indonesia',
        ];
    }
}
