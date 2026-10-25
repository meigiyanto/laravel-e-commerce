<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use App\Services\MidtransService;
use App\Services\RefundService;
use App\Services\StripePaymentService;
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
        $midtransCancellationConfirmed = false;
        $providerPaymentSucceeded = false;
        
        if ($newStatus === 'canceled') {
            $payment = $order->payment()->first();
        
            if ($payment) {
                $safeStatuses = [
                    'failed',
                    'expired',
                    'canceled',
                ];
        
                /*
                 * Status succeeded tidak boleh dibatalkan melalui API
                 * pembatalan. Lanjutkan ke alur refund setelah pesanan
                 * berhasil dibatalkan.
                 */
                if ($payment->status === 'succeeded') {
                    $providerPaymentSucceeded = true;
                } elseif (in_array($payment->status, $safeStatuses, true)) {
                    // Pembayaran sudah terminal; tidak perlu membatalkan provider.
                } elseif ($payment->provider === 'stripe') {
                    if (! $payment->stripe_payment_intent_id) {
                        return back()->with(
                            'error',
                            'Pembatalan ditolak: PaymentIntent Stripe tidak ditemukan.'
                        );
                    }
        
                    try {
                        $paymentIntent = app(StripePaymentService::class)
                            ->cancel($payment->stripe_payment_intent_id);
        
                        if ($paymentIntent->status === 'succeeded') {
                            /*
                             * Stripe mengonfirmasi pembayaran berhasil.
                             * Jangan menandainya canceled; arahkan ke refund.
                             */
                            $providerPaymentSucceeded = true;
                        } elseif ($paymentIntent->status === 'canceled') {
                            $stripeCancellationConfirmed = true;
                        } else {
                            return back()->with(
                                'error',
                                'Status Stripe belum terminal. Pesanan dan stok tidak diubah.'
                            );
                        }
                    } catch (\Throwable $e) {
                        Log::warning(
                            'Admin failed to cancel Stripe PaymentIntent.',
                            [
                                'order_id' => $order->id,
                                'exception' => get_class($e),
                                'message' => $e->getMessage(),
                            ]
                        );
        
                        return back()->with(
                            'error',
                            'Pembatalan Stripe gagal atau hasilnya tidak pasti. Pesanan dan stok tidak diubah.'
                        );
                    }
                } elseif ($payment->provider === 'midtrans') {
                    try {
                        $midtrans = app(MidtransService::class);
        
                        /*
                         * Periksa status terkini sebelum meminta pembatalan.
                         */
                        $providerStatus = $midtrans->getStatus(
                            $order->order_number
                        );
        
                        $transactionStatus = strtolower(
                            (string) ($providerStatus->transaction_status ?? '')
                        );
        
                        $statusCode = (string) (
                            $providerStatus->status_code ?? ''
                        );
        
                        $fraudStatus = strtolower(
                            (string) ($providerStatus->fraud_status ?? '')
                        );
        
                        $isSuccessfulPayment =
                            $transactionStatus === 'settlement'
                            || (
                                $transactionStatus === 'capture'
                                && ($statusCode === '' || $statusCode === '200')
                                && ($fraudStatus === '' || $fraudStatus === 'accept')
                            );
        
                        if ($isSuccessfulPayment) {
                            /*
                             * Transaksi sudah dibayar. Jangan panggil cancel.
                             */
                            $providerPaymentSucceeded = true;
                        } elseif (in_array(
                            $transactionStatus,
                            ['cancel', 'expire', 'deny', 'failure'],
                            true
                        )) {
                            // Provider sudah menyatakan transaksi tidak aktif.
                        } elseif ($transactionStatus === 'pending') {
                            /*
                             * Hanya transaksi pending yang dimintakan
                             * pembatalan di sini.
                             */
                            $midtrans->cancelTransaction(
                                $order->order_number
                            );
        
                            /*
                             * Verifikasi ulang dari Midtrans, bukan hanya
                             * mengandalkan respons cancel().
                             */
                            $verifiedStatus = $midtrans->verifyCancellation(
                                $order->order_number
                            );
        
                            $verifiedTransactionStatus = strtolower(
                                (string) (
                                    $verifiedStatus->transaction_status ?? ''
                                )
                            );
        
                            if (in_array(
                                $verifiedTransactionStatus,
                                ['cancel', 'expire', 'deny', 'failure'],
                                true
                            )) {
                                $midtransCancellationConfirmed = true;
                            } elseif (
                                $verifiedTransactionStatus === 'settlement'
                                || (
                                    $verifiedTransactionStatus === 'capture'
                                    && (
                                        (string) ($verifiedStatus->status_code ?? '') === ''
                                        || (string) $verifiedStatus->status_code === '200'
                                    )
                                    && in_array(
                                        strtolower((string) (
                                            $verifiedStatus->fraud_status ?? ''
                                        )),
                                        ['', 'accept'],
                                        true
                                    )
                                )
                            ) {
                                /*
                                 * Status berubah menjadi berhasil saat proses
                                 * pembatalan berlangsung. Alihkan ke refund.
                                 */
                                $providerPaymentSucceeded = true;
                            } else {
                                return back()->with(
                                    'error',
                                    'Midtrans belum mengonfirmasi pembatalan. Pesanan dan stok tidak diubah.'
                                );
                            }
                        } else {
                            /*
                             * Status tidak dikenal atau tidak aman.
                             * Fail closed: jangan mengubah order maupun stok.
                             */
                            return back()->with(
                                'error',
                                'Status transaksi Midtrans belum dapat dipastikan. Pesanan dan stok tidak diubah.'
                            );
                        }
                    } catch (\Throwable $e) {
                        Log::warning(
                            'Admin failed to cancel Midtrans transaction.',
                            [
                                'order_id' => $order->id,
                                'exception' => get_class($e),
                                'message' => $e->getMessage(),
                            ]
                        );
        
                        return back()->with(
                            'error',
                            'Pembatalan Midtrans gagal atau hasilnya tidak pasti. Pesanan dan stok tidak diubah.'
                        );
                    }
                } else {
                    /*
                     * Jangan membatalkan pembayaran dengan provider yang
                     * tidak memiliki mekanisme pembatalan yang dikenali.
                     */
                    return back()->with(
                        'error',
                        'Pembatalan ditolak: provider pembayaran tidak didukung.'
                    );
                }
            }
        }    
        
        $result = DB::transaction(function () use (
            $order,
            $newStatus,
            $stripeCancellationConfirmed,
            $midtransCancellationConfirmed,
            $providerPaymentSucceeded
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
            
            if ($newStatus === 'canceled'
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
                    /*
                     * Provider mengonfirmasi pembayaran berhasil.
                     * Pertahankan status succeeded agar alur refund dapat berjalan.
                     */
                    if ($providerPaymentSucceeded) {
                        $payment->status = 'succeeded';
                        $payment->paid_at = $payment->paid_at ?? now();
                        $payment->save();
                    } elseif ($payment->status === 'succeeded') {
                        /*
                         * Jangan menurunkan status pembayaran yang sudah berhasil.
                         * Refund ditangani setelah order berhasil dibatalkan.
                         */
                    } elseif (in_array(
                        $payment->status,
                        ['failed', 'expired', 'canceled'],
                        true
                    )) {
                        // Status pembayaran sudah terminal; tidak perlu dibatalkan lagi.
                    } elseif ($payment->provider === 'stripe') {
                        if (! $stripeCancellationConfirmed) {
                            return 'payment_not_safe';
                        }
            
                        $payment->status = 'canceled';
                        $payment->save();
                    } elseif ($payment->provider === 'midtrans') {
                        if (! $midtransCancellationConfirmed) {
                            return 'payment_not_safe';
                        }
            
                        $payment->status = 'canceled';
                        $payment->save();
                    } elseif ($payment->provider !== 'cod') {
                        return 'payment_not_safe';
                    }
                }
            }
            
            /**
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
                app(VariantInventoryService::class)
                    ->restoreItemsForOrder($lockedOrder);
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
