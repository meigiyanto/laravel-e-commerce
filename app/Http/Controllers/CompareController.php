<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CompareController extends Controller
{
    public function index(Request $request)
    {
        $ids = $request->session()->get('compare', []);

        $products = Product::with('category')
            ->whereIn('id', $ids)
            ->get()
            ->sortBy(fn ($p) => array_search($p->id, $ids))
            ->values();

        return view('storefront.compare', compact('products'));
    }

    public function store(Request $request, Product $product)
    {
        $ids = $request->session()->get('compare', []);

        if (! in_array($product->id, $ids)) {
            if (count($ids) >= 4) {
                return back()->with('error', 'Maksimal 4 produk untuk dibandingkan.');
            }

            $ids[] = $product->id;
            $request->session()->put('compare', $ids);
        }

        return back()->with('success', 'Produk ditambahkan ke compare.');
    }

    public function destroy(Request $request, Product $product)
    {
        $ids = array_values(array_diff(
            $request->session()->get('compare', []),
            [$product->id]
        ));

        $request->session()->put('compare', $ids);

        return back()->with('success', 'Produk dihapus dari compare.');
    }

    public function clear(Request $request)
    {
        $request->session()->forget('compare');

        return back()->with('success', 'Compare dikosongkan.');
    }
}
