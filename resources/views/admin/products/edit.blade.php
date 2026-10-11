@extends('layouts.admin')

@section('title', 'Edit Product')
@section('header', 'Edit Product')

@section('content')
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">Edit Product</h3>
            <p class="text-soft">
                Kelola informasi produk, spesifikasi, varian, dan inventaris.
            </p>
        </div>
        <div class="nk-block-head-content">
            <a href="{{ route('admin.products.index') }}"
               class="btn btn-outline-primary">Kembali</a>
        </div>
    </div>
</div>

@if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Periksa kembali input berikut:</strong>
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
        <form method="POST"
              action="{{ route('admin.products.update', $product) }}"
              enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <h5>1. Informasi Produk</h5>

            <div class="form-group">
                <label for="name">Nama Produk *</label>
                <input class="form-control" id="name" name="name" value="{{ old('name', $product->name) }}" required>
            </div>

            <div class="form-group">
                <label for="slug">Slug</label>
                <input class="form-control" id="slug" name="slug" value="{{ old('slug', $product->slug) }}">
            </div>

            <div class="form-group">
                <label for="category_id">Kategori *</label>
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

            <div class="form-group">
                <label for="sub_category_id">Subkategori *</label>
                <select class="form-control" id="sub_category_id"
                        name="sub_category_id" required>
                    <option value="">Pilih subkategori</option>
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

            <div class="form-group">
                <label for="description">Deskripsi</label>
                <textarea class="form-control" id="description"
                          name="description" rows="5">{{ old('description', $product->description) }}</textarea>
            </div>

            <hr>
            <div class="d-flex justify-content-between align-items-center">
                <h5>2. Spesifikasi Produk</h5>
            </div>

            <p class="text-soft">
                Spesifikasi deskriptif seperti resolusi layar, bahan, atau
                jenis prosesor. SKU bukan bagian dari spesifikasi.
            </p>

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
                            @if (!empty($spec['id']))
                                <input type="hidden" name="specifications[{{ $index }}][id]" value="{{ $spec['id'] }}">
                            @endif
                            <tr class="specification-item">
                                <td>
                                    <input type="checkbox" value="" class="spec-input">
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input class="form-control" name="specifications[{{ $index }}][specification_group]" value="{{ $spec['specification_group'] ?? '' }}">
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input class="form-control" name="specifications[{{ $index }}][specification_name]" value="{{ $spec['specification_name'] ?? '' }}">
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input class="form-control" name="specifications[{{ $index }}][specification_value]" value="{{ $spec['specification_value'] ?? '' }}">
                                    </div>
                                </td>
                                <td>
                                    <div class="form-group">
                                        <input class="form-control" name="specifications[{{ $index }}][sort_order]" value="{{ $spec['sort_order'] ?? '' }}">
                                    </div>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-outline-danger remove-specification">
                                        <em class="ni ni-trash"></em>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>
                                <input type="checkbox" value="" id="check-all">
                            </td>
                            <td colspan="5">
                                <button type="button" class="btn btn-danger">
                                    <em class="ni ni-trash"></em>
                                    Delete Selected
                                </button>
                            </td>
                        <tr>
                    </tfoot>
                </table>
            </div>

            <hr>
            <h5>3. Varian, SKU, dan Inventaris</h5>
            <p class="text-soft">
                Perubahan di bawah berlaku pada varian yang sudah ada.
                Stok tidak boleh lebih rendah daripada jumlah yang direservasi.
            </p>

            @forelse ($product->variants as $variant)
                @php
                    $variantKey = $variant->id;
                    $oldVariant = old("variants.$variantKey", []);
                    $selectedValueIds = $oldVariant['attribute_value_ids']
                        ?? $variant->attributeValues->pluck('id')->all();
                    $inventory = $variant->inventory;
                @endphp

                <div class="border rounded p-3 mb-4">
                    <input type="hidden"
                           name="variants[{{ $variantKey }}][id]"
                           value="{{ $variant->id }}">

                    <h6>
                        Varian #{{ $variant->id }}
                        @if ($variant->is_default)
                            <span class="badge badge-primary">Default</span>
                        @endif
                    </h6>

                    <div class="form-group">
                        <label>Nama Varian</label>
                        <input class="form-control"
                               name="variants[{{ $variantKey }}][name]"
                               value="{{ old("variants.$variantKey.name", $variant->name) }}">
                    </div>

                    <div class="form-group">
                        <label>SKU *</label>
                        <input class="form-control"
                               name="variants[{{ $variantKey }}][sku]"
                               value="{{ old("variants.$variantKey.sku", $variant->sku) }}"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Barcode</label>
                        <input class="form-control"
                               name="variants[{{ $variantKey }}][barcode]"
                               value="{{ old("variants.$variantKey.barcode", $variant->barcode) }}">
                    </div>

                    <div class="form-group">
                        <label>Harga Jual *</label>
                        <input type="number" min="0" step="0.01"
                               class="form-control"
                               name="variants[{{ $variantKey }}][price]"
                               value="{{ old("variants.$variantKey.price", $variant->price) }}"
                               required>
                    </div>

                    <div class="form-group">
                        <label>Harga Sebelum Diskon</label>
                        <input type="number" min="0" step="0.01"
                               class="form-control"
                               name="variants[{{ $variantKey }}][compare_at_price]"
                               value="{{ old("variants.$variantKey.compare_at_price", $variant->compare_at_price) }}">
                    </div>

                    <div class="form-group">
                        <label>Harga Modal</label>
                        <input type="number" min="0" step="0.01"
                               class="form-control"
                               name="variants[{{ $variantKey }}][cost_price]"
                               value="{{ old("variants.$variantKey.cost_price", $variant->cost_price) }}">
                    </div>

                    <div class="form-group">
                        <label>Berat (kg)</label>
                        <input type="number" min="0" step="0.001"
                               class="form-control"
                               name="variants[{{ $variantKey }}][weight]"
                               value="{{ old("variants.$variantKey.weight", $variant->weight) }}">
                    </div>

                    <div class="form-group">
                        <label>Status</label>
                        <select class="form-control"
                                name="variants[{{ $variantKey }}][status]" required>
                            <option value="active"
                                @selected(old("variants.$variantKey.status", $variant->status) === 'active')>
                                Aktif
                            </option>
                            <option value="inactive"
                                @selected(old("variants.$variantKey.status", $variant->status) === 'inactive')>
                                Nonaktif
                            </option>
                        </select>
                    </div>

                    <h6>Atribut Varian</h6>
                    <p class="text-soft">
                        Pilih maksimal satu nilai untuk setiap atribut.
                    </p>

                    @foreach ($attributes as $attribute)
                        <div class="form-group">
                            <label for="attribute-{{ $variantKey }}-{{ $attribute->id }}">
                                {{ $attribute->name }}
                            </label>

                            <select class="form-control variant-attribute"
                                    id="attribute-{{ $variantKey }}-{{ $attribute->id }}"
                                    data-attribute-id="{{ $attribute->id }}"
                                    name="variants[{{ $variantKey }}][attribute_value_ids][]">
                                <option value="">Tidak dipilih</option>
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

                    <h6>Inventaris</h6>

                    <div class="form-group">
                        <label>Stok *</label>
                        <input type="number" min="0" class="form-control"
                               name="variants[{{ $variantKey }}][quantity]"
                               value="{{ old("variants.$variantKey.quantity", $inventory?->quantity ?? 0) }}"
                               required>
                        <small class="text-soft">
                            Perubahan stok akan dicatat sebagai pergerakan inventaris.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Stok Direservasi</label>
                        <input type="number" class="form-control"
                               value="{{ $inventory?->reserved_quantity ?? 0 }}"
                               readonly>
                        <small class="text-soft">
                            Tidak diubah dari form ini.
                        </small>
                    </div>

                    <div class="form-group">
                        <label>Batas Minimum Stok *</label>
                        <input type="number" min="0" class="form-control"
                               name="variants[{{ $variantKey }}][reorder_level]"
                               value="{{ old("variants.$variantKey.reorder_level", $inventory?->reorder_level ?? 0) }}"
                               required>
                    </div>
                </div>
            @empty
                <div class="alert alert-warning">
                    Produk ini belum memiliki varian. Buat varian terlebih dahulu
                    sebelum mengedit SKU dan inventaris.
                </div>
            @endforelse

            <hr>
            <h5>4. Gambar Produk</h5>

            @if ($product->images->isNotEmpty())
                @php
                    $productImages = $product->images;
                    $hasLegacyImage = filled($product->image);

                    // Jika tabel product_images kosong, tampilkan gambar
                    // lama dari kolom products.image.
                    $showLegacyImage = $productImages->isEmpty() && $hasLegacyImage;
                @endphp

                @if ($productImages->isNotEmpty() || $showLegacyImage)
                    <div class="row mb-3">
                        @foreach ($productImages as $image)
                            <div class="col-6 col-md-3 mb-3">
                                <div class="border rounded p-2">
                                    <img
                                        src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->path) }}"
                                        alt="{{ $image->alt_text ?? $product->name }}"
                                        class="img-fluid rounded"
                                        style="width: 100%; height: 180px; object-fit: contain;"
                                        onerror="this.style.display='none';"
                                    >

                                    @if ($image->is_primary)
                                        <small class="d-block mt-2">Gambar utama</small>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        @if ($showLegacyImage)
                            <div class="col-6 col-md-3 mb-3">
                                <div class="border rounded p-2">
                                    <img
                                        src="{{ $product->image_url }}"
                                        alt="{{ $product->name }}"
                                        class="img-fluid rounded"
                                        style="width: 100%; height: 180px; object-fit: contain;"
                                        onerror="this.style.display='none';"
                                    >

                                    <small class="d-block mt-2">
                                        Gambar produk lama
                                    </small>
                                </div>
                            </div>
                        @endif
                    </div>
                @else
                    <p class="text-soft">
                        Belum ada gambar yang tersimpan untuk produk ini.
                    </p>
                @endif
                </div>
            @endif

            <div class="form-group mb-4">
                <label class="form-label">Gambar Produk Saat Ini</label>

                @if ($product->image)
                    <div class="mb-3">
                        <img
                            src="{{ $product->image_url }}"
                            alt="{{ $product->name }}"
                            class="img-fluid rounded border"
                            style="width: 220px; max-height: 220px; object-fit: contain;"
                        >
                    </div>
                @else
                    <p class="text-soft">
                        Belum ada gambar produk.
                    </p>
                @endif

                <label for="image" class="form-label">
                    Ganti Gambar (Opsional)
                </label>

                <input
                    type="file"
                    name="image"
                    id="image"
                    class="form-control"
                    accept="image/*"
                >

                @error('image')
                    <small class="text-danger">{{ $message }}</small>
                @enderror

                <small class="text-soft">
                    Kosongkan jika ingin mempertahankan gambar saat ini.
                </small>
            </div>
            <!--
            <div class="form-group">
                <label for="images">Tambah Gambar</label>
                <input type="file" class="form-control" id="images"
                       name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple>
                <small class="text-soft">
                    JPG, PNG, atau WebP. Maksimal 10 file per pengiriman,
                    5 MB per file. Gambar lama tetap tersimpan.
                </small>
            </div>

            <div class="form-group">
                <label for="image_alt_text">Teks Alternatif Gambar Baru</label>
                <input class="form-control" id="image_alt_text"
                       name="image_alt_text"
                       value="{{ old('image_alt_text', $product->name) }}">
            </div>
            -->

            <div class="form-actions mt-4">
                <a href="{{ route('admin.products.index') }}"
                   class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<template id="specification-template">
    <tr class="specification-item">
        <td>
            <input type="checkbox" value="">
        </td>
        <td>
            <div class="form-group">
                <input class="form-control" data-field="specification_group" required>
            </div>
        </td>
        <td>
            <div class="form-group">
                <input class="form-control" data-field="specification_name" required>
            </div>
        </td>
        <td>
            <div class="form-group">
                <input class="form-control" data-field="specification_value" required>
            </div>
        </td>
        <td>
            <div class="form-group">
                <input type="number" min="0" class="form-control" data-field="sort_order" value="0">
            </div>
        </td>
        <td>
            <button type="button" class="btn btn-outline-danger remove-specification">
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
    const template = document.getElementById('specification-template');
    let nextIndex = {{ count($specificationRows) }};

    document.getElementById('add-specification')
        .addEventListener('click', function () {
            const fragment = template.content.cloneNode(true);
            const row = fragment.querySelector('.specification-item');

            row.querySelectorAll('[data-field]').forEach(function (input) {
                input.name = `specifications[${nextIndex}][${input.dataset.field}]`;
            });

            container.children[0].children[0].nextElementSibling.appendChild(fragment);
            nextIndex++;
        });

    container.addEventListener('click', function (event) {
        const button = event.target.closest('.remove-specification');

        if (button) {
            button.closest('.specification-item').remove();
        }
    });

    // Dropdown atribut: pilihan kosong tidak ikut dikirim.
    // Hanya satu nilai yang dipilih untuk setiap atribut pada setiap varian.
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

    // console.log([...document.querySelectorAll('.spec-input')].length);
});
</script>
@endsection
