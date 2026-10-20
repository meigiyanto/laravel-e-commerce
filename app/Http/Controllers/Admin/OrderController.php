<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Services\VariantInventoryService;
use App\Services\RefundService;

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
        $midtransCancellationConfirmed = false;
        
        if ($newStatus === 'canceled') {
            $payment = $order->payment()->first();
        
            if ($payment && $payment->status === 'succeeded') {
                // Pembayaran sukses ditangani lewat alur refund,
                // bukan cancel transaksi yang belum dibayar.
            } elseif ($payment && $payment->provider === 'stripe') {
                if (! $payment->stripe_payment_intent_id) {
                    return back()->with(
                        'error',
                        'Pembatalan ditolak: PaymentIntent Stripe tidak ditemukan.'
                    );
                }
        
                try {
                    $stripe = app(
                        \App\Services\StripePaymentService::class
                    );
        
                    $intent = $stripe->cancel(
                        $payment->stripe_payment_intent_id
                    );
        
                    if ($intent->status !== 'canceled') {
                        return back()->with(
                            'error',
                            'Transaksi Stripe belum dipastikan batal. Pesanan dan stok tidak diubah.'
                        );
                    }
        
                    $stripeCancellationConfirmed = true;
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning(
                        'Gagal membatalkan transaksi Stripe.',
                        [
                            'order_id' => $order->id,
                            'exception' => get_class($e),
                        ]
                    );
        
                    // Jika cancel gagal karena transaksi ternyata sudah
                    // berhasil dibayar, sinkronkan status sebelum refund.
                    try {
                        $intent = app(
                            \App\Services\StripePaymentService::class
                        )->retrieve($payment->stripe_payment_intent_id);
        
                        if ($intent->status === 'succeeded') {
                            return back()->with(
                                'error',
                                'Pembayaran sudah berhasil. Segarkan data pembayaran dan ajukan pembatalan melalui proses refund.'
                            );
                        }
                    } catch (\Throwable $verificationError) {
                        \Illuminate\Support\Facades\Log::warning(
                            'Status Stripe tidak dapat diverifikasi.',
                            ['order_id' => $order->id]
                        );
                    }
        
                    return back()->with(
                        'error',
                        'Status transaksi Stripe tidak dapat dipastikan. Pesanan dan stok tidak diubah.'
                    );
                }
            } elseif (
                $payment
                && $payment->provider === 'midtrans'
                && ! in_array(
                    $payment->status,
                    ['failed', 'expired', 'canceled'],
                    true
                )
            ) {
                try {
                    $midtrans = app(
                        \App\Services\MidtransService::class
                    );
        
                    // Selalu cek status provider terlebih dahulu.
                    $current = $midtrans->getStatus(
                        $order->order_number
                    );
        
                    $providerStatus = strtolower(
                        (string) ($current->transaction_status ?? '')
                    );
        
                    if (in_array($providerStatus, ['settlement', 'capture'], true)) {
                        return back()->with(
                            'error',
                            'Transaksi Midtrans sudah dibayar atau ditangkap. Gunakan proses refund.'
                        );
                    }
        
                    if ($providerStatus !== 'pending') {
                        return back()->with(
                            'error',
                            'Transaksi Midtrans tidak lagi berstatus pending. Periksa status pembayaran sebelum membatalkan pesanan.'
                        );
                    }
        
                    $midtrans->cancelTransaction($order->order_number);
        
                    // Verifikasi status aktual, jangan percaya respons cancel saja.
                    $verified = $midtrans->verifyCancellation(
                        $order->order_number
                    );
        
                    $verifiedStatus = strtolower(
                        (string) ($verified->transaction_status ?? '')
                    );
        
                    if (! in_array(
                        $verifiedStatus,
                        ['cancel', 'canceled', 'expire', 'deny', 'failure'],
                        true
                    )) {
                        return back()->with(
                            'error',
                            'Midtrans belum mengonfirmasi pembatalan. Pesanan dan stok tidak diubah.'
                        );
                    }
        
                    $midtransCancellationConfirmed = true;
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning(
                        'Gagal membatalkan transaksi Midtrans.',
                        [
                            'order_id' => $order->id,
                            'exception' => get_class($e),
                        ]
                    );
        
                    return back()->with(
                        'error',
                        'Status atau pembatalan Midtrans tidak dapat dipastikan. Pesanan dan stok tidak diubah.'
                    );
                }
            }
        }
    
        $result = DB::transaction(function () use (
            $order,
            $newStatus,
            $stripeCancellationConfirmed,
            $midtransCancellationConfirmed
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
                            $payment->status !== 'succeeded'
                            && ! in_array($payment->status, $safeStatuses, true)
                        ) {
                            if (! $stripeCancellationConfirmed) {
                                return 'payment_not_safe';
                            }
                    
                            $payment->status = 'canceled';
                            $payment->save();
                        }
                    } elseif (
                        $payment->provider === 'midtrans'
                        && ! in_array(
                            $payment->status,
                            ['failed', 'expired', 'canceled', 'succeeded'],
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
        
        if ($newStatus === 'canceled' && $result === 'updated') {
            $order->refresh();

            $payment = $order->payment()->first();

            if (
                $order->status === 'canceled'
                && $payment?->status === 'succeeded'
            ) {
                app(RefundService::class)
                    ->requestLateCanceledOrderRefund(
                        $order,
                        $payment
                    );
            }
        }
    
        return redirect()
            ->route('admin.orders.show', $order)
            ->with(
                'success',
                'Status pesanan berhasil diperbarui.'
            );
    }
}
