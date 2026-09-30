<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'rating' => [
                'required',
                'integer',
                'min:1',
                'max:5',
            ],

            'title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'comment' => [
                'required',
                'string',
                'max:5000',
            ],
        ]);

        /*
         * Cari pembelian user untuk produk ini.
         *
         * Hanya order COMPLETED yang dianggap
         * sebagai verified purchase.
         */
        $orderItem = OrderItem::query()
            ->where('product_id', $product->id)
            ->whereHas('order', function ($query) {
                $query
                    ->where('user_id', auth()->id())
                    ->where('status', 'completed');
            })
            ->latest()
            ->first();

        if (!$orderItem) {
            return back()->with(
                'error',
                'Kamu hanya dapat memberikan review untuk produk yang sudah kamu beli dan pesanan sudah selesai.'
            );
        }

        /*
         * Cegah review kedua untuk produk yang sama.
         */
        $alreadyReviewed = Review::query()
            ->where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->exists();

        if ($alreadyReviewed) {
            return back()->with(
                'error',
                'Kamu sudah memberikan review untuk produk ini.'
            );
        }

        Review::create([
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'order_id' => $orderItem->order_id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'comment' => $validated['comment'],
            'is_verified' => true,
        ]);

        return back()->with(
            'success',
            'Review berhasil ditambahkan.'
        );
    }
}
