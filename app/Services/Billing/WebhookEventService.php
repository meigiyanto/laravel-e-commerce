<?php

namespace App\Services\Billing;

use App\Models\WebhookEvent;
use Illuminate\Support\Facades\DB;

class WebhookEventService
{
    public function record(
        string $provider,
        string $eventId,
        string $eventType,
        array $payload,
        ?int $tenantId = null
    ): array {
        return DB::transaction(function () use (
            $provider,
            $eventId,
            $eventType,
            $payload,
            $tenantId
        ) {
            $event = WebhookEvent::query()->firstOrCreate(
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

            $event = WebhookEvent::query()
                ->whereKey($event->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($event->status === 'processed') {
                return [$event, false];
            }

            return [$event, true];
        });
    }

    public function markProcessed(WebhookEvent $event): void
    {
        $event->update([
            'status' => 'processed',
            'processed_at' => now(),
            'failure_reason' => null,
        ]);
    }

    public function markFailed(WebhookEvent $event, string $reason): void
    {
        $event->update([
            'status' => 'failed',
            'failure_reason' => mb_substr($reason, 0, 5000),
        ]);
    }
}
