<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use App\Services\VariantInventoryService;

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

        $subtotal = $cart->items->sum(function ($item) { 
            $price = $item->variant?->price ?? $item->product->price;

            return $price * $item->quantity;
        });
        
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

            $cart->load([
                'items.product',
                'items.variant.inventory',
            ]);

            if ($cart->items->isEmpty()) {
                abort(422, 'Your cart still empty.');
            }

            $cartItems = $cart->items()
                ->lockForUpdate()
                ->with(['product', 'variant'])
                ->get();
            
            if ($cartItems->isEmpty()) {
                abort(422, 'Keranjang masih kosong.');
            }
            
            $productIds = $cartItems
                ->pluck('product_id')
                ->unique()
                ->values();
            
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            
            $stockRows = [];
            $requiredByVariant = [];
            $subtotal = 0;
            
            /*
            foreach ($cart->items as $cartItem) {
                $product = $products->get($cartItem->product_id);
            
                if (! $product) {
                    throw ValidationException::withMessages([
                        'stock' => 'Produk di keranjang tidak lagi tersedia.',
                    ]);
                }
            
                $resolved = app(VariantInventoryService::class)
                    ->resolve($product);
            
                $variant = $resolved['variant'];
                $inventory = $resolved['inventory'];
                $available = (int) $inventory->quantity
                    - (int) $inventory->reserved_quantity;
            
                if (
                    $variant->status !== 'active'
                    || $cartItem->quantity > $available
                ) {
                    throw ValidationException::withMessages([
                        'stock' => "Stok produk {$product->name} tidak mencukupi.",
                    ]);
                }

                $stockRows[$product->id] = $resolved;
                $subtotal += (float) $variant->price * $cartItem->quantity;
            }

            $shippingCost = 0;
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
            */
            
            $stockService = app(VariantInventoryService::class);
            
            foreach ($cartItems as $cartItem) {
                $product = $products->get($cartItem->product_id);
            
                if (! $product) {
                    throw ValidationException::withMessages([
                        'stock' => 'Produk di keranjang tidak lagi tersedia.',
                    ]);
                }
            
                $resolved = $stockService->resolve(
                    $product,
                    $cartItem->product_variant_id
                        ? (int) $cartItem->product_variant_id
                        : null
                );
            
                $variant = $resolved['variant'];
                $inventory = $resolved['inventory'];
            
                if ($variant->status !== 'active') {
                    throw ValidationException::withMessages([
                        'stock' => "Varian {$variant->name} tidak tersedia.",
                    ]);
                }
            
                /*
                 * Item keranjang lama yang belum mempunyai variant_id
                 * dipetakan ke varian default hasil resolusi.
                 */
                if (! $cartItem->product_variant_id) {
                    $cartItem->update([
                        'product_variant_id' => $variant->id,
                    ]);
                }
            
                $variantId = (int) $variant->id;
            
                $stockRows[$cartItem->id] = [
                    'product' => $resolved['product'],
                    'variant' => $variant,
                    'inventory' => $inventory,
                    'quantity' => (int) $cartItem->quantity,
                ];
            
                $requiredByVariant[$variantId] =
                    ($requiredByVariant[$variantId] ?? 0)
                    + (int) $cartItem->quantity;
            
                $subtotal += (float) $variant->price
                    * (int) $cartItem->quantity;
            }
            
            foreach ($requiredByVariant as $variantId => $requiredQuantity) {
                $row = collect($stockRows)->first(
                    fn ($item) => (int) $item['variant']->id === $variantId
                );
            
                $available = (int) $row['inventory']->quantity
                    - (int) $row['inventory']->reserved_quantity;
            
                if ($requiredQuantity > $available) {
                    throw ValidationException::withMessages([
                        'stock' => "Stok varian {$row['variant']->name} tidak mencukupi.",
                    ]);
                }
            }

            foreach ($cartItems as $cartItem) {
                $resolved = $stockRows[$cartItem->id];
            
                $product = $resolved['product'];
                $variant = $resolved['variant'];
            
                $itemSubtotal = (float) $variant->price
                    * (int) $cartItem->quantity;
            
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $product->name,
                    'price' => $variant->price,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => $itemSubtotal,
                ]);
            }
            
            /*
             * Kurangi stok satu kali per varian dengan jumlah gabungan.
             */
            $rowsByVariant = collect($stockRows)->groupBy(
                fn ($item) => (int) $item['variant']->id
            );
            
            foreach ($rowsByVariant as $variantRows) {
                $first = $variantRows->first();
            
                $totalQuantity = $variantRows->sum(
                    fn ($item) => (int) $item['quantity']
                );
            
                $stockService->deductForOrder(
                    $first['product'],
                    $first['variant'],
                    $first['inventory'],
                    $totalQuantity,
                    $order
                );
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
