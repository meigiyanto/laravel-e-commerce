<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderCancellationDecisionNotification;
use App\Services\VariantInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCancellationRequestController extends Controller
{
    public function index()
    {
        $requests = OrderCancellationRequest::query()
            ->with(['order', 'user', 'reviewer'])
            ->latest()
            ->paginate(20);

        return view('admin.order-cancellation-requests.index', compact('requests'));
    }

    public function show(OrderCancellationRequest $cancellationRequest)
    {
        $cancellationRequest->load(['order.items', 'user', 'reviewer']);

        auth()->user()->unreadNotifications()
            ->where('type', \App\Notifications\OrderCancellationRequestedNotification::class)
            ->where('data->cancellation_request_id', $cancellationRequest->id)
            ->update(['read_at' => now()]);

        return view('admin.order-cancellation-requests.show', compact('cancellationRequest'));
    }

    public function decide(
        Request $request,
        OrderCancellationRequest $cancellationRequest
    ) {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'reason_details' => [
                'required_if:reason_code,other',
                'nullable',
                'string',
                'max:1500',
            ],
        ]);
    
        /*
         * Penolakan permintaan tidak memerlukan perubahan status order
         * atau panggilan ke provider pembayaran.
         */
        if ($validated['decision'] === 'rejected') {
            $result = DB::transaction(function () use (
                $cancellationRequest,
                $validated
            ) {
                $lockedRequest = OrderCancellationRequest::query()
                    ->whereKey($cancellationRequest->id)
                    ->lockForUpdate()
                    ->firstOrFail();
    
                if ($lockedRequest->status !== 'pending') {
                    return 'already_reviewed';
                }
    
                $lockedRequest->update([
                    'status' => 'rejected',
                    'admin_note' => $validated['admin_note'] ?? null,
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now(),
                ]);
    
                return $lockedRequest->id;
            });
    
            if ($result !== $cancellationRequest->id) {
                return back()->with(
                    'error',
                    'Permintaan ini sudah ditinjau sebelumnya.'
                );
            }
    
            $cancellationRequest->refresh()->load('order');
    
            \App\Models\User::find($cancellationRequest->user_id)
                ?->notify(
                    new \App\Notifications\OrderCancellationDecisionNotification(
                        $cancellationRequest
                    )
                );
    
            return redirect()
                ->route(
                    'admin.order-cancellation-requests.show',
                    $cancellationRequest
                )
                ->with('success', 'Permintaan pembatalan ditolak.');
        }
    
        /*
         * Ambil snapshot awal. Pemeriksaan status request yang otoritatif
         * tetap dilakukan kembali di dalam transaksi.
         */
        $cancellationRequest->loadMissing('order');
    
        $order = $cancellationRequest->order;
    
        if (! $order) {
            return back()->with('error', 'Pesanan tidak ditemukan.');
        }
    
        $payment = $order->payment()->first();
    
        $stripeCancellationConfirmed = false;
        $midtransCancellationConfirmed = false;
        $paymentSucceeded = false;
    
        /*
         * PRE-FLIGHT PROVIDER
         *
         * Jangan menahan database lock ketika menunggu jaringan.
         * Pembayaran succeeded dialihkan ke refund, bukan cancel provider.
         */
        if ($payment) {
            if ($payment->status === 'succeeded') {
                $paymentSucceeded = true;
            } elseif (in_array(
                $payment->status,
                ['failed', 'expired', 'canceled'],
                true
            )) {
                // Pembayaran sudah terminal.
            } elseif ($payment->provider === 'stripe') {
                if (! $payment->stripe_payment_intent_id) {
                    return back()->with(
                        'error',
                        'PaymentIntent Stripe tidak ditemukan. Periksa transaksi sebelum menyetujui pembatalan.'
                    );
                }
    
                try {
                    $stripe = app(
                        \App\Services\StripePaymentService::class
                    );
    
                    $intent = $stripe->retrieve(
                        $payment->stripe_payment_intent_id
                    );
    
                    if ($intent->status === 'succeeded') {
                        $paymentSucceeded = true;
                    } elseif ($intent->status === 'canceled') {
                        $stripeCancellationConfirmed = true;
                    } else {
                        $intent = $stripe->cancel(
                            $payment->stripe_payment_intent_id
                        );
    
                        if ($intent->status === 'succeeded') {
                            $paymentSucceeded = true;
                        } elseif ($intent->status === 'canceled') {
                            $stripeCancellationConfirmed = true;
                        } else {
                            return back()->with(
                                'error',
                                'Stripe belum mengonfirmasi pembatalan. Permintaan tetap pending agar dapat diperiksa kembali.'
                            );
                        }
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning(
                        'Cancellation approval: Stripe status/cancel failed.',
                        [
                            'order_id' => $order->id,
                            'exception' => get_class($e),
                            'message' => $e->getMessage(),
                        ]
                    );
    
                    return back()->with(
                        'error',
                        'Status Stripe belum dapat dipastikan. Permintaan tetap pending; periksa kembali sebelum mengambil keputusan.'
                    );
                }
            } elseif ($payment->provider === 'midtrans') {
                try {
                    $midtrans = app(
                        \App\Services\MidtransService::class
                    );
    
                    $status = $midtrans->getStatus(
                        $order->order_number
                    );
    
                    $transactionStatus = strtolower(
                        (string) ($status->transaction_status ?? '')
                    );
    
                    $statusCode = (string) ($status->status_code ?? '');
    
                    $fraudStatus = strtolower(
                        (string) ($status->fraud_status ?? '')
                    );
    
                    $isSuccessful =
                        $transactionStatus === 'settlement'
                        || (
                            $transactionStatus === 'capture'
                            && in_array($statusCode, ['', '200'], true)
                            && in_array($fraudStatus, ['', 'accept'], true)
                        );
    
                    if ($isSuccessful) {
                        $paymentSucceeded = true;
                    } elseif (in_array(
                        $transactionStatus,
                        ['cancel', 'expire', 'deny', 'failure'],
                        true
                    )) {
                        $midtransCancellationConfirmed = true;
                    } elseif ($transactionStatus === 'pending') {
                        $midtrans->cancelTransaction(
                            $order->order_number
                        );
    
                        $verified = $midtrans->verifyCancellation(
                            $order->order_number
                        );
    
                        $verifiedStatus = strtolower(
                            (string) ($verified->transaction_status ?? '')
                        );
    
                        $verifiedCode = (string) (
                            $verified->status_code ?? ''
                        );
    
                        $verifiedFraud = strtolower(
                            (string) ($verified->fraud_status ?? '')
                        );
    
                        $verifiedSucceeded =
                            $verifiedStatus === 'settlement'
                            || (
                                $verifiedStatus === 'capture'
                                && in_array($verifiedCode, ['', '200'], true)
                                && in_array($verifiedFraud, ['', 'accept'], true)
                            );
    
                        if ($verifiedSucceeded) {
                            $paymentSucceeded = true;
                        } elseif (in_array(
                            $verifiedStatus,
                            ['cancel', 'expire', 'deny', 'failure'],
                            true
                        )) {
                            $midtransCancellationConfirmed = true;
                        } else {
                            return back()->with(
                                'error',
                                'Midtrans belum memastikan hasil pembatalan. Permintaan tetap pending untuk pemeriksaan ulang.'
                            );
                        }
                    } else {
                        return back()->with(
                            'error',
                            'Status Midtrans belum dikenali. Permintaan tetap pending untuk pemeriksaan manual.'
                        );
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning(
                        'Cancellation approval: Midtrans status/cancel failed.',
                        [
                            'order_id' => $order->id,
                            'exception' => get_class($e),
                            'message' => $e->getMessage(),
                        ]
                    );
    
                    return back()->with(
                        'error',
                        'Status atau pembatalan Midtrans belum dapat dipastikan. Permintaan tetap pending untuk pemeriksaan ulang.'
                    );
                }
            } elseif (
                $payment->payment_method !== 'cod'
                || $payment->status !== 'pending'
            ) {
                return back()->with(
                    'error',
                    'Metode pembayaran ini belum memiliki jalur pembatalan otomatis. Permintaan tetap pending untuk penyelesaian manual.'
                );
            }
        }
    
        /*
         * COMMIT KE DATABASE
         *
         * Validasi ulang status request dan order karena status dapat
         * berubah ketika provider sedang dihubungi.
         */
        $result = DB::transaction(function () use (
            $cancellationRequest,
            $validated,
            $stripeCancellationConfirmed,
            $midtransCancellationConfirmed,
            $paymentSucceeded
        ) {
            $lockedRequest = OrderCancellationRequest::query()
                ->whereKey($cancellationRequest->id)
                ->lockForUpdate()
                ->firstOrFail();
    
            if ($lockedRequest->status !== 'pending') {
                return 'already_reviewed';
            }
    
            $order = Order::query()
                ->whereKey($lockedRequest->order_id)
                ->lockForUpdate()
                ->firstOrFail();
    
            $payment = $order->payment()
                ->lockForUpdate()
                ->first();
    
            if ($order->status !== 'pending') {
                return 'order_not_pending';
            }
    
            if ($payment) {
                if ($paymentSucceeded) {
                    /*
                     * Jangan mengubah succeeded menjadi canceled.
                     * Status sukses dipertahankan untuk proses refund.
                     */
                    $payment->status = 'succeeded';
                    $payment->paid_at = $payment->paid_at ?? now();
                    $payment->save();
                } elseif (in_array(
                    $payment->status,
                    ['failed', 'expired', 'canceled'],
                    true
                )) {
                    // Sudah terminal; tidak perlu membatalkan lagi.
                } elseif ($payment->provider === 'stripe') {
                    if (! $stripeCancellationConfirmed) {
                        return 'payment_not_safe';
                    }
    
                    $payment->status = 'canceled';
                    $payment->save();
                } elseif ($payment->provider === 'midtrans') {
                    if (! $midtransCancellationConfirmed) {
                        return 'payment_not_safe';
                    }
    
                    $payment->status = 'canceled';
                    $payment->save();
                } elseif (
                    $payment->payment_method === 'cod'
                    && $payment->status === 'pending'
                ) {
                    $payment->status = 'canceled';
                    $payment->transaction_status = 'canceled';
                    $payment->save();
                } else {
                    return 'payment_not_safe';
                }
            }
    
            $items = $order->items()
                ->orderBy('product_id')
                ->orderBy('product_variant_id')
                ->orderBy('id')
                ->get();
    
            $productIds = $items->pluck('product_id')
                ->filter()
                ->unique()
                ->sort()
                ->values();
    
            $products = \App\Models\Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
    
            $inventoryService = app(
                \App\Services\VariantInventoryService::class
            );
    
            foreach ($items as $item) {
                $product = $products->get($item->product_id);
    
                if (! $product) {
                    throw new \RuntimeException(
                        "Produk {$item->product_id} tidak ditemukan saat memulihkan stok."
                    );
                }
    
                $inventoryService->restoreForOrder(
                    $product,
                    $item->product_variant_id
                        ? (int) $item->product_variant_id
                        : null,
                    (int) $item->quantity,
                    $order,
                    (int) $item->id,
                );
            }
    
            $order->update(['status' => 'canceled']);
    
            $lockedRequest->update([
                'status' => 'approved',
                'admin_note' => $validated['admin_note'] ?? null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
    
            return $lockedRequest->id;
        });
    
        if ($result !== $cancellationRequest->id) {
            $message = match ($result) {
                'already_reviewed' =>
                    'Permintaan ini sudah ditinjau sebelumnya.',
                'order_not_pending' =>
                    'Pesanan tidak lagi pending. Periksa status terbaru sebelum melanjutkan.',
                'payment_not_safe' =>
                    'Pembayaran belum dapat dipastikan terminal. Permintaan tetap pending untuk pemeriksaan ulang.',
                default =>
                    'Permintaan pembatalan belum dapat diselesaikan.',
            };
    
            return back()->with('error', $message);
        }
    
        /*
         * Refund diproses setelah commit agar tidak menahan database lock
         * selama komunikasi dengan provider.
         */
        $cancellationRequest->refresh()->load('order.payment');
    
        $order = $cancellationRequest->order;
        $payment = $order?->payment;
    
        if (
            $order?->status === 'canceled'
            && $payment?->status === 'succeeded'
        ) {
            try {
                app(\App\Services\RefundService::class)
                    ->requestLateCanceledOrderRefund($order, $payment);
            } catch (\Throwable $e) {
                /*
                 * Order sudah canceled. Catat kegagalan pembuatan permintaan
                 * refund agar dapat diperiksa dan diperbaiki secara operasional.
                 */
                \Illuminate\Support\Facades\Log::error(
                    'Cancellation approved but refund request creation failed.',
                    [
                        'order_id' => $order->id,
                        'payment_id' => $payment->id,
                        'cancellation_request_id' => $cancellationRequest->id,
                        'exception' => get_class($e),
                        'message' => $e->getMessage(),
                    ]
                );
    
                return redirect()
                    ->route(
                        'admin.order-cancellation-requests.show',
                        $cancellationRequest
                    )
                    ->with(
                        'warning',
                        'Pembatalan disetujui, tetapi permintaan refund belum berhasil dibuat. Perlu tindak lanjut admin.'
                    );
            }
        }
    
        \App\Models\User::find($cancellationRequest->user_id)
            ?->notify(
                new \App\Notifications\OrderCancellationDecisionNotification(
                    $cancellationRequest
                )
            );
    
        return redirect()
            ->route(
                'admin.order-cancellation-requests.show',
                $cancellationRequest
            )
            ->with(
                'success',
                $payment?->status === 'succeeded'
                    ? 'Pembatalan disetujui. Permintaan refund telah dibuat untuk ditinjau.'
                    : 'Permintaan pembatalan disetujui dan pesanan dibatalkan.'
            );
    }
}