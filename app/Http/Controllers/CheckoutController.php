<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Support\Tenancy\TenantContext;
use App\Services\CartService;
use App\Services\VariantInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    private function isMultiTenant(): bool
    {
        return config('operational.mode', 'single_store') === 'multi_tenant';
    }

    private function tenantId(): ?int
    {
        return $this->isMultiTenant()
            ? (int) app(TenantContext::class)->require()->getKey()
            : null;
    }

    private function scopeCartQuery($query)
    {
        return $this->isMultiTenant()
            ? $query->where('tenant_id', $this->tenantId())
            : $query->whereNull('tenant_id');
    }

    private function assertProductTenant(Product $product): void
    {
        if ($this->isMultiTenant() && (int) $product->tenant_id !== $this->tenantId()) {
            throw ValidationException::withMessages([
                'stock' => 'Keranjang mengandung produk dari toko lain atau produk tidak tersedia.',
            ]);
        }
    }

    public function index()
    {
        $cart = $this->scopeCartQuery(Cart::query())
            ->where('user_id', auth()->id())
            ->with([
                'items.product.category',
                'items.product.subCategory',
                'items.variant',
            ])
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Your cart still empty.');
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
            'customer_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'shipping_address' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['required', 'in:stripe,midtrans,cod'],
        ]);

        $tenantId = $this->tenantId();

        $order = DB::transaction(function () use ($validated, $tenantId) {
            $cart = $this->scopeCartQuery(Cart::query())
                ->where('user_id', auth()->id())
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                abort(404, 'Cart not found.');
            }

            $cartItems = app(CartService::class)
                ->normalizeLegacyUserCartItems($cart);

            if ($cartItems->isEmpty()) {
                abort(422, 'Keranjang masih kosong.');
            }

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

            foreach ($cartItems as $cartItem) {
                $product = $products->get($cartItem->product_id);

                if (! $product) {
                    throw ValidationException::withMessages([
                        'stock' => 'Produk di keranjang tidak lagi tersedia.',
                    ]);
                }

                $this->assertProductTenant($product);
            }

            $stockService = app(VariantInventoryService::class);
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

                $resolved = $stockService->resolve($product, $requestedVariantId);

                $product = $resolved['product'];
                $variant = $resolved['variant'];
                $inventory = $resolved['inventory'];

                $this->assertProductTenant($product);

                if (
                    (int) $variant->product_id !== (int) $product->id ||
                    ($this->isMultiTenant() && (int) $variant->tenant_id !== $tenantId)
                ) {
                    throw ValidationException::withMessages([
                        'stock' => 'Varian produk tidak sesuai dengan toko aktif.',
                    ]);
                }

                if ($variant->status !== 'active') {
                    throw ValidationException::withMessages([
                        'stock' => "Varian {$variant->name} tidak tersedia.",
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

                $requiredByVariant[$variantId] =
                    ($requiredByVariant[$variantId] ?? 0) + $quantity;

                $subtotal += (float) $variant->price * $quantity;
            }

            /*
             * 2. Validasi total kebutuhan setiap varian.
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
             * 3. Buat order dan payment setelah validasi stok.
             */
            $shippingCost = 0;
            $total = $subtotal + $shippingCost;

            do {
                $orderNumber = 'ORD-'
                    . now()->format('YmdHis')
                    . '-'
                    . strtoupper(Str::random(5));
            } while (Order::where('order_number', $orderNumber)->exists());

            $order = Order::create([
                'tenant_id' => $tenantId,
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
             * 4. Buat item order.
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
             * 5. Reservasi sekali untuk setiap varian.
             */
            $reservations = [];

            foreach ($rowsByVariant->sortKeys() as $variantId => $variantRows) {
                $first = $variantRows->first();

                $totalQuantity = $variantRows->sum(
                    fn ($row) => (int) $row['quantity']
                );

                $idempotencyKey = sprintf(
                    'order:%d:variant:%d:reserve',
                    $order->id,
                    $variantId
                );

                $reservation = $stockService->reserve(
                    $first['variant'],
                    $totalQuantity,
                    $idempotencyKey,
                    Order::class,
                    (int) $order->id
                );

                $reservations[] = $reservation;
            }

            /*
             * 6. COD langsung commit seluruh reservasi.
             * Stripe dan Midtrans tetap menunggu konfirmasi pembayaran.
             */
            if ($validated['payment_method'] === 'cod') {
                foreach ($reservations as $reservation) {
                    $stockService->commitReservation(
                        $reservation->idempotency_key
                    );
                }
            }

            $cart->items()->delete();

            return $order;
        });

        if ($validated['payment_method'] === 'cod') {
            return redirect()
                ->route('orders.show', $order)
                ->with(
                    'success',
                    'COD order successfully created. Payment is made when the order is received by the recipient.'
                );
        }

        if ($validated['payment_method'] === 'midtrans') {
            return redirect()
                ->route('payment.midtrans.show', $order)
                ->with(
                    'success',
                    'Your order has been successfully placed. Please proceed with payment via Midtrans.'
                );
        }

        return redirect()
            ->route('payment.show', $order)
            ->with(
                'success',
                'Your order has been successfully placed. Please proceed with payment via Stripe.'
            );
    }

    public function success(Order $order)
    {
        $this->assertOrderAccess($order);

        $order->load(['items.product', 'payment']);
        $payment = $order->payment;

        if (! $payment) {
            return redirect()
                ->route('orders.show', $order)
                ->with('error', 'Payment data for this order was not found.');
        }

        if ($payment->provider === 'cod') {
            return view('storefront.checkout-success', compact('order'));
        }

        if (
            $payment->provider === 'stripe' &&
            $payment->status !== 'succeeded' &&
            $payment->stripe_payment_intent_id
        ) {
            app(PaymentController::class)->syncPaymentFromStripe(
                $order,
                $payment->stripe_payment_intent_id
            );

            $order->refresh();
            $order->load(['items.product', 'payment']);
            $payment = $order->payment;
        }

        if ($payment->provider === 'midtrans' && $payment->status !== 'succeeded') {
            return redirect()
                ->route('payment.midtrans.show', $order)
                ->with('error', 'Midtrans payment has not been successfully verified.');
        }

        if ($payment->status === 'succeeded') {
            return view('storefront.checkout-success', compact('order'));
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('error', 'Payment was unsuccessful. Please check your order status.');
    }

    private function assertOrderAccess(Order $order): void
    {
        if (
            $this->isMultiTenant() &&
            (int) $order->tenant_id !== $this->tenantId()
        ) {
            abort(404);
        }

        abort_unless(
            (int) $order->user_id === (int) auth()->id(),
            404
        );
    }
}