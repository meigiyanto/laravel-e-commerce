<?php

namespace Tests\Feature\Billing;

use App\Models\Tenant;
use App\Services\Billing\WebhookEventService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use RuntimeException;
use Tests\TestCase;

class WebhookEventServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_event_is_processed_only_once(): void
    {
        $service = app(WebhookEventService::class);
        $handled = 0;

        $first = $service->process(
            provider: 'stripe',
            eventId: 'evt_once_001',
            eventType: 'payment_intent.succeeded',
            payload: ['id' => 'evt_once_001'],
            handler: function () use (&$handled) {
                $handled++;
            }
        );

        $second = $service->process(
            provider: 'stripe',
            eventId: 'evt_once_001',
            eventType: 'payment_intent.succeeded',
            payload: ['id' => 'evt_once_001'],
            handler: function () use (&$handled) {
                $handled++;
            }
        );

        $this->assertTrue($first);
        $this->assertFalse($second);
        $this->assertSame(1, $handled);

        $this->assertDatabaseHas('billing_webhook_events', [
            'provider' => 'stripe',
            'event_id' => 'evt_once_001',
            'status' => 'processed',
        ]);
    }

    public function test_failed_webhook_can_be_retried(): void
    {
        $service = app(WebhookEventService::class);

        try {
            $service->process(
                provider: 'stripe',
                eventId: 'evt_retry_001',
                eventType: 'payment_intent.succeeded',
                payload: ['id' => 'evt_retry_001'],
                handler: function () {
                    throw new RuntimeException('Simulated failure');
                }
            );

            $this->fail('Handler seharusnya gagal.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Simulated failure',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('billing_webhook_events', [
            'provider' => 'stripe',
            'event_id' => 'evt_retry_001',
            'status' => 'failed',
        ]);

        $handled = false;

        $result = $service->process(
            provider: 'stripe',
            eventId: 'evt_retry_001',
            eventType: 'payment_intent.succeeded',
            payload: ['id' => 'evt_retry_001'],
            handler: function () use (&$handled) {
                $handled = true;
            }
        );

        $this->assertTrue($result);
        $this->assertTrue($handled);

        $this->assertDatabaseHas('billing_webhook_events', [
            'provider' => 'stripe',
            'event_id' => 'evt_retry_001',
            'status' => 'processed',
        ]);
    }

    public function test_event_cannot_be_reused_by_another_tenant(): void
    {
        config(['operational.mode' => 'multi_tenant']);

        $tenantA = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $tenantB = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $service = app(WebhookEventService::class);
        $context = app(TenantContext::class);

        $context->run($tenantA, function () use ($service, $tenantA) {
            $service->process(
                provider: 'stripe',
                eventId: 'evt_tenant_collision',
                eventType: 'payment_intent.succeeded',
                payload: ['id' => 'evt_tenant_collision'],
                handler: fn () => null,
                tenantId: $tenantA->id
            );
        });

        $this->expectException(LogicException::class);

        $context->run($tenantB, function () use ($service, $tenantB) {
            $service->process(
                provider: 'stripe',
                eventId: 'evt_tenant_collision',
                eventType: 'payment_intent.succeeded',
                payload: ['id' => 'evt_tenant_collision'],
                handler: fn () => null,
                tenantId: $tenantB->id
            );
        });
    }

    public function test_tenant_id_must_match_active_context(): void
    {
        config(['operational.mode' => 'multi_tenant']);

        $tenant = Tenant::factory()->create([
            'status' => 'active',
        ]);

        $this->expectException(LogicException::class);

        app(WebhookEventService::class)->process(
            provider: 'stripe',
            eventId: 'evt_wrong_context',
            eventType: 'payment_intent.succeeded',
            payload: ['id' => 'evt_wrong_context'],
            handler: fn () => null,
            tenantId: $tenant->id
        );
    }
}
