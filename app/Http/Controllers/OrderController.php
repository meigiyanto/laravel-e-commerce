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
     * Aturan kelayakan pembatalan langsung oleh customer.
     *
     * Pembatalan langsung hanya tersedia untuk pesanan pending
     * yang belum dibayar atau pembayaran COD yang belum selesai.
     * Pembayaran online yang masih aktif tidak boleh dibatalkan
     * melalui endpoint ini.
     */
    private function customerCanCancel(Order $order): bool
    {
        if ($order->status !== 'pending') {
            return false;
        }
    
        $payment = $order->payment;
    
        // Pesanan tanpa data pembayaran belum pernah dinyatakan lunas.
        if (! $payment) {
            return true;
        }
    
        // Pembayaran berhasil harus melalui kebijakan refund.
        if ($payment->status === 'succeeded') {
            return false;
        }
    
        // COD belum dibayar dan tidak memiliki transaksi gateway aktif.
        if (
            $payment->payment_method === 'cod'
            && $payment->status === 'pending'
        ) {
            return true;
        }
    
        // Status terminal boleh dibersihkan jika pesanan masih pending.
        return in_array(
            $payment->status,
            ['failed', 'expired', 'canceled'],
            true
        );
    }
    
    /**
     * Membatalkan pesanan milik customer yang sedang login.
     */
    public function cancel(Order $order)
    {
        abort_unless(
            (int) $order->user_id === (int) auth()->id(),
            403
        );
    
        $result = DB::transaction(function () use ($order) {
            // Selalu periksa status terbaru di bawah row lock.
            $lockedOrder = Order::query()
                ->whereKey($order->id)
                ->lockForUpdate()
                ->firstOrFail();
    
            abort_unless(
                (int) $lockedOrder->user_id === (int) auth()->id(),
                403
            );
    
            $payment = Payment::query()
                ->where('order_id', $lockedOrder->id)
                ->lockForUpdate()
                ->first();
    
            $lockedOrder->setRelation('payment', $payment);
    
            if (! $this->customerCanCancel($lockedOrder)) {
                return 'not_cancellable';
            }
    
            // Batalkan status pembayaran COD yang belum dibayar.
            if (
                $payment
                && $payment->payment_method === 'cod'
                && $payment->status === 'pending'
            ) {
                $payment->update([
                    'status' => 'canceled',
                ]);
            }
    
            $lockedOrder->update([
                'status' => 'canceled',
            ]);
    
            // Kunci produk secara konsisten sebelum memulihkan stok.
            $items = $lockedOrder->items()->get();
    
            $productIds = $items
                ->pluck('product_id')
                ->filter()
                ->unique()
                ->sort()
                ->values();
    
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
    
            $inventoryService = app(VariantInventoryService::class);
    
            foreach ($items as $item) {
                $product = $products->get($item->product_id);
    
                if (! $product) {
                    continue;
                }
    
                $inventoryService->restoreForOrder(
                    $product,
                    $item->product_variant_id
                        ? (int) $item->product_variant_id
                        : null,
                    (int) $item->quantity,
                    $lockedOrder
                );
            }
    
            return 'canceled';
        });
    
        if ($result !== 'canceled') {
            return redirect()
                ->route('orders.show', $order)
                ->with(
                    'error',
                    'Pesanan ini tidak memenuhi syarat untuk dibatalkan. Periksa status pesanan dan pembayaran.'
                );
        }
    
        return redirect()
            ->route('orders.show', $order)
            ->with(
                'success',
                'Pesanan berhasil dibatalkan.'
            );
    }

}
