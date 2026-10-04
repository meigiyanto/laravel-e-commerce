<?php

namespace Tests\Feature;

use App\Jobs\RecoverProcessingRefundJob;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Refund;
use App\Models\User;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RecoverProcessingRefundJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_delegates_processing_refund_to_refund_service(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'cod',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Refund Job recovery',
            'status' => Refund::STATUS_PROCESSING,
            'provider' => 'cod',
            'requested_at' => now()->subMinutes(30),
            'processing_at' => now()->subMinutes(20),
        ]);

        $service = Mockery::mock(RefundService::class);

        $service
            ->shouldReceive('recoverRefund')
            ->once()
            ->withArgs(function (Refund $receivedRefund) use ($refund) {
                return $receivedRefund->is($refund);
            });

        $job = new RecoverProcessingRefundJob(
            $refund->id
        );

        $job->handle($service);
    }

    public function test_job_does_not_delegate_when_refund_is_no_longer_processing(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'completed',
            'total' => 150000,
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'cod',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'reason' => 'Refund Job already completed',
            'status' => Refund::STATUS_COMPLETED,
            'provider' => 'cod',
            'requested_at' => now()->subMinutes(30),
            'processing_at' => now()->subMinutes(20),
            'processed_at' => now(),
        ]);

        $service = Mockery::mock(RefundService::class);

        $service
            ->shouldNotReceive('recoverRefund');

        $job = new RecoverProcessingRefundJob(
            $refund->id
        );

        $job->handle($service);
    }
}