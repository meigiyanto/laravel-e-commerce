<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Daftar order milik user yang sedang login.
     */
    public function index()
    {
        $orders = Order::query()->where('user_id', auth()->id())->latest()->paginate(10);

        return view('orders.index', compact('orders'));
    }

    /**
     * Detail satu order.
     */
    public function show(Order $order)
    {
        // User hanya boleh melihat order miliknya sendiri.
        abort_unless($order->user_id === auth()->id(),403);
        $order->load(['items.product']);
        return view('orders.show', compact('order'));
    }
}
