<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    private const LOW_STOCK_THRESHOLD = 5;

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $stockStatus = $request->input('stock_status');

        $products = Product::query()
            ->with('category')
            ->when($search !== '', function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when(
                $stockStatus === 'out_of_stock',
                fn ($query) => $query->where('stock', 0)
            )
            ->when(
                $stockStatus === 'low_stock',
                fn ($query) => $query
                    ->where('stock', '>', 0)
                    ->where('stock', '<=', self::LOW_STOCK_THRESHOLD)
            )
            ->when(
                $stockStatus === 'in_stock',
                fn ($query) => $query
                    ->where('stock', '>', self::LOW_STOCK_THRESHOLD)
            )
            ->orderBy('stock')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $stockCounts = [
            'out_of_stock' => Product::where('stock', 0)->count(),

            'low_stock' => Product::where('stock', '>', 0)
                ->where('stock', '<=', self::LOW_STOCK_THRESHOLD)
                ->count(),

            'in_stock' => Product::where(
                'stock',
                '>',
                self::LOW_STOCK_THRESHOLD
            )->count(),
        ];

        return view('admin.inventory.index', [
            'products' => $products,
            'search' => $search,
            'stockStatus' => $stockStatus,
            'stockCounts' => $stockCounts,
            'lowStockThreshold' => self::LOW_STOCK_THRESHOLD,
        ]);
    }

    public function updateStock(
        Request $request,
        Product $product
    ) {
        $validated = $request->validate([
            'stock' => [
                'required',
                'integer',
                'min:0',
            ],
        ], [
            'stock.required' => 'Stok wajib diisi.',
            'stock.integer' => 'Stok harus berupa angka.',
            'stock.min' => 'Stok tidak boleh kurang dari 0.',
        ]);

        $product->update([
            'stock' => $validated['stock'],
        ]);

        return redirect()
            ->route('admin.inventory.index')
            ->with(
                'success',
                "Stok produk {$product->name} berhasil diperbarui."
            );
    }

    public function adjustStock(
        Request $request,
        Product $product
    ) {
        $validated = $request->validate([
            'quantity' => [
                'required',
                'integer',
                'not_in:0',
            ],
            'type' => [
                'required',
                'in:add,subtract',
            ],
        ], [
            'quantity.required' => 'Jumlah stok wajib diisi.',
            'quantity.integer' => 'Jumlah stok harus berupa angka.',
            'quantity.not_in' => 'Jumlah stok tidak boleh 0.',
            'type.required' => 'Tipe perubahan stok wajib dipilih.',
            'type.in' => 'Tipe perubahan stok tidak valid.',
        ]);

        $quantity = (int) $validated['quantity'];

        if ($validated['type'] === 'subtract') {
            if ($quantity > $product->stock) {
                return back()->with(
                    'error',
                    "Stok {$product->name} hanya tersedia {$product->stock}."
                );
            }

            $product->decrement('stock', $quantity);
        } else {
            $product->increment('stock', $quantity);
        }

        return redirect()
            ->route('admin.inventory.index')
            ->with(
                'success',
                "Stok produk {$product->name} berhasil diperbarui."
            );
    }
}
