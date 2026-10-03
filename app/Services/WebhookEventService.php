<?php

namespace App\Services;

use App\Models\WebhookEvent;
use Closure;
use Illuminate\Support\Facades\DB;

class WebhookEventService
{
    public function process(
        string $provider,
        string $eventId,
        ?string $eventType,
        array $payload,
        Closure $handler
    ): bool {
        return DB::transaction(function () use (
            $provider,
            $eventId,
            $eventType,
            $payload,
            $handler
        ) {
            $existing = WebhookEvent::query()
                ->where('provider', $provider)
                ->where('event_id', $eventId)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return false;
            }

            $handler();

            WebhookEvent::create([
                'provider' => $provider,
                'event_id' => $eventId,
                'event_type' => $eventType,
                'payload' => $payload,
                'processed_at' => now(),
            ]);

            return true;
        }, attempts: 3);
    }
}
