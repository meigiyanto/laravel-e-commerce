<?php

namespace App\Http\Controllers;

use App\Models\Order;

class PaymentController extends Controller
{
    public function createPayment(Request $request)
    {
        \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => 5000, // dalam sen (50.00 USD)
            'currency' => 'usd',
            'payment_method_types' => ['card'],
        ]);

        return response()->json([
            'clientSecret' => $paymentIntent->client_secret
        ]);
    }
}
