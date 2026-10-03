<?php

use App\Jobs\RecoverProcessingRefund;
use App\Models\Refund;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    Refund::query()
        ->where('status', Refund::STATUS_PROCESSING)
        ->whereNotNull('processing_at')
        ->where(
            'processing_at',
            '<=',
            now()->subMinutes(10)
        )
        ->pluck('id')
        ->each(
            fn (int $refundId) => RecoverProcessingRefund::dispatch($refundId)
        );
})
    ->everyFiveMinutes()
    ->name('refund-recovery')
    ->onOneServer()
    ->withoutOverlapping();
