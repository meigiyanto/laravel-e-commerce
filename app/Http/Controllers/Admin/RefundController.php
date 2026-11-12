<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Refund;
use App\Services\RefundService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RefundController extends Controller
{
    public function __construct(
        protected RefundService $refundService
    ) {}

    /**
     * Memastikan aktor boleh mengelola refund.
     */
    private function authorizeRefundManagement(): void
    {
        $user = request()->user();

        abort_unless($user, 403);

        if (config('operational.mode', 'single_store') === 'single_store') {
            abort_unless($user->hasAnyRole('admin'), 403);

            return;
        }

        $tenant = app(TenantContext::class)->current();

        abort_unless($tenant, 403);
        abort_unless(
            $user->hasTenantPermission('refunds.manage'),
            403
        );
    }

    /**
     * Query refund hanya untuk order pada store/tenant aktif.
     */
    private function refundQuery()
    {
        $query = Refund::query();

        if (config('operational.mode', 'single_store') === 'single_store') {
            return $query->whereHas('order', function ($orders) {
                $orders->whereNull('tenant_id');
            });
        }

        $tenant = app(TenantContext::class)->current();

        abort_unless($tenant, 403);

        return $query->whereHas('order', function ($orders) use ($tenant) {
            $orders->where('tenant_id', $tenant->id);
        });
    }

    /**
     * Memuat refund melalui query yang sudah dibatasi.
     */
    private function findRefund(int $id): Refund
    {
        return $this->refundQuery()->findOrFail($id);
    }

    /**
     * Menampilkan daftar refund.
     */
    public function index(Request $request)
    {
        $this->authorizeRefundManagement();

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

        $refunds = $this->refundQuery()
            ->with(['order.user', 'payment'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('order', function ($orders) use (
                        $search
                    ) {
                        $orders->where(function ($orders) use ($search) {
                            $orders->where(
                                'order_number',
                                'like',
                                "%{$search}%"
                            )->orWhere(
                                'customer_name',
                                'like',
                                "%{$search}%"
                            );
                        });
                    })->orWhereHas('order.user', function ($users) use (
                        $search
                    ) {
                        $users->where(function ($users) use ($search) {
                            $users->where(
                                'name',
                                'like',
                                "%{$search}%"
                            )->orWhere(
                                'email',
                                'like',
                                "%{$search}%"
                            );
                        });
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

        $statusCounts = $this->refundQuery()
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
        $this->authorizeRefundManagement();

        $refund = $this->findRefund((int) $refund->id);

        $refund->load([
            'order.user',
            'order.items.product',
            'payment',
        ]);

        return view('admin.refunds.show', compact('refund'));
    }

    /**
     * Memproses refund yang masih berstatus requested.
     */
    public function process(Refund $refund)
    {
        $this->authorizeRefundManagement();

        $refund = $this->findRefund((int) $refund->id);

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
                ->with('success', 'Refund berhasil diproses.');
        } catch (ValidationException $exception) {
            throw $exception;
        }
    }

    /**
     * Menolak refund requested secara atomik.
     */
    public function reject(Refund $refund)
    {
        $this->authorizeRefundManagement();

        $refund = $this->findRefund((int) $refund->id);

        $result = DB::transaction(function () use ($refund) {
            // Lock order terlebih dahulu agar semua operasi refund
            // menggunakan urutan lock yang konsisten.
            $order = Order::query()
                ->whereKey($refund->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                config('operational.mode', 'single_store') === 'single_store'
                && $order->tenant_id !== null
            ) {
                abort(404);
            }

            if (
                config('operational.mode', 'single_store') === 'multi_tenant'
            ) {
                $tenant = app(TenantContext::class)->current();

                abort_unless(
                    $tenant
                    && (int) $order->tenant_id === (int) $tenant->id,
                    404
                );
            }

            $lockedRefund = Refund::query()
                ->whereKey($refund->id)
                ->where('order_id', $order->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRefund->status !== Refund::STATUS_REQUESTED) {
                return null;
            }

            $lockedRefund->update([
                'status' => Refund::STATUS_REJECTED,
                'processed_at' => now(),
            ]);

            return $lockedRefund;
        });

        if (! $result) {
            return back()->with(
                'error',
                'Refund ini sudah diproses atau tidak dapat ditolak kembali.'
            );
        }

        return redirect()
            ->route('admin.refunds.show', $result)
            ->with('success', 'Refund berhasil ditolak.');
    }
}
