<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
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

        if (!$cart || $cart->items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Keranjang kamu masih kosong.');
        }

        $subtotal = $cart->items->sum(
            fn ($item) => $item->product->price * $item->quantity
        );

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
                'in:stripe,cod',
            ],
        ]);

        $order = DB::transaction(function () use ($validated) {

            $cart = Cart::where('user_id', auth()->id())
                ->lockForUpdate()
                ->first();

            if (!$cart) {
                abort(404, 'Keranjang tidak ditemukan.');
            }

            $cart->load('items.product');

            if ($cart->items->isEmpty()) {
                abort(422, 'Keranjang kamu masih kosong.');
            }

            $productIds = $cart->items
                ->pluck('product_id')
                ->unique()
                ->values();

            $products = \App\Models\Product::whereIn(
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

                if (!$product) {
                    throw new \RuntimeException(
                        "Produk {$cartItem->product_id} tidak ditemukan."
                    );
                }

                if ($cartItem->quantity > $product->stock) {
                    throw new \RuntimeException(
                        "Stok produk {$product->name} tidak mencukupi."
                    );
                }

                /*
                 * Harga diambil dari database.
                 * Bukan dari request browser.
                 */
                $subtotal +=
                    $product->price *
                    $cartItem->quantity;
            }

            $shippingCost = 0;

            /*
             * Total authoritative.
             */
            $total = $subtotal + $shippingCost;

            do {
                $orderNumber =
                    'ORD-' .
                    now()->format('YmdHis') .
                    '-' .
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
                'notes' => $validated['notes'] ?? null,                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total' => $total,
            ]);

            Payment::create([
                'order_id' => $order->id,
                'provider' => $validated['payment_method'] === 'cod' ? 'cod' : 'stripe',
                'currency' => 'IDR',
                'status' => 'pending',
                'payment_method' => $validated['payment_method'],
                'payment_type' => $validated['payment_method'] === 'cod' ? 'cash_on_delivery' : 'card',
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
            return redirect()->route('orders.show', $order)->with('success','Pesanan COD berhasil dibuat. Pembayaran dilakukan saat pesanan diterima.');
        }

        return redirect()->route('payment.show', $order)->with('success','Pesanan berhasil dibuat. Silakan lanjutkan pembayaran.');
    }

    public function success(Order $order)
    {
        abort_unless($order->user_id === auth()->id(),403);
        $order->load(['items.product', 'payment']);

        /*
         * Jangan menganggap redirect dari Stripe
         * sebagai bukti pembayaran berhasil.
         *
         * Webhook adalah sumber kebenaran.
         */
        if (!$order->payment || $order->payment->status !== 'succeeded') {
            return redirect()->route('payment.show', $order);
        }

        return view('storefront.checkout-success', compact('order'));
    }
}
