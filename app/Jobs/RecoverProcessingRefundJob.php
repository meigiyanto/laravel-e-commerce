<?php

namespace App\Jobs;

use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecoverProcessingRefundJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $refundId
    ) {
    }

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

        $refundService->recoverRefund($refund);
    }
}