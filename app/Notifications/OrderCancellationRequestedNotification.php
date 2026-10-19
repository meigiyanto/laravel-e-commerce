<?php

namespace App\Notifications;

use App\Models\OrderCancellationRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderCancellationRequestedNotification extends Notification
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

        return [
            'kind' => 'order_cancellation_requested',
            'cancellation_request_id' => $request->id,
            'order_id' => $request->order_id,
            'order_number' => $request->order?->order_number,
            'message' => 'Permintaan pembatalan pesanan baru menunggu peninjauan.',
            'url' => route('admin.order-cancellation-requests.show', $request),
        ];
    }
}
