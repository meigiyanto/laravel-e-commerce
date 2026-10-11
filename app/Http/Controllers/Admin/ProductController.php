<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\SubCategory;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'subCategory'])->latest()->get();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::with('subCategories')
            ->orderBy('name')
            ->get();

        $attributes = Attribute::with([
            'values' => function ($query) {
                $query->orderBy('sort_order');
            },
        ])
            ->orderBy('sort_order')
            ->get();

        return view(
            'admin.products.create',
            compact('categories', 'attributes')
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id',
            ],
            'sub_category_id' => [
                'required',
                Rule::exists('sub_categories', 'id')
                    ->where('category_id', $request->category_id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:products,slug',
            ],
            'description' => ['nullable', 'string'],

            'sku' => [
                'required',
                'string',
                'max:100',
                'unique:product_variants,sku',
            ],
            'barcode' => [
                'nullable',
                'string',
                'max:100',
                'unique:product_variants,barcode',
            ],
            'variant_name' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => [
                'nullable',
                'numeric',
                'gte:price',
            ],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],

            'quantity' => ['required', 'integer', 'min:0'],
            'reserved_quantity' => [
                'required',
                'integer',
                'min:0',
                'lte:quantity',
            ],
            'reorder_level' => ['required', 'integer', 'min:0'],

            'attribute_value_ids' => ['nullable', 'array'],
            'attribute_value_ids.*' => [
                'integer',
                'distinct',
                'exists:attribute_values,id',
            ],

            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
            'image_alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        $slug = Str::slug($validated['slug'] ?: $validated['name']);

        // Pastikan slug otomatis juga unik.
        $baseSlug = $slug;
        $counter = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $uploadedPaths = [];

        try {
            $product = DB::transaction(function () use (
                $request,
                $validated,
                $slug,
                &$uploadedPaths
            ) {
                $product = Product::create([
                    'category_id' => $validated['category_id'],
                    'sub_category_id' => $validated['sub_category_id'],
                    'name' => $validated['name'],
                    'slug' => $slug,
                    'description' => $validated['description'] ?? null,

                    // Kompatibilitas dengan katalog lama.
                    'price' => $validated['price'],
                    'stock' => $validated['quantity'],
                    'image' => null,
                ]);

                $variant = $product->variants()->create([
                    'sku' => $validated['sku'],
                    'barcode' => $validated['barcode'] ?? null,
                    'name' => $validated['variant_name'] ?: $validated['name'],
                    'price' => $validated['price'],
                    'compare_at_price' => $validated['compare_at_price'] ?? null,
                    'cost_price' => $validated['cost_price'] ?? null,
                    'weight' => $validated['weight'] ?? null,
                    'is_default' => true,
                    'status' => $validated['status'],
                ]);

                $valueIds = $validated['attribute_value_ids'] ?? [];

                if ($valueIds !== []) {
                    $validCount = AttributeValue::whereIn('id', $valueIds)
                        ->count();

                    if ($validCount !== count($valueIds)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'attribute_value_ids' => 'The selected attribute value is invalid.',
                        ]);
                    }

                    $variant->attributeValues()->sync($valueIds);
                }

                $variant->inventory()->create([
                    'quantity' => $validated['quantity'],
                    'reserved_quantity' => $validated['reserved_quantity'],
                    'reorder_level' => $validated['reorder_level'],
                ]);

                if ($validated['quantity'] > 0) {
                    $variant->inventoryMovements()->create([
                        'type' => 'opening_stock',
                        'quantity' => $validated['quantity'],
                        'note' => 'Initial stock when the product is made.',
                        'created_by' => auth()->id(),
                    ]);
                }

                foreach ($request->file('images', []) as $index => $image) {
                    $path = $image->store('products', 'public');
                    $uploadedPaths[] = $path;

                    $product->images()->create([
                        'product_variant_id' => null,
                        'path' => $path,
                        'alt_text' => $validated['image_alt_text'] ?? $product->name,
                        'sort_order' => $index,
                        'is_primary' => $index === 0,
                    ]);

                    if ($index === 0) {
                        $product->update(['image' => $path]);
                    }
                }

                return $product;
            });
        } catch (\Throwable $exception) {
            foreach ($uploadedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Product and inventory added successfully.');
    }

    public function show(Product $product)
    {
        return redirect()->route('admin.products.edit', $product);
    }

    public function edit(Product $product)
    {
        $product->load([
            'specifications',
            'variants.attributeValues.attribute',
            'variants.inventory',
            'images',
        ]);

        $categories = Category::orderBy('name')->get();
        $subCategories = SubCategory::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'categories', 'subCategories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'sub_category_id' => [
                'required',
                Rule::exists('sub_categories', 'id')
                    ->where(function ($query) use ($request) {
                        $query->where('category_id', $request->category_id);
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
