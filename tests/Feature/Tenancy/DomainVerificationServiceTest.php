<?php

namespace Tests\Feature\Tenancy;

use App\Models\DomainVerificationChallenge;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\DomainVerificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DomainVerificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private FakeDomainVerificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new FakeDomainVerificationService();
    }

    private function createStore(string $name = 'Store A'): Store
    {
        $owner = User::factory()->create();

        $tenant = Tenant::create([
            'name' => $name,
            'slug' => str($name)->slug().'-'.$owner->id,
            'status' => 'active',
            'created_by_user_id' => $owner->id,
        ]);

        return Store::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => str($name)->slug().'-'.$owner->id,
            'status' => 'active',
        ]);
    }

    public function test_create_normalizes_domain_and_creates_dns_challenge(): void
    {
        $store = $this->createStore();

        $challenge = $this->service->create(
            $store,
            '  SHOP.Example.Test.  '
        );

        $this->assertDatabaseHas('store_domains', [
            'id' => $challenge->store_domain_id,
            'store_id' => $store->id,
            'domain' => 'shop.example.test',
            'type' => 'custom',
            'verification_status' => 'pending',
            'is_primary' => false,
        ]);

        $this->assertSame(
            '_saas-verification.shop.example.test',
            $challenge->record_name
        );

        $this->assertSame('dns_txt', $challenge->method);
        $this->assertStringStartsWith(
            'saas-verification=',
            $challenge->record_value
        );
        $this->assertSame(48, strlen(
            substr($challenge->record_value, strlen('saas-verification='))
        ));
        $this->assertTrue($challenge->expires_at->between(
            now()->addHours(23)->subMinute(),
            now()->addHours(24)->addMinute()
        ));
        $this->assertNull($challenge->verified_at);
    }

    public function test_create_rejects_invalid_domain(): void
    {
        $store = $this->createStore();

        $this->expectException(ValidationException::class);

        $this->service->create($store, 'http://localhost/path');
    }

    public function test_create_rejects_domain_owned_by_another_store(): void
    {
        $storeA = $this->createStore('Store A');
        $storeB = $this->createStore('Store B');

        StoreDomain::create([
            'store_id' => $storeA->id,
            'domain' => 'shared.example.test',
            'verification_status' => 'pending',
        ]);

        $this->expectException(ValidationException::class);

        $this->service->create($storeB, 'shared.example.test');
    }

    public function test_verify_returns_false_when_dns_txt_does_not_match(): void
    {
        $store = $this->createStore();
        $challenge = $this->service->create(
            $store,
            'shop.example.test'
        );

        $this->service->records = ['saas-verification=wrong-token'];

        $this->assertFalse($this->service->verify($challenge));

        $this->assertDatabaseHas('store_domains', [
            'id' => $challenge->store_domain_id,
            'verification_status' => 'pending',
            'verified_at' => null,
            'is_primary' => false,
        ]);

        $this->assertDatabaseHas('domain_verification_challenges', [
            'id' => $challenge->id,
            'verified_at' => null,
        ]);
    }

    public function test_verify_accepts_matching_dns_txt_and_sets_first_domain_as_primary(): void
    {
        $store = $this->createStore();
        $challenge = $this->service->create(
            $store,
            'shop.example.test'
        );

        $this->service->records = [$challenge->record_value];

        $this->assertTrue($this->service->verify($challenge));

        $this->assertDatabaseHas('store_domains', [
            'id' => $challenge->store_domain_id,
            'verification_status' => 'verified',
            'is_primary' => true,
        ]);

        /* $this->assertDatabaseHas('domain_verification_challenges', [
            'id' => $challenge->id,
            'verified_at' => $this->anything(),
        ]); */
        
        $this->assertNotNull(
            $challenge->fresh()->verified_at,
            'Challenge seharusnya memiliki verified_at setelah verifikasi berhasil.'
        );

        $domain = StoreDomain::findOrFail($challenge->store_domain_id);

        $this->assertNotNull($domain->verified_at);
        $this->assertNotNull($domain->verificationChallenge->verified_at);
    }

    public function test_verify_rejects_expired_challenge(): void
    {
        $store = $this->createStore();
        $challenge = $this->service->create(
            $store,
            'expired.example.test'
        );

        $challenge->update([
            'expires_at' => now()->subMinute(),
        ]);

        $this->service->records = [$challenge->record_value];

        $this->assertFalse($this->service->verify($challenge));

        $this->assertDatabaseHas('store_domains', [
            'id' => $challenge->store_domain_id,
            'verification_status' => 'pending',
            'verified_at' => null,
        ]);
    }

    public function test_verifying_another_domain_does_not_replace_existing_primary_domain(): void
    {
        $store = $this->createStore();

        $firstChallenge = $this->service->create(
            $store,
            'first.example.test'
        );

        $this->service->records = [$firstChallenge->record_value];

        $this->assertTrue($this->service->verify($firstChallenge));

        $secondChallenge = $this->service->create(
            $store,
            'second.example.test'
        );

        $this->service->records = [$secondChallenge->record_value];

        $this->assertTrue($this->service->verify($secondChallenge));

        $this->assertDatabaseHas('store_domains', [
            'id' => $firstChallenge->store_domain_id,
            'verification_status' => 'verified',
            'is_primary' => true,
        ]);

        $this->assertDatabaseHas('store_domains', [
            'id' => $secondChallenge->store_domain_id,
            'verification_status' => 'verified',
            'is_primary' => false,
        ]);

        $this->assertSame(
            1,
            StoreDomain::query()
                ->where('store_id', $store->id)
                ->where('is_primary', true)
                ->count()
        );
    }
}

class FakeDomainVerificationService extends DomainVerificationService
{
    public array $records = [];

    protected function lookupTxtRecords(string $hostname): array
    {
        return $this->records;
    }
}
