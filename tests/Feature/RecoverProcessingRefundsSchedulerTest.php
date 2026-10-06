<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class RecoverProcessingRefundsSchedulerTest extends TestCase
{
    public function test_recover_processing_refunds_command_is_scheduled_every_five_minutes(): void
    {
        $schedule = app(Schedule::class);

        $event = collect($schedule->events())
            ->first(function ($event) {
                return str_contains(
                    $event->command ?? '',
                    'recover:processing-refunds'
                );
            });

        $this->assertNotNull($event);

        $this->assertSame(
            '*/5 * * * *',
            $event->expression
        );
    }
}
