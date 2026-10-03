<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use Illuminate\Http\Request;

class RefundController extends Controller
{
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
}