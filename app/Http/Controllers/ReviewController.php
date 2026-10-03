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
        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string|max:5000',
        ]);

        $item = OrderItem::where('product_id', $product->id)
            ->whereHas('order', fn ($q) => $q
                ->where('user_id', auth()->id())
                ->where('status', 'completed'))
            ->latest()
            ->first();

        if (! $item) {
            return back()->with(
                'error',
                'Review hanya dapat diberikan untuk produk dari pesanan yang sudah selesai.'
            );
        }

        if (Review::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->exists()) {
            return back()->with('error', 'Kamu sudah memberikan review untuk produk ini.');
        }

        Review::create([
            ...$data,
            'user_id' => auth()->id(),
            'product_id' => $product->id,
            'order_id' => $item->order_id,
            'is_verified' => true,
        ]);

        return back()->with('success', 'Review berhasil ditambahkan.');
    }

    public function update(Request $request, Review $review)
    {
        abort_unless($review->user_id === auth()->id(), 403);

        $data = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'title' => 'nullable|string|max:255',
            'comment' => 'required|string|max:5000',
        ]);

        $review->update($data);

        return back()->with('success', 'Review berhasil diperbarui.');
    }

    public function destroy(Review $review)
    {
        abort_unless($review->user_id === auth()->id(), 403);

        $review->delete();

        return back()->with('success', 'Review berhasil dihapus.');
    }
}
