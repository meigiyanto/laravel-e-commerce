<?php

namespace App\Services;

use App\Models\Order;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    public function __construct()
    {
        Config::$serverKey =
            config('midtrans.server_key');

        Config::$isProduction =
            config('midtrans.is_production');

        Config::$isSanitized =
            config('midtrans.is_sanitized', true);

        Config::$is3ds =
            config('midtrans.is_3ds', true);
    }

    /**
     * Membuat Snap Token untuk order.
     */
    public function createSnapToken(Order $order): string
    {
        $order->loadMissing(['items', 'user']);

        $params = [
            'transaction_details' => [
                'order_id' => $order->order_number,

                'gross_amount' => (int) round(
                    (float) $order->total
                ),
            ],

            'item_details' => $order->items
                ->map(function ($item) {
                    return [
                        'id' => (string) $item->product_id,

                        'price' => (int) round(
                            (float) $item->price
                        ),

                        'quantity' => (int) $item->quantity,

                        'name' => $item->product_name,
                    ];
                })
                ->values()
                ->all(),

            'customer_details' => [
                'first_name' => $order->customer_name,

                'email' => $order->user?->email,

                'phone' => $order->phone,

                'shipping_address' => [
                    'address' => $order->shipping_address,
                ],
            ],

            'callbacks' => [
                'finish' => route('checkout.success', $order),

                'error' => route(
                    'payment.midtrans.show',
                    $order
                ),
            ],
        ];

        return Snap::getSnapToken(
            $params
        );
    }

    /**
     * Mengambil status transaksi Midtrans.
     */
    public function getStatus(
        string $identifier
    ): object {
        return Transaction::status(
            $identifier
        );
    }

    /**
     * Verifikasi signature notification Midtrans.
     *
     * SHA512(
     *     order_id +
     *     status_code +
     *     gross_amount +
     *     ServerKey
     * )
     */
    public function verifyNotification(
        array $notification
    ): bool {
        $orderId =
            (string) $notification['order_id'];

        $statusCode =
            (string) $notification['status_code'];

        $grossAmount =
            (string) $notification['gross_amount'];

        $serverKey =
            (string) config(
                'midtrans.server_key'
            );

        $signature = hash(
            'sha512',
            $orderId.
            $statusCode.
            $grossAmount.
            $serverKey
        );

        return hash_equals(
            $signature,
            (string) $notification['signature_key']
        );
    }

    public function refund(
    string $identifier,
    int $amount,
    string $refundKey,
    ?string $reason = null
    ): object {
        $params = [
            'refund_key' => $refundKey,
            'amount' => $amount,
        ];

        if ($reason !== null) {
            $params['reason'] = $reason;
        }

        return Transaction::refund(
            $identifier,
            $params
        );
    }
}
