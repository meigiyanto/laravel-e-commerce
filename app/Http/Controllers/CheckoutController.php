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
                abort(404, 'Cart not found.');
            }
        
            $cartItems = $cart->items()
                ->orderBy('id')
                ->lockForUpdate()
                ->with(['product', 'variant'])
                ->get();
        
            if ($cartItems->isEmpty()) {
                abort(422, 'Keranjang masih kosong.');
            }
        
            // Kunci semua produk dalam urutan ID yang konsisten.
            $productIds = $cartItems
                ->pluck('product_id')
                ->unique()
                ->sort()
                ->values();
        
            $products = Product::query()
                ->whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
        
            $stockService = app(VariantInventoryService::class);
        
            // Semua data stok diindeks menggunakan ID item keranjang.
            $stockRows = [];
            $requiredByVariant = [];
            $subtotal = 0;
        
            /*
             * 1. Resolusi varian dan penghitungan subtotal.
             * Belum membuat order atau mengurangi stok.
             */
            foreach ($cartItems as $cartItem) {
                $product = $products->get($cartItem->product_id);
        
                if (! $product) {
                    throw ValidationException::withMessages([
                        'stock' => 'Produk di keranjang tidak lagi tersedia.',
                    ]);
                }
        
                if ((int) $cartItem->quantity < 1) {
                    throw ValidationException::withMessages([
                        'stock' => 'Jumlah produk di keranjang tidak valid.',
                    ]);
                }
        
                $requestedVariantId = $cartItem->product_variant_id
                    ? (int) $cartItem->product_variant_id
                    : null;
        
                $resolved = $stockService->resolve(
                    $product,
                    $requestedVariantId
                );
        
                $product = $resolved['product'];
                $variant = $resolved['variant'];
                $inventory = $resolved['inventory'];
        
                if ($variant->status !== 'active') {
                    throw ValidationException::withMessages([
                        'stock' => "Varian {$variant->name} tidak tersedia.",
                    ]);
                }
        
                // Simpan varian default yang berhasil di-resolve
                // jika item keranjang lama belum memiliki variant ID.
                if (! $cartItem->product_variant_id) {
                    $cartItem->update([
                        'product_variant_id' => $variant->id,
                    ]);
                }
        
                $quantity = (int) $cartItem->quantity;
                $variantId = (int) $variant->id;
        
                $stockRows[$cartItem->id] = [
                    'product' => $product,
                    'variant' => $variant,
                    'inventory' => $inventory,
                    'quantity' => $quantity,
                ];
        
                // Gabungkan semua item yang menggunakan varian sama.
                $requiredByVariant[$variantId] =
                    ($requiredByVariant[$variantId] ?? 0) + $quantity;
        
                $subtotal += (float) $variant->price * $quantity;
            }
        
            /*
             * 2. Validasi total kebutuhan setiap varian.
             * Mencegah beberapa baris keranjang melampaui stok
             * meskipun masing-masing baris secara individual terlihat cukup.
             */
            $rowsByVariant = collect($stockRows)->groupBy(
                fn ($row) => (int) $row['variant']->id
            );
        
            foreach ($requiredByVariant as $variantId => $requiredQuantity) {
                $rows = $rowsByVariant->get($variantId);
                $row = $rows?->first();
        
                if (! $row) {
                    throw ValidationException::withMessages([
                        'stock' => 'Data varian keranjang tidak valid.',
                    ]);
                }
        
                $available = (int) $row['inventory']->quantity
                    - (int) $row['inventory']->reserved_quantity;
        
                if ($requiredQuantity > $available) {
                    throw ValidationException::withMessages([
                        'stock' => "Stok varian {$row['variant']->name} tidak mencukupi.",
                    ]);
                }
            }
        
            /*
             * 3. Hitung total dan buat order serta payment
             * hanya setelah semua varian dan stok tervalidasi.
             */
            $shippingCost = 0;
            $total = $subtotal + $shippingCost;
        
            do {
                $orderNumber = 'ORD-'
                    . now()->format('YmdHis')
                    . '-'
                    . strtoupper(Str::random(5));
            } while (
                Order::where('order_number', $orderNumber)->exists()
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
                'payment_type' => match ($validated['payment_method']) {
                    'stripe' => 'card',
                    'midtrans' => 'midtrans_snap',
                    'cod' => 'cash_on_delivery',
                },
                'transaction_status' => 'pending',
                'gross_amount' => $order->total,
            ]);
        
            /*
             * 4. Buat item order dari varian yang sudah divalidasi.
             */
            foreach ($cartItems as $cartItem) {
                $resolved = $stockRows[$cartItem->id];
        
                $product = $resolved['product'];
                $variant = $resolved['variant'];
                $quantity = $resolved['quantity'];
        
                $itemSubtotal = (float) $variant->price * $quantity;
        
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_variant_id' => $variant->id,
                    'product_name' => $product->name,
                    'price' => $variant->price,
                    'quantity' => $quantity,
                    'subtotal' => $itemSubtotal,
                ]);
            }
        
            /*
             * 5. Kurangi stok sekali untuk setiap varian,
             * dengan jumlah gabungan seluruh baris keranjang.
             */
            foreach ($rowsByVariant as $variantRows) {
                $first = $variantRows->first();
        
                $totalQuantity = $variantRows->sum(
                    fn ($row) => (int) $row['quantity']
                );
        
                $stockService->deductForOrder(
                    $first['product'],
                    $first['variant'],
                    $first['inventory'],
                    $totalQuantity,
                    $order
                );
            }
        
            // 6. Kosongkan keranjang setelah order dan stok diproses.
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
