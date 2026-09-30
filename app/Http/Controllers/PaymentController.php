<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MidtransService;

class PaymentController extends Controller
{
    public function show(Order $order, MidtransService $midtrans)
    {
        abort_unless(
            $order->user_id === auth()->id(),
            403
        );

        $order->load('payment');

        if ($order->payment->transaction_status === 'settlement' || ($order->payment->transaction_status === 'capture' && $order->payment->fraud_status === 'accept')
        ) {
            return redirect()->route('checkout.success', $order);
        }


        $snapToken = $midtrans->createSnapToken($order);

        return view('storefront.payment', compact('order', 'snapToken')
        );
    }
}
