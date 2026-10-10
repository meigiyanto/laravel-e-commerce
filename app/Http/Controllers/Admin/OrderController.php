<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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

        /*
         * Tidak ada perubahan status.
         */
        if ($order->status === $newStatus) {
            return back()->with(
                'success',
                'Status pesanan tidak berubah.'
            );
        }

        $result = DB::transaction(function () use (
            $order,
            $newStatus
        ) {
            /*
             * Lock dan baca status pesanan terbaru.
             */
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            /*
             * Aturan transisi status pesanan.
             */
            $allowedTransitions = [
                'pending' => [
                    'processing',
                    'canceled',
                ],
                'processing' => [
                    'shipped',
                    'canceled',
                ],
                'shipped' => [
                    'completed',
                ],
                'completed' => [],
                'canceled' => [],
            ];

            /*
             * Tolak perpindahan status yang tidak diizinkan.
             */
            if (
                ! in_array(
                    $newStatus,
                    $allowedTransitions[$lockedOrder->status] ?? [],
                    true
                )
            ) {
                return 'transition_blocked';
            }


            /*
             * Pesanan yang sudah dibatalkan tidak boleh
             * diaktifkan kembali.
             */
            if (
                $lockedOrder->status === 'canceled'
                && $newStatus !== 'canceled'
            ) {
                return 'reactivation_blocked';
            }

            /*
             * Pesanan yang sudah dikirim atau selesai
             * tidak boleh dibatalkan oleh admin.
             */
            if (
                $newStatus === 'canceled'
                && in_array(
                    $lockedOrder->status,
                    ['shipped', 'completed'],
                    true
                )
            ) {
                return 'cancellation_blocked';
            }

            /*
             * Kembalikan stok hanya ketika status berubah
             * menjadi canceled dari status yang diizinkan.
             */
            if (
                $newStatus === 'canceled'
                && in_array(
                    $lockedOrder->status,
                    ['pending', 'processing'],
                    true
                )
            ) {
                $items = $lockedOrder->items()->get();

                $productIds = $items
                    ->pluck('product_id')
                    ->unique()
                    ->values();

                Product::query()
                    ->whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get();

                foreach ($items as $item) {
                    Product::query()
                        ->whereKey($item->product_id)
                        ->increment(
                            'stock',
                            $item->quantity
                        );
                }
            }

            $lockedOrder->update([
                'status' => $newStatus,
            ]);

            return 'updated';
        });

        if ($result === 'reactivation_blocked') {
            return back()->with(
                'error',
                'Pesanan yang sudah dibatalkan tidak dapat diaktifkan kembali.'
            );
        }

        if ($result === 'cancellation_blocked') {
            return back()->with(
                'error',
                'Pesanan yang sudah dikirim atau selesai tidak dapat dibatalkan.'
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
