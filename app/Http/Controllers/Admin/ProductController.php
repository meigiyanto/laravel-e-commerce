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
        ])->orderBy('sort_order')->get();

        $availableGalleryImages = $this->availableGalleryImages();

        return view('admin.products.create', compact(
            'categories',
            'attributes',
            'availableGalleryImages'
        ));
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
                    $validCount = AttributeValue::whereIn('id', $valueIds)->count();

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
            'variants.inventory',
            'variants.attributeValues.attribute',
            'images',
        ]);

        $categories = Category::with('subCategories')
            ->orderBy('name')
            ->get();

        $attributes = Attribute::with([
            'values' => fn ($query) => $query->orderBy('sort_order'),
        ])->orderBy('sort_order')->get();
        
        $availableGalleryImages = $this->availableGalleryImages();
        
        return view('admin.products.edit', compact(
            'product',
            'categories',
            'attributes',
            'availableGalleryImages'
        ));
    }

    public function update(Request $request, Product $product)
    {
        $rules = [
            'category_id' => ['required', 'exists:categories,id'],
            'sub_category_id' => [
                'required',
                Rule::exists('sub_categories', 'id')
                    ->where('category_id', $request->input('category_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            // Spesifikasi
            'specifications' => ['nullable', 'array'],
            'specifications.*.id' => [
                'nullable',
                'integer',
                Rule::exists('product_specifications', 'id')
                    ->where('product_id', $product->id),
            ],
            'specifications.*.specification_group' => [
                'required', 'string', 'max:255',
            ],
            'specifications.*.specification_name' => [
                'required', 'string', 'max:255',
            ],
            'specifications.*.specification_value' => [
                'required', 'string', 'max:2000',
            ],
            'specifications.*.sort_order' => [
                'nullable', 'integer', 'min:0',
            ],

            // Varian yang sudah ada
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => [
                'required',
                'integer',
                Rule::exists('product_variants', 'id')
                    ->where('product_id', $product->id),
            ],
            'variants.*.sku' => ['required', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.name' => ['nullable', 'string', 'max:255'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => [
                'nullable', 'numeric', 'min:0',
            ],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0'],
            'variants.*.status' => [
                'required', Rule::in(['active', 'inactive']),
            ],
            'variants.*.quantity' => ['required', 'integer', 'min:0'],
            'variants.*.reorder_level' => [
                'required', 'integer', 'min:0',
            ],
            'variants.*.attribute_value_ids' => ['nullable', 'array'],
            'variants.*.attribute_value_ids.*' => [
                'integer', 'distinct', 'exists:attribute_values,id',
            ],

            // Gambar baru; gambar lama tidak dihapus
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => [
                'image', 'mimes:jpg,jpeg,png,webp', 'max:5120',
            ],
            'image_alt_text' => ['nullable', 'string', 'max:255'],
            // Pengelolaan gambar lama
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => [
                'integer',
                'distinct',
                Rule::exists('product_images', 'id')
                    ->where('product_id', $product->id),
            ],

            'primary_image_id' => [
                'nullable',
                'integer',
                Rule::exists('product_images', 'id')
                    ->where('product_id', $product->id),
            ],
        ];

        // SKU dan barcode harus unik, kecuali milik varian yang sama.
        foreach ($request->input('variants', []) as $key => $input) {
            $variantId = filter_var($input['id'] ?? null, FILTER_VALIDATE_INT);

            $rules["variants.$key.sku"][] = Rule::unique('product_variants', 'sku')->ignore($variantId);

            if (filled($input['barcode'] ?? null)) {
                $rules["variants.$key.barcode"][] = Rule::unique('product_variants', 'barcode')->ignore($variantId);
            }
        }

        $data = $request->validate($rules);

        foreach ($data['variants'] as $key => $variant) {
            $compareAtPrice = $variant['compare_at_price'] ?? null;

            if (
                $compareAtPrice !== null &&
                $compareAtPrice !== '' &&
                (float) $compareAtPrice < (float) $variant['price']
            ) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "variants.$key.compare_at_price" =>'Harga sebelum diskon harus sama dengan atau lebih besar daripada harga jual.',
                ]);
            }
        }

        $deleteImageIds = array_map('intval', $data['delete_images'] ?? []);

        if (
            ! empty($data['primary_image_id']) &&
            in_array(
                (int) $data['primary_image_id'],
                $deleteImageIds,
                true
            )
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'primary_image_id' => 'Gambar utama tidak boleh dipilih untuk dihapus.',
            ]);
        }
        // Membuat slug unik, termasuk ketika slug dibuat otomatis.
        $baseSlug = Str::slug($data['slug'] ?: $data['name']);
        $slug = $baseSlug;
        $suffix = 1;

        while (Product::where('slug', $slug)
                ->where('id', '!=', $product->id)
                ->exists())
        {
            $slug = $baseSlug . '-' . $suffix++;
        }

        $uploadedPaths = [];
        $uploadedPaths = [];
        $deletedImagePaths = [];

        try {
            DB::transaction(function () use (
                $request,
                $data,
                $product,
                $slug,
                &$uploadedPaths,
                &$deletedImagePaths
            ) {
                $product->update([
                    'category_id' => $data['category_id'],
                    'sub_category_id' => $data['sub_category_id'],
                    'name' => $data['name'],
                    'slug' => $slug,
                    'description' => $data['description'] ?? null,
                ]);

                /*
                 * 1. Simpan spesifikasi.
                 * ID yang dikirim harus milik produk ini.
                 * Baris tanpa ID akan dibuat sebagai spesifikasi baru.
                 * Baris lama yang tidak dikirim akan dihapus.
                 */
                $savedSpecificationIds = [];

                foreach ($data['specifications'] ?? [] as $item) {
                    $attributes = [
                        'specification_group' => $item['specification_group'],
                        'specification_name' => $item['specification_name'],
                        'specification_value' => $item['specification_value'],
                        'sort_order' => $item['sort_order'] ?? 0,
                    ];

                    if (! empty($item['id'])) {
                        $specification = $product->specifications()
                            ->whereKey($item['id'])
                            ->firstOrFail();

                        $specification->update($attributes);
                    } else {
                        $specification = $product->specifications()
                            ->create($attributes);
                    }

                    $savedSpecificationIds[] = $specification->id;
                }

                $product->specifications()
                    ->whereNotIn('id', $savedSpecificationIds)
                    ->delete();

                /*
                 * 2. Simpan setiap varian dan nilai atributnya.
                 */
                foreach ($data['variants'] as $key => $item) {
                    $variant = $product->variants()
                        ->whereKey($item['id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    $variant->update([
                        'sku' => $item['sku'],
                        'barcode' => $item['barcode'] ?? null,
                        'name' => $item['name'] ?: $product->name,
                        'price' => $item['price'],
                        'compare_at_price' => $item['compare_at_price'] ?? null,
                        'cost_price' => $item['cost_price'] ?? null,
                        'weight' => $item['weight'] ?? null,
                        'status' => $item['status'],
                    ]);

                    $valueIds = $item['attribute_value_ids'] ?? [];
                    $values = AttributeValue::whereIn('id', $valueIds)->get();

                    // Maksimal satu nilai untuk setiap atribut per varian.
                    if ($values->groupBy('attribute_id')->contains(fn ($group) => $group->count() > 1)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "variants.$key.attribute_value_ids" =>
                                'Pilih maksimal satu nilai untuk setiap atribut.',
                        ]);
                    }

                    $variant->attributeValues()->sync($valueIds);

                    /*
                     * 3. Simpan inventaris.
                     * Stok tidak boleh turun di bawah reserved_quantity.
                     * Perubahan stok dicatat sebagai movement.
                     */
                    $inventory = $variant->inventory()
                        ->lockForUpdate()
                        ->first();

                    $quantity = (int) $item['quantity'];
                    $reserved = (int) ($inventory?->reserved_quantity ?? 0);

                    if ($quantity < $reserved) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "variants.$key.quantity" =>
                                'Stok tidak boleh lebih kecil daripada stok yang direservasi.',
                        ]);
                    }

                    $reorderLevel = (int) $item['reorder_level'];

                    if (! $inventory) {
                        $inventory = $variant->inventory()->create([
                            'quantity' => $quantity,
                            'reserved_quantity' => 0,
                            'reorder_level' => $reorderLevel,
                        ]);

                        if ($quantity > 0) {
                            $variant->inventoryMovements()->create([
                                'type' => 'opening_stock',
                                'quantity' => $quantity,
                                'note' => 'Stok awal saat inventaris dibuat.',
                                'created_by' => auth()->id(),
                            ]);
                        }
                    } else {
                        $difference = $quantity - (int) $inventory->quantity;

                        $inventory->update([
                            'quantity' => $quantity,
                            'reorder_level' => $reorderLevel,
                        ]);

                        if ($difference !== 0) {
                            $variant->inventoryMovements()->create([
                                'type' => 'adjustment',
                                'quantity' => $difference,
                                'note' => 'Penyesuaian stok dari halaman edit produk.',
                                'created_by' => auth()->id(),
                            ]);
                        }
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | 4. Hapus gambar yang dipilih dan tambahkan gambar baru
                |--------------------------------------------------------------------------
                */

                // Simpan path sebelum record database dihapus.
                // File fisik baru akan dihapus setelah transaksi berhasil.
                $imagesToDelete = $product->images()->whereIn('id', $data['delete_images'] ?? [])->get();

                foreach ($imagesToDelete as $image) {
                    $deletedImagePaths[] = $image->path;
                    $image->delete();
                }

                foreach ($request->file('images', []) as $image) {
                    $path = $image->store('products', 'public');
                    $uploadedPaths[] = $path;

                    $product->images()->create([
                        'product_variant_id' => null,
                        'path' => $path,
                        'alt_text' => $data['image_alt_text'] ?? $product->name,
                        'sort_order' => (int) $product->images()->max('sort_order') + 1,
                        'is_primary' => false,
                    ]);
                }

                // Pilih gambar utama: pilihan admin, gambar utama lama yang masih ada,
                // atau gambar pertama yang tersisa.
                $primaryImage = null;

                if (! empty($data['primary_image_id'])) {
                    $primaryImage = $product->images()
                        ->whereKey($data['primary_image_id'])
                        ->firstOrFail();
                } else {
                    $primaryImage = $product->images()
                        ->where('is_primary', true)
                        ->first();

                    if (! $primaryImage) {
                        $primaryImage = $product->images()
                            ->orderBy('sort_order')
                            ->orderBy('id')
                            ->first();
                    }
                }

                // Pastikan hanya satu gambar utama.
                $product->images()->update(['is_primary' => false]);

                if ($primaryImage) {
                    $primaryImage->update(['is_primary' => true]);

                    // Sinkronkan gambar utama dengan kolom katalog lama.
                    $product->update(['image' => $primaryImage->path]);
                } else {
                    $product->update(['image' => null]);
                }

                /**
                 * 5. Sinkronkan kolom katalog lama.
                 * Ini diperlukan selama storefront masih membaca
                 * products.price dan products.stock.
                 */
                $defaultVariant = $product->variants()
                    ->where('is_default', true)
                    ->with('inventory')
                    ->first()
                    ?? $product->variants()->with('inventory')->first();

                if ($defaultVariant) {
                    $product->update([
                        'price' => $defaultVariant->price,
                        'stock' => $defaultVariant->inventory?->quantity ?? 0,
                    ]);
                }
            });
        } catch (\Throwable $exception) {
            foreach ($uploadedPaths as $path) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        foreach (array_unique($deletedImagePaths) as $path) {
            Storage::disk('public')->delete($path);
        }

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Produk, spesifikasi, varian, dan stok berhasil diperbarui.');
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
    
    
    private function availableGalleryImages(): array
    {
        $usedPaths = DB::table('products')
            ->whereNotNull('image')
            ->pluck('image')
            ->merge(
                DB::table('product_images')->pluck('path')
            )
            ->filter()
            ->unique()
            ->all();
    
        return collect(
            Storage::disk('public')->files('products')
        )
            ->filter(function ($path) {
                return in_array(
                    strtolower(pathinfo($path, PATHINFO_EXTENSION)),
                    ['jpg', 'jpeg', 'png', 'webp'],
                    true
                );
            })
            ->reject(function ($path) use ($usedPaths) {
                return in_array($path, $usedPaths, true);
            })
            ->map(function ($path) {
                return [
                    'path' => $path,
                    'filename' => basename($path),
                    'url' => Storage::disk('public')->url($path),
                ];
            })
            ->values()
            ->all();
    }
}
