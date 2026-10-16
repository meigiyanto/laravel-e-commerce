<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['category', 'subCategory'])
            ->latest()
            ->get();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        [$categories, $attributes] = $this->formOptions();

        return view('admin.products.create', compact(
            'categories',
            'attributes'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'sub_category_id' => ['required', Rule::exists('sub_categories', 'id')->where('category_id', $request->input('category_id'))],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'description' => ['nullable', 'string'],

            'sku' => ['required', 'string', 'max:100', 'unique:product_variants,sku'],
            'barcode' => ['nullable', 'string', 'max:100', 'unique:product_variants,barcode'],
            'variant_name' => ['nullable', 'string', 'max:255'],
            
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['active', 'inactive'])],

            'quantity' => ['required', 'integer', 'min:0'],
            'reserved_quantity' => ['required', 'integer', 'min:0', 'lte:quantity'],
            'reorder_level' => ['required', 'integer', 'min:0'],

            'attribute_value_ids' => ['nullable', 'array'],
            'attribute_value_ids.*' => ['integer', 'distinct', 'exists:attribute_values,id'],

            'specifications' => ['nullable', 'array'],
            'specifications.*.specification_group' => ['required', 'string', 'max:255'],
            'specifications.*.specification_name' => ['required', 'string', 'max:255'],
            'specifications.*.specification_value' => ['required', 'string', 'max:2000'],
            'specifications.*.sort_order' => ['nullable', 'integer', 'min:0'],

            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_alt_text' => ['nullable', 'string', 'max:255'],
        ]);

        if (
            filled($data['compare_at_price'] ?? null)
            && (float) $data['compare_at_price'] < (float) $data['price']
        ) {
            throw ValidationException::withMessages([
                'compare_at_price' => 'Harga sebelum diskon harus sama atau lebih tinggi daripada harga jual.',
            ]);
        }

        $valueIds = $data['attribute_value_ids'] ?? [];
        $this->validateAttributeValues($valueIds);

        $slug = $this->uniqueSlug($data['slug'] ?: $data['name']);
        $uploadedPaths = [];

        try {
            $product = DB::transaction(function () use (
                $request,
                $data,
                $slug,
                $valueIds,
                &$uploadedPaths
            ) {
                $product = Product::create([
                    'category_id' => $data['category_id'],
                    'sub_category_id' => $data['sub_category_id'],
                    'name' => $data['name'],
                    'slug' => $slug,
                    'description' => $data['description'] ?? null,
                    'price' => $data['price'],
                    'stock' => $data['quantity'],
                    'image' => null,
                ]);

                // Produk sederhana juga menggunakan satu varian default internal.
                $variant = $product->variants()->create([
                    'sku' => $data['sku'],
                    'barcode' => $data['barcode'] ?? null,
                    'name' => $data['variant_name'] ?: $product->name,
                    'price' => $data['price'],
                    'compare_at_price' => $data['compare_at_price'] ?? null,
                    'cost_price' => $data['cost_price'] ?? null,
                    'weight' => $data['weight'] ?? null,
                    'is_default' => true,
                    'status' => $data['status'],
                ]);

                $variant->attributeValues()->sync($valueIds);

                $variant->inventory()->create([
                    'quantity' => (int) $data['quantity'],
                    'reserved_quantity' => (int) $data['reserved_quantity'],
                    'reorder_level' => (int) $data['reorder_level'],
                ]);

                if ((int) $data['quantity'] > 0) {
                    $variant->inventoryMovements()->create([
                        'type' => 'opening_stock',
                        'quantity' => (int) $data['quantity'],
                        'note' => 'Stok awal saat produk dibuat.',
                        'created_by' => auth()->id(),
                    ]);
                }

                foreach ($data['specifications'] ?? [] as $index => $spec) {
                    $product->specifications()->create([
                        'specification_group' => $spec['specification_group'],
                        'specification_name' => $spec['specification_name'],
                        'specification_value' => $spec['specification_value'],
                        'sort_order' => $spec['sort_order'] ?? $index,
                    ]);
                }

                foreach ($request->file('images', []) as $index => $file) {
                    $path = $file->store('products', 'public');
                    $uploadedPaths[] = $path;

                    $product->images()->create([
                        'product_variant_id' => null,
                        'path' => $path,
                        'alt_text' => $data['image_alt_text'] ?? $product->name,
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
            ->with('success', 'Produk berhasil dibuat.');
    }

    public function edit(Product $product)
    {
        $product->load([
            'specifications',
            'variants.inventory',
            'variants.attributeValues.attribute',
            'images',
        ]);

        [$categories, $attributes] = $this->formOptions();

        return view('admin.products.edit', compact(
            'product',
            'categories',
            'attributes'
        ));
    }

    public function update(Request $request, Product $product)
    {
        $validationRules = [
            'category_id' => ['required', 'exists:categories,id'],
            'sub_category_id' => [
                'required',
                Rule::exists('sub_categories', 'id')
                    ->where('category_id', $request->input('category_id')),
            ],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'specifications' => ['nullable', 'array'],
            'specifications.*.id' => [
                'nullable', 'integer',
                Rule::exists('product_specifications', 'id')
                    ->where('product_id', $product->id),
            ],
            'specifications.*.specification_group' => ['required', 'string', 'max:255'],
            'specifications.*.specification_name' => ['required', 'string', 'max:255'],
            'specifications.*.specification_value' => ['required', 'string', 'max:2000'],
            'specifications.*.sort_order' => ['nullable', 'integer', 'min:0'],

            // Boleh tidak dikirim apabila produk lama belum memiliki varian.
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer', Rule::exists('product_variants', 'id')->where('product_id', $product->id)],
            'variants.*.sku' => ['required', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.name' => ['nullable', 'string', 'max:255'],
            'variants.*.price' => ['required', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.cost_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0'],
            'variants.*.status' => ['required', Rule::in(['active', 'inactive'])],
            'variants.*.quantity' => ['required', 'integer', 'min:0'],
            'variants.*.reorder_level' => ['required', 'integer', 'min:0'],
            'variants.*.attribute_value_ids' => ['nullable', 'array'],
            'variants.*.attribute_value_ids.*' => [
                'integer', 'distinct', 'exists:attribute_values,id',
            ],

            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'image_alt_text' => ['nullable', 'string', 'max:255'],
            'delete_images' => ['nullable', 'array'],
            'delete_images.*' => [
                'integer', 'distinct',
                Rule::exists('product_images', 'id')
                    ->where('product_id', $product->id),
            ],
            'primary_image_id' => [
                'nullable', 'integer',
                Rule::exists('product_images', 'id')
                    ->where('product_id', $product->id),
            ],
        ];
        
        foreach ($request->input('variants', []) as $key => $input) {
            $variantId = filter_var(
                $input['id'] ?? null,
                FILTER_VALIDATE_INT
            );
    
            $validationRules["variants.$key.sku"][] = Rule::unique(
                'product_variants',
                'sku'
            )->ignore($variantId ?: null);
        
            if (filled($input['barcode'] ?? null)) {
                $validationRules["variants.$key.barcode"][] = Rule::unique(
                    'product_variants',
                    'barcode'
                )->ignore($variantId ?: null);
            }
        }

        $data = $request->validate($validationRules);

        $this->validateVariantPrices($data['variants'] ?? []);
        $this->validateAttributeValues(
            collect($data['variants'] ?? [])
                ->flatMap(fn ($variant) => $variant['attribute_value_ids'] ?? [])
                ->unique()
                ->values()
                ->all()
        );

        $deleteIds = array_map('intval', $data['delete_images'] ?? []);

        if (
            filled($data['primary_image_id'] ?? null)
            && in_array((int) $data['primary_image_id'], $deleteIds, true)
        ) {
            throw ValidationException::withMessages([
                'primary_image_id' => 'Gambar utama tidak boleh sekaligus dihapus.',
            ]);
        }

        $slug = $this->uniqueSlug(
            $data['slug'] ?: $data['name'],
            $product->id
        );

        $uploadedPaths = [];
        $deletedPaths = [];

        try {
            DB::transaction(function () use (
                $request,
                $product,
                $data,
                $slug,
                $deleteIds,
                &$uploadedPaths,
                &$deletedPaths
            ) {
                $product = Product::whereKey($product->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $product->update([
                    'category_id' => $data['category_id'],
                    'sub_category_id' => $data['sub_category_id'],
                    'name' => $data['name'],
                    'slug' => $slug,
                    'description' => $data['description'] ?? null,
                ]);

                // Simpan spesifikasi yang dikirim. ID harus milik produk ini.
                $savedSpecificationIds = [];

                foreach ($data['specifications'] ?? [] as $index => $spec) {
                    $attributes = [
                        'specification_group' => $spec['specification_group'],
                        'specification_name' => $spec['specification_name'],
                        'specification_value' => $spec['specification_value'],
                        'sort_order' => $spec['sort_order'] ?? $index,
                    ];

                    if (! empty($spec['id'])) {
                        $record = $product->specifications()
                            ->whereKey($spec['id'])
                            ->firstOrFail();
                        $record->update($attributes);
                    } else {
                        $record = $product->specifications()->create($attributes);
                    }

                    $savedSpecificationIds[] = $record->id;
                }

                $product->specifications()
                    ->whereNotIn('id', $savedSpecificationIds ?: [0])
                    ->delete();

                $variants = $data['variants'] ?? [];

                // Perbaikan inti: data lama tanpa varian dimigrasikan satu kali.
                if ($product->variants()->doesntExist()) {
                    $legacyPrice = (float) $product->price;
                    $legacyStock = max(0, (int) $product->stock);

                    $variant = $product->variants()->create([
                        'sku' => $this->uniqueLegacySku($product),
                        'name' => $product->name,
                        'price' => $legacyPrice,
                        'is_default' => true,
                        'status' => 'active',
                    ]);

                    $variant->inventory()->create([
                        'quantity' => $legacyStock,
                        'reserved_quantity' => 0,
                        'reorder_level' => 0,
                    ]);

                    if ($legacyStock > 0) {
                        $variant->inventoryMovements()->create([
                            'type' => 'opening_stock',
                            'quantity' => $legacyStock,
                            'note' => 'Migrasi stok lama ke inventaris varian.',
                            'created_by' => auth()->id(),
                        ]);
                    }

                    $variants = [];
                }
                
                foreach ($variants as $key => $item) {
                    $isNewVariant = blank($item['id'] ?? null);
                
                    if ($isNewVariant) {
                        $variant = $product->variants()->create([
                            'sku' => $item['sku'],
                            'barcode' => $item['barcode'] ?? null,
                            'name' => filled($item['name'] ?? null)
                                ? $item['name']
                                : $product->name,
                            'price' => $item['price'],
                            'compare_at_price' => $item['compare_at_price'] ?? null,
                            'cost_price' => $item['cost_price'] ?? null,
                            'weight' => $item['weight'] ?? null,
                            'is_default' => false,
                            'status' => $item['status'],
                        ]);
                    } else {
                        $variant = $product->variants()
                            ->whereKey($item['id'])
                            ->lockForUpdate()
                            ->firstOrFail();
                
                        $variant->update([
                            'sku' => $item['sku'],
                            'barcode' => $item['barcode'] ?? null,
                            'name' => filled($item['name'] ?? null)
                                ? $item['name']
                                : $product->name,
                            'price' => $item['price'],
                            'compare_at_price' => $item['compare_at_price'] ?? null,
                            'cost_price' => $item['cost_price'] ?? null,
                            'weight' => $item['weight'] ?? null,
                            'status' => $item['status'],
                        ]);
                    }

                    if (
                        filled($item['compare_at_price'] ?? null)
                        && (float) $item['compare_at_price'] < (float) $item['price']
                    ) {
                        throw ValidationException::withMessages([
                            "variants.$key.compare_at_price" =>
                                'Harga sebelum diskon harus sama atau lebih tinggi daripada harga jual.',
                        ]);
                    }

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

                    if (
                        $values->groupBy('attribute_id')
                            ->contains(fn ($group) => $group->count() > 1)
                    ) {
                        throw ValidationException::withMessages([
                            "variants.$key.attribute_value_ids" =>
                                'Pilih maksimal satu nilai untuk setiap atribut.',
                        ]);
                    }

                    $variant->attributeValues()->sync($valueIds);

                    $inventory = $variant->inventory()
                        ->lockForUpdate()
                        ->first();

                    $quantity = (int) $item['quantity'];
                    $reserved = (int) ($inventory?->reserved_quantity ?? 0);

                    if ($quantity < $reserved) {
                        throw ValidationException::withMessages([
                            "variants.$key.quantity" =>
                                "Stok tidak boleh kurang dari stok yang direservasi ({$reserved}).",
                        ]);
                    }

                    if (! $inventory) {
                        $inventory = $variant->inventory()->create([
                            'quantity' => $quantity,
                            'reserved_quantity' => 0,
                            'reorder_level' => (int) $item['reorder_level'],
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
                            'reorder_level' => (int) $item['reorder_level'],
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

                // Hapus record gambar dalam transaksi; file dihapus setelah commit.
                $imagesToDelete = $product->images()
                    ->whereIn('id', $deleteIds)
                    ->get();

                foreach ($imagesToDelete as $image) {
                    $deletedPaths[] = $image->path;
                    $image->delete();
                }

                foreach ($request->file('images', []) as $file) {
                    $path = $file->store('products', 'public');
                    $uploadedPaths[] = $path;

                    $product->images()->create([
                        'product_variant_id' => null,
                        'path' => $path,
                        'alt_text' => $data['image_alt_text'] ?? $product->name,
                        'sort_order' => (int) ($product->images()->max('sort_order') ?? -1) + 1,
                        'is_primary' => false,
                    ]);
                }

                // Pertahankan gambar utama jika masih ada; jika tidak, pilih gambar pertama.
                $primary = null;

                if (! empty($data['primary_image_id'])) {
                    $primary = $product->images()
                        ->whereKey($data['primary_image_id'])
                        ->firstOrFail();
                } else {
                    $primary = $product->images()
                        ->where('is_primary', true)
                        ->first()
                        ?? $product->images()->orderBy('sort_order')->first();
                }

                $product->images()->update(['is_primary' => false]);

                if ($primary) {
                    $primary->update(['is_primary' => true]);
                    $product->update(['image' => $primary->path]);
                } elseif (in_array($product->getOriginal('image'), $deletedPaths, true)) {
                    $product->update(['image' => null]);
                }

                // Kolom legacy tetap dibutuhkan oleh katalog/cart/checkout lama.
                $defaultVariant = $product->variants()
                    ->where('is_default', true)
                    ->with('inventory')
                    ->first()
                    ?? $product->variants()->with('inventory')->orderBy('id')->first();

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

        foreach (array_unique($deletedPaths) as $path) {
            // Hindari menghapus file yang masih dipakai produk lain.
            if (! DB::table('product_images')->where('path', $path)->exists()) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()
            ->route('admin.products.edit', $product)
            ->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product)
    {
        if ($product->orderItems()->exists()) {
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'Produk tidak dapat dihapus karena sudah digunakan dalam pesanan.');
        }

        $paths = $product->images()->pluck('path')->all();

        DB::transaction(function () use ($product) {
            $product->delete();
        });

        foreach (array_unique($paths) as $path) {
            if (! DB::table('product_images')->where('path', $path)->exists()) {
                Storage::disk('public')->delete($path);
            }
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk berhasil dihapus.');
    }

    private function formOptions(): array
    {
        $categories = Category::with('subCategories')
            ->orderBy('name')
            ->get();

        $attributes = Attribute::with([
            'values' => fn ($query) => $query->orderBy('sort_order'),
        ])->orderBy('sort_order')->get();

        return [$categories, $attributes];
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'product';
        $slug = $base;
        $suffix = 1;

        while (
            Product::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }

    private function uniqueLegacySku(Product $product): string
    {
        $base = 'LEGACY-' . $product->id;
        $sku = $base;
        $suffix = 1;

        while (ProductVariant::where('sku', $sku)->exists()) {
            $sku = $base . '-' . $suffix++;
        }

        return $sku;
    }

    private function validateAttributeValues(array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $uniqueIds = array_unique(array_map('intval', $ids));

        if (AttributeValue::whereIn('id', $uniqueIds)->count() !== count($uniqueIds)) {
            throw ValidationException::withMessages([
                'attribute_value_ids' => 'Salah satu nilai atribut yang dipilih tidak valid.',
            ]);
        }
    }

    private function validateVariantPrices(array $variants): void
    {
        foreach ($variants as $key => $variant) {
            if (
                filled($variant['compare_at_price'] ?? null)
                && (float) $variant['compare_at_price'] < (float) ($variant['price'] ?? 0)
            ) {
                throw ValidationException::withMessages([
                    "variants.$key.compare_at_price" =>
                        'Harga sebelum diskon harus sama atau lebih tinggi daripada harga jual.',
                ]);
            }
        }
    }
}