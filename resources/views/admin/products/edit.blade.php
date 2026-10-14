@extends('layouts.admin')

@section('title', 'Edit Product')
@section('header', 'Edit Product')

@section('content')
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-between">
        
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">Edit Product</h3>
            <div class="nk-block-des text-soft">
                <p>Manage product information, specifications, variants, and inventory.</p>
            </div>
        </div>
        
        <div class="nk-block-head-content">
            <a href="{{ route('admin.products.index') }}" class="btn btn-white btn-outline-light">
                <em class="icon ni ni-arrow-left"></em>
                <span>Go back</span>
            </a>
        </div>
        
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Double-check the following input.:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php
    $specificationRows = old(
        'specifications',
        $product->specifications->map(fn ($spec) => [
            'id' => $spec->id,
            'specification_group' => $spec->specification_group,
            'specification_name' => $spec->specification_name,
            'specification_value' => $spec->specification_value,
            'sort_order' => $spec->sort_order,
        ])->values()->all()
    );
@endphp

<div class="card card-bordered">
    <div class="card-inner">
        <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <h5>1. Product Information</h5>

            <div class="form-group">
                <label for="name">Product name *</label>
                <input class="form-control" id="name" name="name" value="{{ old('name', $product->name) }}" required>
            </div>

            <div class="form-group">
                <label for="slug">Slug</label>
                <input class="form-control" id="slug" name="slug" value="{{ old('slug', $product->slug) }}">
            </div>

            <div class="row">

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select class="form-control" id="category_id"
                                name="category_id" required>
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    @selected(old('category_id', $product->category_id) == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sub_category_id">Subcategory *</label>
                        <select class="form-control" id="sub_category_id"
                                name="sub_category_id" required>
                            <option value="">Select a subcategory</option>
                            @foreach ($categories as $category)
                                @foreach ($category->subCategories as $subCategory)
                                    <option value="{{ $subCategory->id }}"
                                        data-category="{{ $category->id }}"
                                        @selected(old('sub_category_id', $product->sub_category_id) == $subCategory->id)>
                                        {{ $subCategory->name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>

            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="5">{{ old('description', $product->description) }}</textarea>
            </div>

            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <h5>2. Product Specifications</h5>
            </div>

            <p class="text-soft">Descriptive specifications such as screen resolution, materials, or processor type. The SKU is not part of the specifications.</p>

            <div id="specifications-container">
                <table class="table table-hover table-striped nowrap">
                    <thead>
                        <tr>
                            <th></th>
                            <th>Group</th>
                            <th>Specification Name</th>
                            <th>Value</th>
                            <th>Order</th>
                            <th>
                                <button type="button" class="btn btn-outline-secondary" id="add-specification">
                                    <em class="ni ni-plus"></em>
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($specificationRows as $index => $spec)
                        <tr class="specification-item">
                            <td>
                                @if (!empty($spec['id']))
                                    <input
                                        type="hidden"
                                        name="specifications[{{ $index }}][id]"
                                        value="{{ $spec['id'] }}"
                                    >
                                @endif
                    
                                <input
                                    type="checkbox"
                                    class="spec-input"
                                    aria-label="Pilih spesifikasi untuk dihapus"
                                >
                            </td>
                    
                            <td>
                                <input
                                    class="form-control"
                                    name="specifications[{{ $index }}][specification_group]"
                                    value="{{ $spec['specification_group'] ?? '' }}"
                                    maxlength="255"
                                    required
                                >
                            </td>
                    
                            <td>
                                <input
                                    class="form-control"
                                    name="specifications[{{ $index }}][specification_name]"
                                    value="{{ $spec['specification_name'] ?? '' }}"
                                    maxlength="255"
                                    required
                                >
                            </td>
                    
                            <td>
                                <input
                                    class="form-control"
                                    name="specifications[{{ $index }}][specification_value]"
                                    value="{{ $spec['specification_value'] ?? '' }}"
                                    maxlength="2000"
                                    required
                                >
                            </td>
                    
                            <td>
                                <input
                                    type="number"
                                    class="form-control"
                                    name="specifications[{{ $index }}][sort_order]"
                                    value="{{ $spec['sort_order'] ?? 0 }}"
                                    min="0"
                                    step="1"
                                >
                            </td>
                    
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-outline-danger remove-specification"
                                    aria-label="Hapus spesifikasi"
                                >
                                    <em class="ni ni-trash"></em>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>
                                <input type="checkbox" id="check-all">
                            </td>
                            <td colspan="5">
                                <button type="button" id="btn-delete" class="btn btn-danger" disabled>
                                    <em class="ni ni-trash"></em>
                                    Delete Selected
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <hr>
            <h5>3. Variants, SKUs, and Inventory</h5>
            <p class="text-soft">The changes below apply to existing variants. Stock cannot be lower than the reserved quantity.</p>

            @forelse ($product->variants as $variant)
                @php
                    $variantKey = $variant->id;
                    $oldVariant = old("variants.$variantKey", []);
                    $selectedValueIds = $oldVariant['attribute_value_ids'] ?? $variant->attributeValues->pluck('id')->all();
                    $inventory = $variant->inventory;
                @endphp

                <div class="border rounded p-3 mb-4">
                    <input type="hidden" name="variants[{{ $variantKey }}][id]" value="{{ $variant->id }}">
                    <h6>Varian #{{ $variant->id }}
                        @if ($variant->is_default)
                            <span class="badge badge-primary">Default</span>
                        @endif
                    </h6>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Variant Name</label>
                                <input class="form-control" name="variants[{{ $variantKey }}][name]" value="{{ old("variants.$variantKey.name", $variant->name) }}">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>SKU *</label>
                                <input class="form-control" name="variants[{{ $variantKey }}][sku]" value="{{ old("variants.$variantKey.sku", $variant->sku) }}" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Barcode</label>
                                <input class="form-control" name="variants[{{ $variantKey }}][barcode]" value="{{ old("variants.$variantKey.barcode", $variant->barcode) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Selling price *</label>
                                <input type="number" min="0" step="0.01" class="form-control" name="variants[{{ $variantKey }}][price]" value="{{ old("variants.$variantKey.price", $variant->price) }}" required>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Price Before Discount</label>
                                <input type="number" min="0" step="0.01" class="form-control" name="variants[{{ $variantKey }}][compare_at_price]" value="{{ old("variants.$variantKey.compare_at_price", $variant->compare_at_price) }}">
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Capital price</label>
                                <input type="number" min="0" step="0.01" class="form-control" name="variants[{{ $variantKey }}][cost_price]" value="{{ old("variants.$variantKey.cost_price", $variant->cost_price) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Weight (kg)</label>
                                <input type="number" min="0" step="0.001" class="form-control" name="variants[{{ $variantKey }}][weight]" value="{{ old("variants.$variantKey.weight", $variant->weight) }}">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" name="variants[{{ $variantKey }}][status]" required>
                                    <option value="active" @selected(old("variants.$variantKey.status", $variant->status) === 'active')>Active</option>
                                    <option value="inactive" @selected(old("variants.$variantKey.status", $variant->status) === 'inactive')>Non-active</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <h6>Variant Attributes</h6>
                    <p class="text-soft">Select a maximum of one value for each attribute.</p>

                    @foreach ($attributes as $attribute)
                        <div class="form-group">
                            <label for="attribute-{{ $variantKey }}-{{ $attribute->id }}">{{ $attribute->name }}</label>
                            <select class="form-control variant-attribute"
                                    id="attribute-{{ $variantKey }}-{{ $attribute->id }}"
                                    data-attribute-id="{{ $attribute->id }}"
                                    name="variants[{{ $variantKey }}][attribute_value_ids][]">
                                <option value="">Not selected</option>
                                @foreach ($attribute->values as $value)
                                    <option value="{{ $value->id }}"
                                            data-attribute-id="{{ $attribute->id }}"
                                        @selected(in_array($value->id, $selectedValueIds))>
                                        {{ $value->value }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach

                    <h6>Inventory</h6>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Stock *</label>
                                <input type="number" min="0" class="form-control" name="variants[{{ $variantKey }}][quantity]" value="{{ old("variants.$variantKey.quantity", $inventory?->quantity ?? 0) }}" required>
                                <small class="text-soft">
                                    Changes in stock will be recorded as inventory movements.
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Reserved Stock</label>
                                <input type="number" class="form-control" value="{{ $inventory?->reserved_quantity ?? 0 }}" readonly>
                                <small class="text-soft">
                                    Unchanged from this form.
                                </small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Minimum Stock Level *</label>
                                <input type="number" min="0" class="form-control" name="variants[{{ $variantKey }}][reorder_level]" value="{{ old("variants.$variantKey.reorder_level", $inventory?->reorder_level ?? 0) }}" required>
                            </div>
                        </div>
                    </div>
                </div>
                
                @empty
                    <div class="alert alert-warning" role="alert">This product does not yet have any variants. Create a variant first before editing the SKU and inventory.</div>
                @endforelse

                <hr>
                
            {{-- Tombol untuk form data produk utama --}}
            <div class="form-actions mt-4 mb-4">
                <a href="{{ route('admin.products.index') }}"
                   class="btn btn-secondary">
                    Cancel
                </a>
            
                <button type="submit" class="btn btn-primary">
                    Save Product Changes
                </button>
            </div>
        
        {{-- Tutup form data produk utama --}}
        </form>
    </div>
</div>

<div class="card card-bordered">
    <div class="card-inner">

    <h5>5. Product Images</h5>
    
    <p class="text-soft">Manage images separately. Changes to images do not alter product variants, prices, stock, or specifications.</p>

    <form method="POST" action="{{ route('admin.products.images.update', $product) }}" enctype="multipart/form-data">
            @csrf
            @method('PATCH')

            @php
                $productImages = $product->images;
            
                // 1. Gambar utama yang terhubung melalui product_images.
                $primaryImage = $productImages->firstWhere(
                    'is_primary',
                    true
                ) ?? $productImages->first();
            
                $primaryImageUrl = null;
            
                if (
                    $primaryImage &&
                    is_string($primaryImage->path) &&
                    trim($primaryImage->path) !== '' &&
                    Storage::disk('public')->exists($primaryImage->path)
                ) {
                    $primaryImageUrl = Storage::disk('public')
                        ->url($primaryImage->path);
                }
            
                // 2. Gambar lama dari products.image.
                // Hanya izinkan path lokal, bukan URL eksternal.
                $legacyImagePath = $product->image;
                $legacyImageUrl = null;
            
                if (
                    is_string($legacyImagePath) &&
                    trim($legacyImagePath) !== '' &&
                    ! filter_var($legacyImagePath, FILTER_VALIDATE_URL) &&
                    ! str_starts_with($legacyImagePath, '//')
                ) {
                    $legacyImagePath = ltrim(trim($legacyImagePath), '/');
            
                    if (str_starts_with($legacyImagePath, 'storage/')) {
                        $legacyImagePath = substr(
                            $legacyImagePath,
                            strlen('storage/')
                        );
                    }
            
                    if (Storage::disk('public')->exists($legacyImagePath)) {
                        $legacyImageUrl = Storage::disk('public')
                            ->url($legacyImagePath);
                    }
                }
            
                // 3. Fallback lokal. Tidak menghasilkan URL gambar
                // yang rusak jika file default belum tersedia.
                $defaultImageUrl = null;
            
                if (is_file(public_path('images/product/a.png'))) {
                    $defaultImageUrl = asset('images/product/a.png');
                } elseif (Storage::disk('public')->exists('placeholder.png')) {
                    $defaultImageUrl = Storage::disk('public')
                        ->url('placeholder.png');
                }
            
                $displayImageUrl = $primaryImageUrl
                    ?: $legacyImageUrl
                    ?: $defaultImageUrl;
            
                $displayImageSource = $primaryImageUrl
                    ? 'Gambar utama yang terhubung'
                    : ($legacyImageUrl
                        ? 'Gambar lama dari products.image'
                        : ($defaultImageUrl
                            ? 'Gambar default lokal'
                            : null));
            @endphp
            
            <h6>Current Main Image</h6>
            
            <div class="card card-bordered mb-4">
                <div class="card-inner">
                    @if ($displayImageUrl)
                        <img
                            src="{{ $displayImageUrl }}"
                            alt="{{ $product->name }}"
                            class="img-fluid img-responsive mb-3"
                            style="max-width: 320px; max-height: 320px; object-fit: contain;"
                        >
            
                        <p class="text-soft mb-0">
                            {{ $displayImageSource }}
                        </p>
            
                        @if (! $primaryImageUrl && $legacyImageUrl)
                            <div class="alert alert-info mt-3 mb-0">
                                Gambar masih berasal dari kolom lama
                                <code>products.image</code>.
                                Gambar tidak akan diunggah ulang hanya karena
                                halaman edit dibuka.
                            </div>
                        @elseif (! $primaryImageUrl && ! $legacyImageUrl)
                            <div class="alert alert-secondary mt-3 mb-0">
                                Ini hanya gambar fallback untuk tampilan.
                                Gambar tersebut belum tentu tersimpan sebagai
                                gambar produk.
                            </div>
                        @endif
                    @else
                        <div class="alert alert-warning mb-0">
                            Belum ada gambar utama yang dapat ditampilkan.
                            Periksa path gambar produk atau sediakan gambar
                            default lokal.
                        </div>
                    @endif
                </div>
            </div>
            
            <h6>Available Gallery Images</h6>
            
            <p class="text-soft">
                Pilih gambar galeri yang belum terhubung untuk menambahkannya
                ke produk. Preview gambar utama di atas tetap ditampilkan
                terlepas dari ketersediaan gambar galeri.
            </p>

            @if (count($availableGalleryImages))
                <div class="gallery-images-container mb-4" style="height: 320px; overflow-y: auto; overflow-x: hidden; border: 2px solid lightgrey; border-radius: 4px; margin: 6px; padding: 6px;">
                    <div class="row">
                        @foreach ($availableGalleryImages as $image)
                            <div class="col-md-4 col-lg-2 mb-3">
                                <div class="card-inner px-2">
                                    <div class="custom-control custom-radio image-control mb-2">
                                        <input type="radio" class="custom-control-input" id="gallery-image-{{ $loop->index }}" name="existing_image_paths[]" value="{{ $image['path'] }}" @checked(in_array($image['path'], old('existing_image_paths', [])))>
                                        <label class="custom-control-label" for="gallery-image-{{ $loop->index }}" title="{{ $image['filename'] }}">
                                            <img src="{{ $image['url'] }}" alt="{{ $image['filename'] }}" class="img-fluid img-responsive mb-2" loading="lazy">
                                         </label>
                                    </div>
    
                                    <small class="text-soft d-block text-break">{{ $image['filename'] }}</small>
                                    <small class="text-soft">Unlinked yet</small>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            
            @else
                <div class="alert alert-info" role="alert">
                    Belum ada gambar galeri yang tersedia untuk dihubungkan.
                    Gambar utama produk tetap ditampilkan pada bagian sebelumnya.
                </div>
            @endif

            {{-- Gambar yang sudah terhubung --}}
            <h5>Connected Images</h5>
        
            {{-- Gambar galeri yang belum terhubung --}}
            <hr>
        
            {{-- Unggah gambar baru --}}
            <div class="row mb-3">
                <div class="col-md-6">
                    @if ($productImages->isNotEmpty())
                        @foreach ($productImages as $image)
                            @php
                                $imageExists = is_string($image->path) && Storage::disk('public')->exists($image->path);
                                $imageUrl = $imageExists ? Storage::disk('public')->url($image->path) : null;
                                $primaryImage = $productImages->firstWhere('is_primary', true) ?? $productImages->first();
                            @endphp
            
                            <div class="card card-bordered">
                                <div class="card-inner">
                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $image->alt_text ?: $product->name }}" class="img-fluid img-responsive mb-3">
                                    @else
                                        <div class="d-flex align-items-center justify-content-center mb-3" style="height: 150px;">
                                            <span class="text-soft">Image unavailable</span>
                                        </div>
                                    @endif
                                    <div class="d-flex justify-content-between">
                                        <div class="custom-control custom-radio mb-2">
                                            <input type="radio" class="custom-control-input" id="primary-image-{{ $image->id }}" name="primary_image_id" value="{{ $image->id }}" @checked($primaryImage?->id === $image->id)>
                                            <label class="custom-control-label" for="primary-image-{{ $image->id }}">Main image</label>
                                        </div>
                                        
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="delete-image-{{ $image->id }}" name="delete_images[]" value="{{ $image->id }}">
                                            <label class="custom-control-label" for="delete-image-{{ $image->id }}">Delete image</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div class="alert alert-info" role="alert">
                            No images are currently linked via the product_images table. You can select gallery images below.
                        </div>
                    @endif
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="product-images-upload">Upload New Images</label>
        
                        <input
                            type="file"
                            id="product-images-upload"
                            name="images[]"
                            accept="image/jpeg,image/png,image/webp"
                            multiple
                            class="form-control"
                        >
                
                        <small class="text-soft">Format JPG, PNG, atau WebP; maximum 5 MB per image.</small>
                        
                    </div>
                    <div class="form-group">
                        <label for="image-alt-text">Alt text</label>
                
                        <input
                            type="text"
                            id="image-alt-text"
                            name="image_alt_text"
                            class="form-control"
                            maxlength="255"
                            value="{{ old('image_alt_text', $product->name) }}"
                        >
                    </div>
                </div>
            </div>
        
            <div class="form-actions mt-4">
                <a
                    href="{{ route('admin.products.edit', $product) }}"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Save Images
                </button>
            </div>
        </form>
        
        <p class="mt-3">(**)<br>
        - Existing images will be retained if no new images are uploaded.<br>
        - Existing images will remain unless you choose to delete them.</p>
    </div>
</div>

<template id="specification-template">
    <tr class="specification-item">
        <td>
            <input
                type="checkbox"
                class="spec-input"
                aria-label="Pilih spesifikasi untuk dihapus"
            >
        </td>

        <td>
            <input
                class="form-control"
                data-field="specification_group"
                maxlength="255"
                required
            >
        </td>

        <td>
            <input
                class="form-control"
                data-field="specification_name"
                maxlength="255"
                required
            >
        </td>

        <td>
            <input
                class="form-control"
                data-field="specification_value"
                maxlength="2000"
                required
            >
        </td>

        <td>
            <input
                type="number"
                class="form-control"
                data-field="sort_order"
                min="0"
                step="1"
                value="0"
            >
        </td>

        <td>
            <button
                type="button"
                class="btn btn-outline-danger remove-specification"
                aria-label="Hapus spesifikasi"
            >
                <em class="ni ni-trash"></em>
            </button>
        </td>
    </tr>
</template>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const category = document.getElementById('category_id');
    const subCategory = document.getElementById('sub_category_id');

    function filterSubCategories() {
        const categoryId = category.value;

        Array.from(subCategory.options).forEach(function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            option.hidden = option.dataset.category !== categoryId;

            if (option.hidden && option.selected) {
                subCategory.value = '';
            }
        });
    }

    category.addEventListener('change', filterSubCategories);
    filterSubCategories();
    
    const container = document.getElementById('specifications-container');
    const tbody = container.querySelector('tbody');
    const template = document.getElementById('specification-template');
    
    const existingIndexes = @json(array_map(
        'intval',
        array_keys($specificationRows)
    ));
    
    let nextIndex = existingIndexes.length
        ? Math.max(...existingIndexes) + 1
        : 0;

    document.getElementById('add-specification').addEventListener('click', function () {
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('.specification-item');

        row.querySelectorAll('[data-field]').forEach(function (input) {
            input.name =
                `specifications[${nextIndex}][${input.dataset.field}]`;
        });

        tbody.appendChild(fragment);
        nextIndex++;
    });

    container.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-specification');

        if (button) {
            button.closest('.specification-item').remove();
        }
    });

    document.querySelectorAll('.variant-attribute').forEach(function (select) {
        select.addEventListener('change', function () {
            if (!this.value) {
                this.disabled = true;
                this.dataset.empty = '1';
            } else {
                this.disabled = false;
                delete this.dataset.empty;
            }
        });

        if (!select.value) {
            select.disabled = true;
            select.dataset.empty = '1';
        }
    });

    const selectAllCheckbox = document.getElementById('check-all');
    const deleteBtn = document.getElementById('btn-delete');
    
    function updateButtonStatus() {
        const checkedBoxes = document.querySelectorAll(
            '.spec-input:checked'
        );
    
        deleteBtn.disabled = checkedBoxes.length === 0;
    }
    
    
    document.querySelector('#specifications-container table tbody').addEventListener('change', function(e) {
        if (e.target.classList.contains('spec-input')) {
            updateButtonStatus();
        }
    });
    
    selectAllCheckbox.addEventListener('change', function() {
        const itemCheckboxes = document.querySelectorAll('.spec-input');
        itemCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateButtonStatus();
    });
    
    deleteBtn.addEventListener('click', () => {
        const checkedBoxes = document.querySelectorAll('.spec-input:checked');
        
        if (confirm(`Apakah Anda yakin ingin menghapus ${checkedBoxes.length} item?`)) {
            checkedBoxes.forEach(checkbox => {
                const row = checkbox.closest('tr'); 
                if (row) row.remove();
            });
            
            selectAllCheckbox.checked = false;
            deleteBtn.disabled = true; 
        }
    });
});
</script>
@endsection
