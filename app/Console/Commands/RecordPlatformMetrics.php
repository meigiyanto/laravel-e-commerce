<?php

namespace App\Console\Commands;

use App\Services\Platform\PlatformAnalyticsService;
use Illuminate\Console\Command;

class RecordPlatformMetrics extends Command
{
    protected $signature = 'platform:record-metrics';
    protected $description = 'Record daily platform analytics';

    public function handle(PlatformAnalyticsService $analytics): int
    {
        $analytics->recordDailySnapshot();

        $this->info('Platform metrics recorded.');

        return self::SUCCESS;
    }
}
