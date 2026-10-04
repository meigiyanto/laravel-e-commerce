<?php

namespace App\Console\Commands;

use App\Jobs\RecoverProcessingRefundJob;
use App\Services\RefundService;
use Illuminate\Console\Command;

class RecoverProcessingRefundsCommand extends Command
{
    protected $signature = 'recover:processing-refunds';

    protected $description = 'Dispatch jobs untuk refund yang stuck dalam status processing';

    public function handle(
        RefundService $refundService
    ): int {
        $refunds = $refundService->recoverProcessing();

        foreach ($refunds as $refund) {
            RecoverProcessingRefundJob::dispatch(
                $refund->id
            );
        }

        $this->info(
            sprintf(
                'Dispatched %d refund recovery job(s).',
                $refunds->count()
            )
        );

        return self::SUCCESS;
    }
}