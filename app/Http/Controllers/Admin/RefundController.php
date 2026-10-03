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
}