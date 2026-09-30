<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\MidtransService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function show(Order $order, MidtransService $midtrans)
    {
        abort_unless(
            $order->user_id === auth()->id(),
            403
        );

        $order->load('payment');

        if (!$order->payment) {
            abort(404, 'Payment tidak ditemukan.');
        }

        if ($order->payment->transaction_status === 'settlement') {
            return redirect()
                ->route('checkout.success', $order);
        }

        $snapToken = $midtrans->createSnapToken($order);

        return view(
            'storefront.payment',
            compact('order', 'snapToken')
        );
    }
}
