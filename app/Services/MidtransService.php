<?php

namespace App\Services;

use App\Models\Order;
use Midtrans\Config;
use Midtrans\Snap;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey = config('services.midtrans.server_key');

        Config::$isProduction = config(
            'services.midtrans.is_production'
        );

        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createSnapToken(Order $order): string
    {
        $order->load('items');

        $itemDetails = [];

        foreach ($order->items as $item) {
            $itemDetails[] = [
                'id' => (string) $item->product_id,
                'price' => (int) $item->price,
                'quantity' => $item->quantity,
                'name' => $item->product_name,
            ];
        }

        if ((float) $order->shipping_cost > 0) {
            $itemDetails[] = [
                'id' => 'shipping',
                'price' => (int) $order->shipping_cost,
                'quantity' => 1,
                'name' => 'Ongkos Kirim',
            ];
        }

        $params = [
            'transaction_details' => [
                'order_id' => $order->order_number,
                'gross_amount' => (int) $order->total,
            ],

            'item_details' => $itemDetails,

            'customer_details' => [
                'first_name' => $order->customer_name,
                'phone' => $order->phone,
            ],
        ];

        return Snap::getSnapToken($params);
    }
}
