<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CheckoutController extends Controller
{
    /**
     * Menampilkan halaman checkout.
     */
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

        $subtotal = $cart->items->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        /*
         * Untuk sementara ongkos kirim dibuat gratis.
         * Nanti bisa diganti dengan perhitungan ongkir.
         */
        $shippingCost = 0;

        $total = $subtotal + $shippingCost;

        return view('storefront.checkout', compact(
            'cart',
            'subtotal',
            'shippingCost',
            'total'
        ));
    }

    /**
     * Membuat order.
     */
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
        ]);

        $order = DB::transaction(function () use ($validated) {

            /*
             * Lock cart agar proses checkout tidak bentrok
             * dengan request lain.
             */
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

            /*
             * Lock semua produk yang akan dibeli.
             */
            $productIds = $cart->items
                ->pluck('product_id')
                ->unique()
                ->values();

            $products = \App\Models\Product::whereIn('id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $subtotal = 0;

            /*
             * Validasi stok menggunakan data produk
             * yang sudah di-lock.
             */
            foreach ($cart->items as $cartItem) {

                $product = $products->get($cartItem->product_id);

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

                $subtotal +=
                    $product->price * $cartItem->quantity;
            }

            /*
             * Ongkir sementara gratis.
             */
            $shippingCost = 0;

            $total = $subtotal + $shippingCost;

            /*
             * Generate nomor order.
             */
            do {
                $orderNumber =
                    'ORD-' .
                    now()->format('YmdHis') .
                    '-' .
                    strtoupper(Str::random(5));

            } while (
                Order::where('order_number', $orderNumber)->exists()
            );

            /*
             * Buat order utama.
             */
            $order = Order::create([
                'user_id' => auth()->id(),
                'order_number' => $orderNumber,
                'status' => 'pending',

                'customer_name' =>
                    $validated['customer_name'],

                'phone' =>
                    $validated['phone'],

                'shipping_address' =>
                    $validated['shipping_address'],

                'notes' =>
                    $validated['notes'] ?? null,

                'subtotal' => $subtotal,

                'shipping_cost' =>
                    $shippingCost,

                'total' => $total,
            ]);

            /*
             * Buat order items dan kurangi stok.
             */
            foreach ($cart->items as $cartItem) {

                $product =
                    $products->get($cartItem->product_id);

                $itemSubtotal =
                    $product->price * $cartItem->quantity;

                $order->items()->create([
                    'product_id' =>
                        $product->id,

                    'product_name' =>
                        $product->name,

                    'price' =>
                        $product->price,

                    'quantity' =>
                        $cartItem->quantity,

                    'subtotal' =>
                        $itemSubtotal,
                ]);

                /*
                 * Kurangi stock.
                 */
                $product->decrement(
                    'stock',
                    $cartItem->quantity
                );
            }

            /*
             * Kosongkan keranjang setelah order
             * berhasil dibuat.
             */
            $cart->items()->delete();

            return $order;
        });

        return redirect()
            ->route(
                'checkout.success',
                $order
            )
            ->with(
                'success',
                'Pesanan berhasil dibuat.'
            );
    }

    /**
     * Halaman sukses checkout.
     */
    public function success(Order $order)
    {
        /*
         * Pastikan user hanya dapat melihat order miliknya.
         */
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        $order->load([
            'items.product',
        ]);

        return view(
            'storefront.checkout-success',
            compact('order')
        );
    }
}
