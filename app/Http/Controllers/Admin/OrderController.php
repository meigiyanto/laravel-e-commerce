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
         * Order canceled tidak boleh diaktifkan kembali.
         */
        if (
            $order->status === 'canceled'
            && $newStatus !== 'canceled'
        ) {
            return back()->with(
                'error',
                'Pesanan yang sudah dibatalkan tidak dapat diaktifkan kembali.'
            );
        }

        /*
         * Tidak ada perubahan.
         */
        if ($order->status === $newStatus) {
            return back()->with(
                'success',
                'Status pesanan tidak berubah.'
            );
        }

        DB::transaction(function () use ($order, $newStatus) {
            /*
             * Lock order untuk mencegah race condition.
             */
            $lockedOrder = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            /*
             * Jika order dibatalkan,
             * kembalikan stok produk.
             */
            if (
                $newStatus === 'canceled'
                && $lockedOrder->status !== 'canceled'
            ) {
                $items = $lockedOrder->items()->get();

                /*
                 * Lock product terlebih dahulu.
                 */
                $productIds = $items
                    ->pluck('product_id')
                    ->unique()
                    ->values();

                Product::query()
                    ->whereIn('id', $productIds)
                    ->lockForUpdate()
                    ->get();

                /*
                 * Kembalikan stok.
                 */
                foreach ($items as $item) {
                    Product::query()
                        ->whereKey($item->product_id)
                        ->increment(
                            'stock',
                            $item->quantity
                        );
                }
            }

            /*
             * Update status.
             */
            $lockedOrder->update([
                'status' => $newStatus,
            ]);
        });

        return redirect()
            ->route('admin.orders.show', $order)
            ->with(
                'success',
                'Status pesanan berhasil diperbarui.'
            );
    }
}
