<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function __construct(
        protected RefundService $refundService
    ) {}

    /**
     * Mengajukan refund untuk order milik customer yang sedang login.
     */
    public function store(
        Request $request,
        Order $order
    ): RedirectResponse {
        /*
         * Customer hanya boleh mengajukan refund
         * untuk order miliknya sendiri.
         */
        abort_unless(
            $order->user_id === auth()->id(),
            403
        );

        $data = $request->validate([
            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $refund = $this->refundService->request(
            $order,
            $data['amount'],
            $data['reason'] ?? null
        );

        return redirect()
            ->route('orders.show', $order)
            ->with(
                'success',
                'Permintaan refund berhasil diajukan dan menunggu proses admin.'
            );
    }
}