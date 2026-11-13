<?php

namespace App\Jobs;

use App\Jobs\Middleware\SetTenantContext;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use LogicException;

class RecoverProcessingRefundJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $refundId,
        public ?int $tenantId = null
    ) {}

    public function middleware(): array
    {
        if (config('operational.mode', 'single_store') !== 'multi_tenant') {
            return [];
        }

        if ($this->tenantId === null) {
            return [];
        }

        return [
            new SetTenantContext($this->tenantId),
        ];
    }

    public function handle(RefundService $refundService): void
    {
        if (
            config('operational.mode', 'single_store') === 'multi_tenant'
            && $this->tenantId === null
        ) {
            throw new LogicException(
                'Tenant ID wajib tersedia untuk refund recovery Multi Tenant.'
            );
        }

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
