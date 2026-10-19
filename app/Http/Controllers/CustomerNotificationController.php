<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class CustomerNotificationController extends Controller
{
    public function index()
    {
        $notifications = auth()->user()->notifications()->latest()->paginate(20);
        return view('storefront.notifications.index', compact('notifications'));
    }

    public function markRead(DatabaseNotification $notification)
    {
        abort_unless($notification->notifiable_id === auth()->id()
            && $notification->notifiable_type === auth()->user()::class, 404);

        $notification->markAsRead();
        $url = $notification->data['url'] ?? route('notifications.index');

        return redirect()->to($url);
    }
}
