<?php

namespace App\Services\Billing;

use App\Models\BillingWebhookEvent;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use LogicException;

class WebhookEventService
{
    private function assertTenantContext(?int $tenantId): void
    {
        $currentTenantId = app(TenantContext::class)->current()?->id;

        if (
            ($tenantId === null) !== ($currentTenantId === null)
            || (
                $tenantId !== null
                && (int) $tenantId !== (int) $currentTenantId
            )
        ) {
            throw new LogicException(
                'Webhook event tidak sesuai dengan tenant context aktif.'
            );
        }
    }

    public function record(
        string $provider,
        string $eventId,
        string $eventType,
        array $payload,
        ?int $tenantId = null
    ): array {
        $this->assertTenantContext($tenantId);

        return DB::transaction(function () use (
            $provider,
            $eventId,
            $eventType,
            $payload,
            $tenantId
        ) {
            $event = BillingWebhookEvent::query()->firstOrCreate(
                [
                    'provider' => $provider,
                    'event_id' => $eventId,
                ],
                [
                    'tenant_id' => $tenantId,
                    'event_type' => $eventType,
                    'status' => 'pending',
                    'payload' => $payload,
                ]
            );

            $event = BillingWebhookEvent::query()
                ->whereKey($event->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                (int) ($event->tenant_id ?? 0)
                !== (int) ($tenantId ?? 0)
            ) {
                throw new LogicException(
                    'Event ID sudah digunakan oleh tenant lain.'
                );
            }

            if ($event->status === 'processed') {
                return [$event, false];
            }

            $timeoutMinutes = max(
                1,
                (int) config('services.webhooks.processing_timeout_minutes', 15)
            );

            $processingIsActive = $event->status === 'processing'
                && $event->updated_at !== null
                && $event->updated_at->gt(now()->subMinutes($timeoutMinutes));

            if ($processingIsActive) {
                return [$event, false];
            }

            // Claim event secara atomik sebelum worker menjalankan handler.
            $event->update([
                'status' => 'processing',
                'failure_reason' => null,
            ]);

            return [$event->fresh(), true];
        });
    }

    public function markProcessed(BillingWebhookEvent $event): void
    {
        $this->assertTenantContext(
            $event->tenant_id === null ? null : (int) $event->tenant_id
        );

        DB::transaction(function () use ($event) {
            $locked = BillingWebhookEvent::query()
                ->whereKey($event->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTenantContext(
                $locked->tenant_id === null ? null : (int) $locked->tenant_id
            );

            $locked->update([
                'status' => 'processed',
                'processed_at' => now(),
                'failure_reason' => null,
            ]);
        });
    }

    public function markFailed(
        BillingWebhookEvent $event,
        string $reason
    ): void {
        $this->assertTenantContext(
            $event->tenant_id === null ? null : (int) $event->tenant_id
        );

        DB::transaction(function () use ($event, $reason) {
            $locked = BillingWebhookEvent::query()
                ->whereKey($event->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTenantContext(
                $locked->tenant_id === null ? null : (int) $locked->tenant_id
            );

            $locked->update([
                'status' => 'failed',
                'failure_reason' => mb_substr($reason, 0, 5000),
            ]);
        });
    }
}