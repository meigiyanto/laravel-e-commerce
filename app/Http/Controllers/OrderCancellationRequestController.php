<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderCancellationRequest;
use App\Models\User;
use App\Notifications\OrderCancellationRequestedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderCancellationRequestController extends Controller
{
    public function store(Request $request, Order $order)
    {
        abort_unless((int) $order->user_id === (int) auth()->id(), 403);

        $reasonLabels = [
            'changed_mind' => 'Berubah pikiran',
            'wrong_item' => 'Salah memilih produk / varian',
            'wrong_quantity' => 'Jumlah pesanan tidak sesuai',
            'wrong_address' => 'Alamat pengiriman salah',
            'delivery_too_long' => 'Estimasi pengiriman terlalu lama',
            'payment_issue' => 'Kendala pembayaran',
            'duplicate_order' => 'Pesanan terbuat dua kali',
            'found_better_price' => 'Menemukan harga yang lebih sesuai',
            'other' => 'Alasan lainnya',
        ];

        $validated = $request->validate([
            'reason_code' => ['required', 'string', 'in:' . implode(',', array_keys($reasonLabels))],
            'reason_details' => ['nullable', 'string', 'max:1500'],
        ]);

        $reason = $reasonLabels[$validated['reason_code']]
            . (! empty($validated['reason_details']) ? ': ' . trim($validated['reason_details']) : '');

        $cancellationRequest = DB::transaction(function () use ($order, $reason) {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless((int) $lockedOrder->user_id === (int) auth()->id(), 403);

            if ($lockedOrder->status !== 'pending') {
                throw ValidationException::withMessages([
                    'reason' => 'Permintaan hanya dapat diajukan untuk pesanan yang masih pending.',
                ]);
            }

            $existing = OrderCancellationRequest::query()
                ->where('order_id', $lockedOrder->id)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'reason' => 'Permintaan pembatalan untuk pesanan ini masih menunggu peninjauan.',
                ]);
            }

            return OrderCancellationRequest::create([
                'order_id' => $lockedOrder->id,
                'user_id' => auth()->id(),
                'status' => 'pending',
                'reason' => $reason,
            ]);
        });

        $admins = User::query()->whereIn('role', [User::ROLE_ADMIN, User::ROLE_STAFF])->get();
        foreach ($admins as $admin) {
            $admin->notify(new OrderCancellationRequestedNotification($cancellationRequest));
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Permintaan pembatalan telah dikirim untuk ditinjau admin.');
    }
}
