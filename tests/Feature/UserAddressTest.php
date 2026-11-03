<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserAddress;
use App\Models\Tenant;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserAddressTest extends TestCase
{
    use RefreshDatabase;
    private ?string $tenantDomain = null;

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
            ->get($this->tenantUrl($owner, 'account.addresses.index'))
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
            ->get($this->tenantUrl($owner, 'account.addresses.edit', $address))
            ->assertNotFound();

        $this->actingAs($owner)
            ->put($this->tenantUrl($owner, 'account.addresses.update', $address), $this->validAddressData())
            ->assertNotFound();

        $this->actingAs($owner)
            ->delete($this->tenantUrl($owner, 'account.addresses.destroy', $address))
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
                $this->tenantUrl($user, 'account.addresses.store'),
                $this->validAddressData()
            )
            ->assertRedirect($this->tenantUrl($user, 'account.addresses.index'));

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
            ->patch($this->tenantUrl($user, 'account.addresses.default', $second))
            ->assertRedirect($this->tenantUrl($user, 'account.addresses.index'));

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
            ->delete($this->tenantUrl($user, 'account.addresses.destroy', $default))
            ->assertRedirect($this->tenantUrl($user, 'account.addresses.index'));

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
    
    public function test_user_can_view_create_address_page(): void
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->get($this->tenantUrl($user, 'account.addresses.create'))
            ->assertOk()
            ->assertViewIs('storefront.account.addresses.form');
    }
    
    public function test_user_can_view_edit_address_page(): void
    {
        $user = User::factory()->create();
        $address = UserAddress::factory()->for($user)->create();
        
        $this->actingAs($user)
            ->get($this->tenantUrl($user, 'account.addresses.edit', $address))
            ->assertOk()
            ->assertViewIs('storefront.account.addresses.form')
            ->assertViewHas('address', $address);
    }
    
    public function test_user_can_create_an_address(): void
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->post(
                $this->tenantUrl($user, 'account.addresses.store'),
                $this->validAddressData()
            )
            ->assertRedirect($this->tenantUrl($user, 'account.addresses.index'));
    
        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id,
            'label' => 'Rumah',
            'recipient_name' => 'Test Customer',
        ]);
    }
    
    public function test_user_can_update_their_address(): void
    {
        $user = User::factory()->create();
        $address = UserAddress::factory()->for($user)->create();
        
        $data = $this->validAddressData();
        $data['label'] = 'Kantor';
        $data['city'] = 'Jakarta Pusat';
    
        $this->actingAs($user)
            ->put($this->tenantUrl($user, 'account.addresses.update', $address), $data)
            ->assertRedirect($this->tenantUrl($user, 'account.addresses.index'));
    
        $this->assertDatabaseHas('user_addresses', [
            'id' => $address->id,
            'label' => 'Kantor',
            'city' => 'Jakarta Pusat',
        ]);
    }
    
    public function test_address_requires_mandatory_fields(): void
    {
        $user = User::factory()->create();
    
        $this->actingAs($user)
            ->post($this->tenantUrl($user, 'account.addresses.store'), [])
            ->assertSessionHasErrors([
                'label',
                'recipient_name',
                'phone',
                'address_line',
                'province',
                'city',
                'district',
                'village',
                'postal_code',
                'country',
            ]);
    }
    
    public function test_guest_cannot_access_address_pages(): void
    {
        $domain = $this->createTenantDomain();
    
        $this->get($this->tenantUrl($domain, 'account.addresses.index'))
            ->assertRedirect($this->tenantUrl($domain, 'login'));
    
        $this->get($this->tenantUrl($domain, 'account.addresses.create'))
            ->assertRedirect($this->tenantUrl($domain, 'login'));
    }
    
    private function tenantUrl(
        User|string $context,
        string $routeName,
        mixed $parameters = []
    ): string {
        $domain = is_string($context) ? $context : $this->createTenantDomain();
    
        return 'http://'.$domain.route($routeName, $parameters, false);
    }
    
    private function createTenantDomain(): string
    {
        if ($this->tenantDomain !== null) {
            return $this->tenantDomain;
        }
    
        $tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);
    
        $store = Store::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => 'active',
            ]
        );
    
        $domain = 'address-test-'.$tenant->id.'.example.test';
    
        StoreDomain::updateOrCreate(
            ['domain' => $domain],
            [
                'store_id' => $store->id,
                'verification_status' => 'verified',
                'is_primary' => true,
            ]
        );
    
        return $this->tenantDomain = $domain;
    }
}
