<?php

namespace App\Jobs;

use App\Models\Refund;
use App\Models\Order;
use App\Models\Payment;
use App\Services\RefundService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Jobs\Middleware\SetTenantContext;
use LogicException;

class RecoverProcessingRefundJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [60,
        300,
        900];
    public int $timeout = 85;

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
            config('operational.mode', 'single_store') === 'multi_tenant' && $this->tenantId === null
        ) {
            throw new LogicException(
                'Tenant ID wajib tersedia untuk recovery refund multi-tenant.'
            );
        }

        $refund = Refund::query()
        ->whereKey($this->refundId)
        ->first();

        if (! $refund || $refund->status !== Refund::STATUS_PROCESSING) {
            return;
        }

        // Order memakai global scope tenant. Refund tidak.
        $order = Order::query()
        ->whereKey($refund->order_id)
        ->first();

        // Order tenant lain tidak boleh terlihat dari konteks job ini.
        if (! $order) {
            return;
        }

        if (
            config('operational.mode', 'single_store') === 'multi_tenant' && (int) $order->tenant_id !== $this->tenantId
        ) {
            throw new LogicException(
                'Tenant refund tidak sesuai dengan tenant job.'
            );
        }

        $payment = Payment::query()
        ->whereKey($refund->payment_id)
        ->first();

        if (! $payment || (int) $payment->order_id !== (int) $order->id) {
            throw new LogicException(
                'Payment refund tidak dimiliki oleh order yang sama.'
            );
        }

        $refundService->recoverRefund($refund);
    }
}