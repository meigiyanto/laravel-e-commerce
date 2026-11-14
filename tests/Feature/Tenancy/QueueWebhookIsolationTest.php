<?php

namespace Tests\Feature\Tenancy;

use App\Jobs\Middleware\SetTenantContext;
use App\Models\Tenant;
use App\Models\BillingWebhookEvent;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueWebhookIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_middleware_sets_and_clears_tenant_context(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'active']);
        $context = app(TenantContext::class);

        $middleware = new SetTenantContext($tenant->id);

        $executed = false;

        $middleware->handle(new \stdClass(), function () use (
            $context,
            $tenant,
            &$executed
        ) {
            $executed = true;

            $this->assertSame($tenant->id, $context->current()->id);
        });

        $this->assertTrue($executed);
        $this->assertNull($context->current());
    }

    public function test_queue_middleware_rejects_suspended_tenant(): void
    {
        $tenant = Tenant::factory()->create(['status' => 'suspended']);

        $middleware = new SetTenantContext($tenant->id);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $middleware->handle(new \stdClass(), fn () => null);
    }

    public function test_webhook_event_is_unique_per_provider_and_event_id(): void
    {
        $first = BillingWebhookEvent::create([
            'provider' => 'test-provider',
            'event_id' => 'evt_123',
            'event_type' => 'payment.succeeded',
            'status' => 'pending',
            'payload' => ['id' => 'evt_123'],
        ]);

        $this->assertDatabaseHas('billing_webhook_events', [
            'id' => $first->id,
            'provider' => 'test-provider',
            'event_id' => 'evt_123',
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        BillingWebhookEvent::create([
            'provider' => 'test-provider',
            'event_id' => 'evt_123',
            'event_type' => 'payment.succeeded',
            'status' => 'pending',
        ]);
    }

    public function test_same_event_id_can_exist_for_different_providers(): void
    {
        BillingWebhookEvent::create([
            'provider' => 'stripe',
            'event_id' => 'evt_123',
            'event_type' => 'payment.succeeded',
            'status' => 'pending',
        ]);

        BillingWebhookEvent::create([
            'provider' => 'midtrans',
            'event_id' => 'evt_123',
            'event_type' => 'payment.succeeded',
            'status' => 'pending',
        ]);

        $this->assertDatabaseCount('billing_webhook_events', 2);
    }

    public function test_queue_middleware_restores_previous_context_after_job_exception(): void
    {
        $tenantA = Tenant::factory()->create(['status' => 'active']);
        $tenantB = Tenant::factory()->create(['status' => 'active']);
        $context = app(TenantContext::class);

        $context->run($tenantA, function () use ($tenantB, $context) {
            try {
                (new SetTenantContext($tenantB->id))->handle(
                    new \stdClass(),
                    function () use ($tenantB, $context) {
                        $this->assertSame(
                            $tenantB->id,
                            $context->current()->id
                        );

                        throw new \RuntimeException('Simulated job failure');
                    }
                );

                $this->fail('Job seharusnya melempar exception.');
            } catch (\RuntimeException $exception) {
                $this->assertSame(
                    'Simulated job failure',
                    $exception->getMessage()
                );
            }

            $this->assertSame(
                $tenantA->id,
                $context->current()->id
            );
        });

        $this->assertNull($context->current());
    }
}