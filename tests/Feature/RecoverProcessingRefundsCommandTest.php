<?php

namespace Tests\Feature;

use App\Jobs\RecoverProcessingRefundJob;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class RecoverProcessingRefundsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_dispatches_one_job_for_each_eligible_refund(): void
    {
        Bus::fake();

        $refundOne = new Refund();
        $refundOne->id = 101;

        $refundTwo = new Refund();
        $refundTwo->id = 202;

        $service = Mockery::mock(RefundService::class);

        $service
            ->shouldReceive('recoverProcessing')
            ->once()
            ->andReturn(collect([
                $refundOne,
                $refundTwo,
            ]));

        $this->app->instance(
            RefundService::class,
            $service
        );

        $this->artisan('recover:processing-refunds')
            ->assertExitCode(0);

        Bus::assertDispatchedTimes(
            RecoverProcessingRefundJob::class,
            2
        );

        Bus::assertDispatched(
            RecoverProcessingRefundJob::class,
            function (RecoverProcessingRefundJob $job) {
                return $job->refundId === 101;
            }
        );

        Bus::assertDispatched(
            RecoverProcessingRefundJob::class,
            function (RecoverProcessingRefundJob $job) {
                return $job->refundId === 202;
            }
        );
    }

    public function test_command_does_not_dispatch_when_no_refund_is_eligible(): void
    {
        Bus::fake();

        $service = Mockery::mock(RefundService::class);

        $service
            ->shouldReceive('recoverProcessing')
            ->once()
            ->andReturn(collect());

        $this->app->instance(
            RefundService::class,
            $service
        );

        $this->artisan('recover:processing-refunds')
            ->assertExitCode(0);

        Bus::assertNothingDispatched();
    }
}