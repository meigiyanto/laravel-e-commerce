<?php

namespace App\Jobs;

use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecoverProcessingRefund implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public int $refundId
    ) {}

    public function handle(
        RefundService $refundService
    ): void {
        $refund = Refund::find($this->refundId);

        if (! $refund) {
            return;
        }

        if ($refund->status !== Refund::STATUS_PROCESSING) {
            return;
        }

        $refundService->recoverProcessing($refund);
    }
}
