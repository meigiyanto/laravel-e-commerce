<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    public function index()
    {
        $cart = Cart::where('user_id', auth()->id())
            ->with([
                'items.product.category',
                'items.product.subCategory',
            ])
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Your cart still empty.');
        }

        $subtotal = $cart->items->sum(fn ($item) => $item->product->price * $item->quantity);

        $shippingCost = 0;

        $total = $subtotal + $shippingCost;

        return view('storefront.checkout', compact(
            'cart',
            'subtotal',
            'shippingCost',
            'total'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => [
                'required',
                'string',
                'max:255',
            ],

            'phone' => [
                'required',
                'string',
                'max:30',
            ],

            'shipping_address' => [
                'required',
                'string',
                'max:2000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'payment_method' => [
                'required',
                'in:stripe,midtrans,cod',
            ],
        ]);

        $order = DB::transaction(function () use ($validated) {

            $cart = Cart::where('user_id', auth()->id())
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                abort(404, 'Cart not founs.');
            }

            $cart->load('items.product');

            if ($cart->items->isEmpty()) {
                abort(422, 'Your cart still empty.');
            }

            $productIds = $cart->items
                ->pluck('product_id')
                ->unique()
                ->values();

            $products = Product::whereIn(
                'id',
                $productIds
            )
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;

            foreach ($cart->items as $cartItem) {

                $product = $products->get(
                    $cartItem->product_id
                );

                if (! $product) {
                    throw new \RuntimeException(
                        "Product {$cartItem->product_id} not found."
                    );
                }

                if ($cartItem->quantity > $product->stock) {
                    throw new \RuntimeException(
                        "Product stock {$product->name} not enough."
                    );
                }

                /*
                 * Harga diambil dari database.
                 * Bukan dari request browser.
                 */
                $subtotal += $product->price * $cartItem->quantity;
            }

            $shippingCost = 0;

            /*
             * Total authoritative.
             */
            $total = $subtotal + $shippingCost;

            do {
                $orderNumber =
                    'ORD-'.
                    now()->format('YmdHis').
                    '-'.
                    strtoupper(Str::random(5));

            } while (
                Order::where(
                    'order_number',
                    $orderNumber
                )->exists()
            );

            $order = Order::create([
                'user_id' => auth()->id(),
                'order_number' => $orderNumber,
                'status' => 'pending',
                'customer_name' => $validated['customer_name'],
                'phone' => $validated['phone'],
                'shipping_address' => $validated['shipping_address'],
                'notes' => $validated['notes'] ?? null,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total' => $total,
            ]);

            Payment::create([
                'order_id' => $order->id,
                'provider' => $validated['payment_method'],
                'currency' => 'IDR',
                'status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'payment_type' => match (
                    $validated['payment_method']
                ) {
                    'stripe' => 'card',
                    'midtrans' => 'midtrans_snap',
                    'cod' => 'cash_on_delivery',
                },
                'transaction_status' => 'pending',
                'gross_amount' => $order->total,
            ]);

            foreach ($cart->items as $cartItem) {
                $product = $products->get($cartItem->product_id);
                $itemSubtotal = $product->price * $cartItem->quantity;

                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'price' => $product->price,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => $itemSubtotal,
                ]);

                $product->decrement('stock', $cartItem->quantity);
            }

            $cart->items()->delete();

            return $order;
        });

        if ($validated['payment_method'] === 'cod') {
            return redirect()->route('orders.show', $order)->with('success', 'COD order successfully created. Payment is made when the order is received by the recipient.');
        }

        if ($validated['payment_method'] === 'midtrans') {
            return redirect()->route('payment.midtrans.show', $order)->with('success', 'Your order has been successfully placed. Please proceed with payment via Midtrans. ');
        }

        return redirect()->route('payment.show', $order)->with('success', 'Your order has been successfully placed. Please proceed with payment via Stripe.');
    }

    public function success(Order $order)
    {
        abort_unless($order->user_id === auth()->id(), 403);
        $order->load(['items.product', 'payment']);
        $payment = $order->payment;

        /*
         * Order tanpa payment tidak boleh dianggap berhasil.
         */
        if (! $payment) {
            return redirect()->route('orders.show', $order)->with('error', 'Payment data for this order was not found.');
        }

        /*
         * COD:
         * Tidak membutuhkan payment gateway.
         */
        if ($payment->provider === 'cod') {
            return view('storefront.checkout-success', compact('order')
            );
        }

        /*
         * Stripe:
         * Pertahankan mekanisme sinkronisasi Stripe
         * yang sudah digunakan sebelumnya.
         */
        if (
            $payment->provider === 'stripe' &&
            $payment->status !== 'succeeded' &&
            $payment->stripe_payment_intent_id
        ) {
            app(PaymentController::class)
                ->syncPaymentFromStripe($order, $payment->stripe_payment_intent_id);

            $order->refresh();

            $order->load([
                'items.product',
                'payment',
            ]);

            $payment = $order->payment;
        }

        /*
         * Midtrans:
         *
         * Jika browser callback belum membuat status
         * menjadi succeeded, cek status langsung ke Midtrans.
         *
         * Webhook/HTTP Notification tetap menjadi mekanisme
         * utama untuk sinkronisasi pembayaran.
         */
        if (
            $payment->provider === 'midtrans' &&
            $payment->status !== 'succeeded'
        ) {
            return redirect()
                ->route('payment.midtrans.show', $order)
                ->with('error', 'Midtrans payment has not been successfully verified.');
        }

        /*
         * Pembayaran sudah berhasil diverifikasi.
         */
        if ($payment->status === 'succeeded') {
            return view('storefront.checkout-success', compact('order')
            );
        }

        /*
         * Pembayaran gagal, expired, cancelled,
         * atau masih pending.
         *
         * Jangan tampilkan halaman success.
         */
        return redirect()
            ->route('orders.show', $order)
            ->with('error', 'Payment was unsuccessful. Please check your order status. '
            );
    }
}
