<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\VariantInventoryService;

class OrderController extends Controller
{
    /**
     * Status order yang tersedia.
     */
    private const STATUSES = [
        'pending',
        'processing',
        'shipped',
        'completed',
        'canceled',
    ];

    /**
     * Menampilkan semua order.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status');

        $orders = Order::query()
            ->with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where(
                        'order_number',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhere(
                            'customer_name',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'phone',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas('user', function ($query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere(
                                    'email',
                                    'like',
                                    "%{$search}%"
                                );
                        });
                });
            })
            ->when(
                in_array($status, self::STATUSES, true),
                fn ($query) => $query->where('status', $status)
            )
            ->latest()
            ->get();

        /*
         * Jumlah order berdasarkan status.
         */
        $statusCounts = Order::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('admin.orders.index', [
            'orders' => $orders,
            'search' => $search,
            'status' => $status,
            'statuses' => self::STATUSES,
            'statusCounts' => $statusCounts,
        ]);
    }

    /**
     * Menampilkan detail order.
     */
    public function show(Order $order)
    {
        $order->load([
            'user',
            'items.product',
        ]);

        return view('admin.orders.show', compact('order'));
    }

    /**
     * Mengubah status order.
     */
    public function updateStatus(
        Request $request,
        Order $order
    ) {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(self::STATUSES),
            ],
        ]);
    
        $newStatus = $validated['status'];
    
        if ($order->status === $newStatus) {
            return back()->with(
                'success',
                'Status pesanan tidak berubah.'
            );
        }
    
        /*
         * Konfirmasi pembatalan Stripe dilakukan sebelum transaksi
         * database agar tidak menahan lock saat menunggu jaringan.
         */
        $stripeCancellationConfirmed = false;
    
        if ($newStatus === 'canceled') {
            $payment = $order->payment()->first();
    
            if (
                $payment
                && $payment->provider === 'stripe'
                && ! in_array($payment->status, [
                    'failed',
                    'expired',
                    'canceled',
                ], true)
            ) {
                if (! $payment->stripe_payment_intent_id) {
                    return back()->with(
                        'error',
                        'Pembatalan ditolak: PaymentIntent Stripe tidak ditemukan.'
                    );
                }
    
                try {
                    $paymentIntent = app(
                        \App\Services\StripePaymentService::class
                    )->cancel($payment->stripe_payment_intent_id);
    
                    if ($paymentIntent->status !== 'canceled') {
                        return back()->with(
                            'error',
                            'Pembayaran Stripe belum dapat dipastikan batal. Pesanan dan stok tidak diubah.'
                        );
                    }
    
                    $stripeCancellationConfirmed = true;
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning(
                        'Admin failed to cancel Stripe PaymentIntent.',
                        [
                            'order_id' => $order->id,
                            'exception' => get_class($e),
                            'message' => $e->getMessage(),
                        ]
                    );
    
                    return back()->with(
                        'error',
                        'Pembatalan Stripe gagal. Status pesanan dan stok tidak diubah.'
                    );
                }
            }
        }
    
        $result = DB::transaction(function () use (
            $order,
            $newStatus,
            $stripeCancellationConfirmed
        ) {
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);
    
            $allowedTransitions = [
                'pending' => ['processing', 'canceled'],
                'processing' => ['shipped', 'canceled'],
                'shipped' => ['completed'],
                'completed' => [],
                'canceled' => [],
            ];
    
            if (! in_array(
                $newStatus,
                $allowedTransitions[$lockedOrder->status] ?? [],
                true
            )) {
                return 'transition_blocked';
            }
    
            if (
                $newStatus === 'canceled'
                && in_array(
                    $lockedOrder->status,
                    ['pending', 'processing'],
                    true
                )
            ) {
                $payment = $lockedOrder->payment()
                    ->lockForUpdate()
                    ->first();
    
                if ($payment) {
                    if ($payment->provider === 'stripe') {
                        $safeStatuses = [
                            'failed',
                            'expired',
                            'canceled',
                        ];
    
                        if (
                            ! in_array(
                                $payment->status,
                                $safeStatuses,
                                true
                            )
                        ) {
                            if (! $stripeCancellationConfirmed) {
                                return 'payment_not_safe';
                            }
    
                            $payment->update([
                                'status' => 'canceled',
                                'transaction_status' => 'canceled',
                                'transaction_id' => $payment->stripe_payment_intent_id,
                                'reference_id' => $payment->stripe_payment_intent_id,
                                'metadata' => array_merge(
                                    $payment->metadata ?? [],
                                    [
                                        'admin_cancellation_confirmed' => true,
                                    ]
                                ),
                            ]);
                        }
                    } elseif (
                        $payment->provider === 'midtrans'
                        && ! in_array(
                            $payment->status,
                            ['failed', 'expired', 'canceled'],
                            true
                        )
                    ) {
                        return 'payment_not_safe';
                    }
                }
            }
    
            /*
             * Stok dipulihkan hanya saat pesanan benar-benar
             * beralih dari pending/processing ke canceled.
             */
            if (
                $newStatus === 'canceled'
                && in_array(
                    $lockedOrder->status,
                    ['pending', 'processing'],
                    true
                )
            ) {
                $items = $lockedOrder->items()
                    ->orderBy('product_id')
                    ->orderBy('product_variant_id')
                    ->orderBy('id')
                    ->get();
    
                $productIds = $items
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
    
                foreach ($items as $item) {
                    $product = $products->get($item->product_id);
    
                    if (! $product) {
                        throw new \RuntimeException(
                            "Produk {$item->product_id} tidak ditemukan saat memulihkan stok."
                        );
                    }
    
                    $stockService->restoreForOrder(
                        $product,
                        $item->product_variant_id
                            ? (int) $item->product_variant_id
                            : null,
                        (int) $item->quantity,
                        $lockedOrder
                    );
                }
            }
    
            $lockedOrder->update([
                'status' => $newStatus,
            ]);
    
            return 'updated';
        });
    
        if ($result === 'payment_not_safe') {
            return back()->with(
                'error',
                'Pembatalan ditolak: pembayaran online belum dipastikan terminal.'
            );
        }
    
        if ($result === 'transition_blocked') {
            return back()->with(
                'error',
                'Perubahan status pesanan tidak diizinkan.'
            );
        }
    
        return redirect()
            ->route('admin.orders.show', $order)
            ->with(
                'success',
                'Status pesanan berhasil diperbarui.'
            );
    }
}
