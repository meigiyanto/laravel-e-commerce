<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Payment;
use App\Services\VariantInventoryService;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Daftar order milik user yang sedang login.
     */
    public function index()
    {
        $orders = Order::query()
            ->where('user_id', auth()->id())
            ->with('payment')
            ->latest()
            ->paginate(10);

        return view('storefront.orders.index', compact('orders'));
    }

    /**
     * Detail satu order.
     */
    public function show(Order $order)
    {
        /*
         * User hanya boleh melihat order miliknya sendiri.
         */
        abort_unless($order->user_id === auth()->id(), 403);

        $order->load([
            'items.product',
            'payment',
            'cancellationRequests'
        ]);

        /*
         * Jika pembayaran Stripe masih pending
         * tetapi PaymentIntent sudah ada,
         * sinkronkan status dari Stripe.
         *
         * Dengan ini halaman /orders/{order}
         * tidak akan terus menampilkan pending
         * jika Stripe sebenarnya sudah succeeded.
         */
        if (
            $order->payment &&
            $order->payment->payment_method === 'stripe' &&
            $order->payment->status !== 'succeeded' &&
            $order->payment->stripe_payment_intent_id
        ) {
            app(PaymentController::class)
                ->syncPaymentFromStripe(
                    $order,
                    $order->payment->stripe_payment_intent_id
                );

            /*
             * Ambil ulang data terbaru setelah
             * sinkronisasi.
             */
            $order->refresh();

            $order->load([
                'items.product',
                'payment',
            ]);
        }
        
        $canCancelOrder = $this->customerCanCancel($order);

        return view('storefront.orders.show', compact('order', 'canCancelOrder'));
    }
    
    /**
     * Menentukan apakah customer masih boleh mengajukan
     * permintaan pembatalan untuk pesanan ini.
     *
     * Aturan:
     * - Pesanan harus berstatus pending.
     * - Tidak boleh ada permintaan yang masih pending.
     * - Pesanan yang permintaan pembatalannya sudah approved
     *   tidak boleh mengajukan permintaan lagi.
     *
     * Penolakan oleh admin tidak otomatis membatalkan pesanan.
     * Pesanan yang masih pending dapat diajukan kembali.
     *
     * Ini hanya menentukan hak pengajuan di storefront.
     * Keputusan akhir tetap divalidasi oleh controller pembatalan.
     */
    private function customerCanCancel(Order $order): bool
    {
        if ($order->status !== 'pending') {
            return false;
        }
    
        $requests = $order->relationLoaded('cancellationRequests')
            ? $order->cancellationRequests
            : $order->cancellationRequests()->get();
    
        return ! $requests->contains(
            fn ($request) => in_array(
                $request->status,
                ['pending', 'approved'],
                true
            )
        );
    }
}
