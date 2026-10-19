<?php

namespace App\Notifications;

use App\Models\OrderCancellationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderCancellationDecisionNotification extends Notification
{
    use Queueable;

    public function __construct(public OrderCancellationRequest $cancellationRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $request = $this->cancellationRequest->loadMissing('order');
        $approved = $request->status === 'approved';

        return [
            'kind' => 'order_cancellation_decision',
            'cancellation_request_id' => $request->id,
            'order_id' => $request->order_id,
            'order_number' => $request->order?->order_number,
            'status' => $request->status,
            'message' => $approved
                ? 'Permintaan pembatalan pesanan Anda disetujui.'
                : 'Permintaan pembatalan pesanan Anda ditolak.',
            'admin_note' => $request->admin_note,
            'url' => route('orders.show', $request->order_id),
        ];
    }
}
