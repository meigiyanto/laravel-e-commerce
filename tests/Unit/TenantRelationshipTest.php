<?php

namespace Tests\Unit;

use App\Models\Invoice;
use App\Models\PlatformAuditLog;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\SubscriptionPayment;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\TenantMembership;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\TestCase;

class TenantRelationshipTest extends TestCase
{
    public function test_tenant_exposes_expected_relationships(): void
    {
        $tenant = new Tenant;

        $this->assertInstanceOf(HasMany::class, $tenant->memberships());
        $this->assertInstanceOf(HasMany::class, $tenant->activeMemberships());
        $this->assertInstanceOf(BelongsToMany::class, $tenant->users());
        $this->assertInstanceOf(HasOne::class, $tenant->store());
        $this->assertInstanceOf(HasMany::class, $tenant->invitations());
        $this->assertInstanceOf(HasMany::class, $tenant->auditLogs());
        $this->assertInstanceOf(HasMany::class, $tenant->invoices());
        $this->assertInstanceOf(HasMany::class, $tenant->subscriptionPayments());
    }

    public function test_tenant_relationships_reference_correct_models(): void
    {
        $tenant = new Tenant;

        $this->assertInstanceOf(TenantMembership::class, $tenant->memberships()->getRelated());
        $this->assertInstanceOf(TenantInvitation::class, $tenant->invitations()->getRelated());
        $this->assertInstanceOf(PlatformAuditLog::class, $tenant->auditLogs()->getRelated());
        $this->assertInstanceOf(Invoice::class, $tenant->invoices()->getRelated());
        $this->assertInstanceOf(SubscriptionPayment::class, $tenant->subscriptionPayments()->getRelated());
        $this->assertInstanceOf(Store::class, $tenant->store()->getRelated());
    }

    public function test_store_exposes_domain_relationships(): void
    {
        $store = new Store;

        $this->assertInstanceOf(HasMany::class, $store->domains());
        $this->assertInstanceOf(HasMany::class, $store->verifiedDomains());
        $this->assertInstanceOf(HasMany::class, $store->primaryDomain());
        $this->assertInstanceOf(StoreDomain::class, $store->domains()->getRelated());
    }
}
