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

    public function decide(Request $request, OrderCancellationRequest $cancellationRequest)
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'reason_details' => ['required_if:reason_code,other', 'string' 'max:1500'],
        ]);

        $result = DB::transaction(function () use ($cancellationRequest, $validated) {
            $lockedRequest = OrderCancellationRequest::query()
                ->whereKey($cancellationRequest->id)->lockForUpdate()->firstOrFail();

            if ($lockedRequest->status !== 'pending') {
                return 'already_reviewed';
            }

            $order = Order::query()->whereKey($lockedRequest->order_id)->lockForUpdate()->firstOrFail();
            $payment = Payment::query()->where('order_id', $order->id)->lockForUpdate()->first();

            if ($validated['decision'] === 'approved') {
                if ($order->status !== 'pending') {
                    return 'order_not_pending';
                }
                
                if ($payment && $payment->status === 'succeeded') {
                    return 'payment_succeeded';
                }

                // Do not approve while Stripe/Midtrans may still complete payment.
                if ($payment && ! (
                    $payment->payment_method === 'cod' && $payment->status === 'pending'
                ) && ! in_array($payment->status, ['failed', 'expired', 'canceled'], true)) {
                    return 'payment_not_safe';
                }

                $items = $order->items()->orderBy('id')->get();
                $productIds = $items->pluck('product_id')->filter()->unique()->sort()->values();
                $products = Product::query()->whereIn('id', $productIds)
                    ->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $inventoryService = app(VariantInventoryService::class);

                foreach ($items as $item) {
                    $product = $products->get($item->product_id);
                    if (! $product) {
                        continue;
                    }
                    $inventoryService->restoreForOrder(
                        $product,
                        $item->product_variant_id ? (int) $item->product_variant_id : null,
                        (int) $item->quantity,
                        $order
                    );
                }

                if ($payment && $payment->payment_method === 'cod' && $payment->status === 'pending') {
                    $payment->update(['status' => 'canceled', 'transaction_status' => 'canceled']);
                }

                $order->update(['status' => 'canceled']);
            }

            $lockedRequest->update([
                'status' => $validated['decision'],
                'admin_note' => $validated['admin_note'] ?? null,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);

            return $lockedRequest->id;
        });

        if ($result !== $cancellationRequest->id) {
            $message = match ($result) {
                'already_reviewed' => 'Permintaan ini sudah ditinjau sebelumnya.',
                'order_not_pending' => 'Pesanan tidak lagi berstatus pending; permintaan tidak dapat disetujui.',
                'payment_not_safe' => 'Pembayaran online masih aktif atau belum aman untuk dibatalkan. Periksa status provider terlebih dahulu.',
                'payment_succeeded' => 'Pembayaran sudah berhasil. Gunakan alur refund, bukan pembatalan langsung.',
                default => 'Permintaan tidak dapat diproses.',
            };

            return back()->with('error', $message);
        }

        $cancellationRequest->refresh()->load('order');
        $customer = User::find($cancellationRequest->user_id);
        $customer?->notify(new OrderCancellationDecisionNotification($cancellationRequest));

        return redirect()->route('admin.order-cancellation-requests.show', $cancellationRequest)
            ->with('success', 'Keputusan permintaan pembatalan berhasil disimpan.');
    }
}