<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundController extends Controller
{
    public function __construct(
        protected RefundService $refundService
    ) {}

    /**
     * Menampilkan daftar refund.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');

        $statuses = [
            Refund::STATUS_REQUESTED,
            Refund::STATUS_APPROVED,
            Refund::STATUS_PROCESSING,
            Refund::STATUS_COMPLETED,
            Refund::STATUS_REJECTED,
            Refund::STATUS_FAILED,
        ];

        $refunds = Refund::query()
            ->with([
                'order',
                'order.user',
                'payment',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->whereHas('order', function ($query) use ($search) {
                            $query
                                ->where(
                                    'order_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'customer_name',
                                    'like',
                                    "%{$search}%"
                                );
                        })
                        ->orWhereHas('order.user', function ($query) use ($search) {
                            $query
                                ->where(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'email',
                                    'like',
                                    "%{$search}%"
                                );
                        });
                });
            })
            ->when(
                in_array($status, $statuses, true),
                fn ($query) => $query->where('status', $status)
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $statusCounts = Refund::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('admin.refunds.index', [
            'refunds' => $refunds,
            'search' => $search,
            'status' => $status,
            'statuses' => $statuses,
            'statusCounts' => $statusCounts,
        ]);
    }

    /**
     * Menampilkan detail refund.
     */
    public function show(Refund $refund)
    {
        $refund->load([
            'order.user',
            'order.items.product',
            'payment',
        ]);

        return view(
            'admin.refunds.show',
            compact('refund')
        );
    }

    /**
     * Memproses refund yang masih berstatus requested.
     */
    public function process(Refund $refund)
    {
        if ($refund->status !== Refund::STATUS_REQUESTED) {
            return back()->with(
                'error',
                'Refund ini sudah diproses atau tidak dapat diproses kembali.'
            );
        }

        try {
            $processedRefund = $this->refundService->process($refund);

            return redirect()
                ->route('admin.refunds.show', $processedRefund)
                ->with(
                    'success',
                    'Refund berhasil diproses.'
                );
        } catch (ValidationException $exception) {
            throw $exception;
        }
    }

    /**
     * Menolak refund yang masih berstatus requested.
     */
    public function reject(Refund $refund)
    {
        return DB::transaction(function () use ($refund) {
            $refund = Refund::query()
                ->lockForUpdate()
                ->findOrFail($refund->id);

            if ($refund->status !== Refund::STATUS_REQUESTED) {
                return back()->with(
                    'error',
                    'Refund ini sudah diproses atau tidak dapat ditolak kembali.'
                );
            }

            $refund->update([
                'status' => Refund::STATUS_REJECTED,
                'processed_at' => now(),
            ]);

            return redirect()
                ->route('admin.refunds.show', $refund)
                ->with(
                    'success',
                    'Refund berhasil ditolak.'
                );
        });
    }

    public function test_admin_can_process_requested_refund(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $order = Order::factory()->create([
            'user_id' => $admin->id,
            'status' => 'completed',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
            'requested_at' => now(),
        ]);

        $processedRefund = $refund->fresh();

        $processedRefund->update([
            'status' => Refund::STATUS_COMPLETED,
            'reference_id' => 're_test_123',
            'processed_at' => now(),
        ]);

        $this->mock(RefundService::class, function ($mock) use (
            $refund,
            $processedRefund
        ) {
            $mock
                ->shouldReceive('process')
                ->once()
                ->withArgs(function ($argument) use ($refund) {
                    return $argument->is($refund);
                })
                ->andReturn($processedRefund);
        });

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.refunds.process', $refund));

        $response
            ->assertRedirect(
                route('admin.refunds.show', $processedRefund)
            )
            ->assertSessionHas(
                'success',
                'Refund berhasil diproses.'
            );
    }

    public function test_admin_cannot_process_completed_refund(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $order = Order::factory()->create([
            'user_id' => $admin->id,
            'status' => 'completed',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'status' => Refund::STATUS_COMPLETED,
            'provider' => 'stripe',
            'requested_at' => now(),
            'processed_at' => now(),
        ]);

        $this->mock(RefundService::class, function ($mock) {
            $mock
                ->shouldNotReceive('process');
        });

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.refunds.process', $refund));

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'error',
                'Refund ini sudah diproses atau tidak dapat diproses kembali.'
            );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_COMPLETED,
        ]);
    }

    public function test_admin_can_reject_requested_refund(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $order = Order::factory()->create([
            'user_id' => $admin->id,
            'status' => 'completed',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'status' => Refund::STATUS_REQUESTED,
            'provider' => 'stripe',
            'requested_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.refunds.reject', $refund));

        $response
            ->assertRedirect(
                route('admin.refunds.show', $refund)
            )
            ->assertSessionHas(
                'success',
                'Refund berhasil ditolak.'
            );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_REJECTED,
        ]);
    }

    public function test_admin_cannot_reject_completed_refund(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $order = Order::factory()->create([
            'user_id' => $admin->id,
            'status' => 'completed',
        ]);

        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'provider' => 'stripe',
            'status' => 'succeeded',
            'gross_amount' => 150000,
        ]);

        $refund = Refund::create([
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'amount' => 50000,
            'currency' => 'IDR',
            'status' => Refund::STATUS_COMPLETED,
            'provider' => 'stripe',
            'requested_at' => now(),
            'processed_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.refunds.reject', $refund));

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'error',
                'Refund ini sudah diproses atau tidak dapat ditolak kembali.'
            );

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_COMPLETED,
        ]);
    }

    public function test_non_admin_cannot_process_refund(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $refund = Refund::factory()->create([
            'status' => Refund::STATUS_REQUESTED,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('admin.refunds.process', $refund));

        $response->assertForbidden();

        $this->assertDatabaseHas('refunds', [
            'id' => $refund->id,
            'status' => Refund::STATUS_REQUESTED,
        ]);
    }
}
