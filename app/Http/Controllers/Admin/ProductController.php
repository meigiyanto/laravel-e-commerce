<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with([
            'category',
            'subCategory',
        ])
            ->latest()
            ->get();

        return view(
            'admin.products.index',
            compact('products')
        );
    }

    public function create()
    {
        $categories = Category::with('subCategories')->orderBy('name')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sub_category_id' => [
                'required',
                Rule::exists('sub_categories', 'id')
                    ->where(function ($query) use ($request) {
                        $query->where(
                            'category_id',
                            $request->category_id
                        );
                    }),
            ],

            'category_id' => [
                'required',
                'exists:categories,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:products,slug',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'image' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $validated['slug'] = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // unset($validated['category_id']);

        Product::create($validated);

        return redirect()->route('admin.products.index')->with('success', 'Product berhasil ditambahkan.');
    }

    public function show(Product $product)
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function edit(Product $product)
    {
        $categories = Category::with('subCategories')->orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sub_category_id' => [
                'required',
                Rule::exists('sub_categories', 'id')
                    ->where(function ($query) use ($request) {
                        $query->where(
                            'category_id',
                            $request->category_id
                        );
                    }),
            ],

            'category_id' => [
                'required',
                'exists:categories,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products', 'slug')
                    ->ignore($product->id),
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'price' => [
                'required',
                'numeric',
                'min:0',
            ],

            'stock' => [
                'required',
                'integer',
                'min:0',
            ],

            'image' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $validated['slug'] = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        // unset($validated['category_id']);

        $product->update($validated);

        return redirect()->route('admin.products.index')->with('success', 'Product berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        if ($product->orderItems()->exists()) {
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'Product tidak dapat dihapus karena sudah digunakan dalam order.');
        }

        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product berhasil dihapus.');
    }
}
