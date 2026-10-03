<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\RefundService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RefundController extends Controller
{
    public function __construct(
        protected RefundService $refundService,
    ) {
    }

    /**
     * Mengajukan refund untuk order milik user yang sedang login.
     */
    public function store(
        Request $request,
        Order $order
    ): RedirectResponse {
        abort_unless(
            $order->user_id === auth()->id(),
            403
        );

        $validated = $request->validate([
            'amount' => [
                'required',
                'numeric',
                'gt:0',
            ],
            'reason' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $this->refundService->request(
            $order,
            $validated['amount'],
            $validated['reason'] ?? null
        );

        return redirect()
            ->route('orders.show', $order)
            ->with(
                'success',
                'Pengajuan refund berhasil dibuat dan sedang menunggu proses.'
            );
    }
}