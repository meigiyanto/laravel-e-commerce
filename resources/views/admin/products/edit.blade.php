@extends('layouts.admin')

@section('title', 'Edit Produk')
@section('header', 'Edit Produk')

@section('content')
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">Edit Produk</h3>
            <p class="text-soft">Kelola informasi, spesifikasi, varian, inventaris, dan gambar produk.</p>
        </div>
        <div class="nk-block-head-content">
            <a href="{{ route('admin.products.index') }}"
               class="btn btn-white btn-outline-light">
                <em class="icon ni ni-arrow-left"></em> Kembali
            </a>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Periksa input berikut:</strong>
        <ul class="mb-0">
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

            <div class="form-group mb-2">
                <label for="name" class="control-label">Nama produk *</label>
                <input class="form-control" id="name" name="name"
                       value="{{ old('name', $product->name) }}"
                       maxlength="255" required>
            </div>

            <div class="form-group mb-2">
                <label for="slug" class="control-label">Slug</label>
                <input class="form-control" id="slug" name="slug"
                       value="{{ old('slug', $product->slug) }}">
            </div>

            <div class="row">
                <div class="col-md-6 mb-2">
                    <div class="form-group">
                        <label for="category_id" class="control-label">Kategori *</label>
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

                <div class="col-md-6 mb-2">
                    <div class="form-group">
                        <label for="sub_category_id" class="control-label">Subkategori *</label>
                        <select class="form-control" id="sub_category_id"
                                name="sub_category_id" required>
                            <option value="">Pilih subkategori</option>
                            @foreach ($categories as $category)
                                @foreach ($category->subCategories as $subCategory)
                                    <option value="{{ $subCategory->id }}"
                                            data-category="{{ $category->id }}"
                                        @selected(old('sub_category_id', $product->sub_category_id) == $subCategory->id)>
                                        {{ $category->name }} — {{ $subCategory->name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="description" class="control-label">Deskripsi</label>
                <textarea class="form-control" id="description"
                          name="description" rows="5">{{ old('description', $product->description) }}</textarea>
            </div>

            <hr>
            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div>
                    <h5>5. Spesifikasi Produk</h5>
                    <p class="text-soft">
                        Contoh: Material — Kulit sintetis; Warna — Hitam.
                    </p>
                </div>
                <div>
                    <button type="button" class="btn btn-outline-secondary" id="add-specification">
                        <em class="ni ni-plus"></em>
                    </button>
                </div>
            </div>
            
            <div id="specifications-container">
                <div class="table-responsive">
                    <table class="table table-hover table-striped nowrap datatable-init">
                        <thead>
                            <tr>
                                <th class="no-sort" style="width: 5%;"></th>
                                <th class="no-sort" style="width: 20%;">Grup</th>
                                <th class="no-sort" style="width: 30%;">Nama Spesifikasi</th>
                                <th class="no-sort" style="width: 30%;">Value</th>
                                <th class="no-sort" style="width: 10%;">Urutan</th>
                                <th class="no-sort" style="width: 5%;"></th>
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
            </div>
            
            <hr>
            <h5>3. Varian dan Inventaris</h5>

            @if ($product->variants->isEmpty())
                <div class="alert alert-warning">
                    <strong>Produk belum mempunyai varian.</strong>
                    Anda tetap dapat menyimpan informasi produk dan spesifikasinya.
                    Saat penyimpanan, controller akan membuat satu varian default
                    menggunakan SKU internal dan menyelaraskan inventaris dari
                    nilai stok lama produk.
                </div>
            @else
                <p class="text-soft">
                    Harga dan stok berikut milik masing-masing varian.
                    Stok tidak dapat dikurangi di bawah jumlah yang direservasi.
                </p>

                @foreach ($product->variants as $variant)
                    @php
                        $key = $variant->id;
                        $oldVariant = old("variants.$key", []);
                        $inventory = $variant->inventory;
                        $selectedValues = $oldVariant['attribute_value_ids']
                            ?? $variant->attributeValues->pluck('id')->all();

                        $reserved = (int) ($inventory?->reserved_quantity ?? 0);
                        $quantity = (int) ($inventory?->quantity ?? 0);
                    @endphp

                    <div class="border rounded p-3 mb-4">
                        <input type="hidden"
                               name="variants[{{ $key }}][id]"
                               value="{{ $variant->id }}">

                        <h6>
                            Varian #{{ $variant->id }}
                            @if ($variant->is_default)
                                <span class="badge badge-primary">Default</span>
                            @endif
                        </h6>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">Nama varian</label>
                                    <input class="form-control"
                                           name="variants[{{ $key }}][name]"
                                           value="{{ old("variants.$key.name", $variant->name) }}">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">SKU *</label>
                                    <input class="form-control"
                                           name="variants[{{ $key }}][sku]"
                                           value="{{ old("variants.$key.sku", $variant->sku) }}"
                                           maxlength="100" required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">Barcode</label>
                                    <input class="form-control"
                                           name="variants[{{ $key }}][barcode]"
                                           value="{{ old("variants.$key.barcode", $variant->barcode) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">Harga jual *</label>
                                    <input class="form-control" type="number"
                                           min="0" step="0.01"
                                           name="variants[{{ $key }}][price]"
                                           value="{{ old("variants.$key.price", $variant->price) }}"
                                           required>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <labe class="control-label"l>Harga sebelum diskon</label>
                                    <input class="form-control" type="number"
                                           min="0" step="0.01"
                                           name="variants[{{ $key }}][compare_at_price]"
                                           value="{{ old("variants.$key.compare_at_price", $variant->compare_at_price) }}">
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">Harga modal</label>
                                    <input class="form-control" type="number"
                                           min="0" step="0.01"
                                           name="variants[{{ $key }}][cost_price]"
                                           value="{{ old("variants.$key.cost_price", $variant->cost_price) }}">
                                </div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Berat (kg)</label>
                                    <input class="form-control" type="number"
                                           min="0" step="0.001"
                                           name="variants[{{ $key }}][weight]"
                                           value="{{ old("variants.$key.weight", $variant->weight) }}">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="control-label">Status *</label>
                                    <select class="form-control"
                                            name="variants[{{ $key }}][status]" required>
                                        <option value="active"
                                            @selected(old("variants.$key.status", $variant->status) === 'active')>
                                            Aktif
                                        </option>
                                        <option value="inactive"
                                            @selected(old("variants.$key.status", $variant->status) === 'inactive')>
                                            Nonaktif
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <h6>Atribut varian</h6>

                        @foreach ($attributes as $attribute)
                            @php
                                $attributeValue = collect($selectedValues)
                                    ->first(function ($valueId) use ($attribute) {
                                        return $attribute->values->contains('id', $valueId);
                                    });
                            @endphp

                            <div class="form-group">
                                <label for="attribute-{{ $key }}-{{ $attribute->id }}" class="control-label">
                                    {{ $attribute->name }}
                                </label>
                                <select class="form-control"
                                        id="attribute-{{ $key }}-{{ $attribute->id }}"
                                        name="variants[{{ $key }}][attribute_value_ids][]">
                                    <option value="">Tidak dipilih</option>
                                    @foreach ($attribute->values as $value)
                                        <option value="{{ $value->id }}"
                                            @selected((string) $attributeValue === (string) $value->id)>
                                            {{ $value->value }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach

                        <h6>Inventaris</h6>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">Stok *</label>
                                    <input class="form-control" type="number"
                                           min="{{ $reserved }}" step="1"
                                           name="variants[{{ $key }}][quantity]"
                                           value="{{ old("variants.$key.quantity", $quantity) }}"
                                           required>
                                    <small class="text-soft">
                                        Stok tidak boleh di bawah {{ $reserved }} unit yang direservasi.
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">Stok direservasi</label>
                                    <input class="form-control" type="number"
                                           value="{{ $reserved }}" readonly>
                                    <small class="text-soft">
                                        Nilai ini tidak diubah melalui form produk.
                                    </small>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="control-label">Batas stok minimum *</label>
                                    <input class="form-control" type="number" min="0"
                                           name="variants[{{ $key }}][reorder_level]"
                                           value="{{ old("variants.$key.reorder_level", $inventory?->reorder_level ?? 0) }}"
                                           required>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

            <hr>
            <h5>4. Gambar Produk</h5>
            @php
                $productImages = $product->images;
                $primaryImage = $productImages->firstWhere('is_primary', true)
                    ?? $productImages->first();
            @endphp

            @if ($primaryImage)
                <div class="mb-3">
                    <p class="text-soft">Gambar utama saat ini</p>
                    @if (
                        is_string($primaryImage->path)
                        && Storage::disk('public')->exists($primaryImage->path)
                    )
                        <img src="{{ Storage::disk('public')->url($primaryImage->path) }}"
                             alt="{{ $primaryImage->alt_text ?: $product->name }}"
                             class="img-fluid mb-2"
                             style="max-width:240px; max-height:240px; object-fit:contain">
                    @else
                        <div class="alert alert-warning">
                            File gambar utama tidak ditemukan pada penyimpanan lokal.
                        </div>
                    @endif
                </div>
            @elseif (
                is_string($product->image)
                && trim($product->image) !== ''
                && ! filter_var($product->image, FILTER_VALIDATE_URL)
            )
                @php
                    $legacyPath = ltrim($product->image, '/');
                    if (str_starts_with($legacyPath, 'storage/')) {
                        $legacyPath = substr($legacyPath, 8);
                    }
                @endphp

                @if (Storage::disk('public')->exists($legacyPath))
                    <div class="mb-3">
                        <p class="text-soft">Gambar dari kolom lama</p>
                        <img src="{{ Storage::disk('public')->url($legacyPath) }}"
                             alt="{{ $product->name }}"
                             class="img-fluid"
                             style="max-width:240px; max-height:240px; object-fit:contain">
                    </div>
                @endif
            @endif

            @if ($productImages->isNotEmpty())
                <p class="text-soft">
                    Pilih gambar utama, atau tandai gambar yang ingin dihapus.
                    Gambar yang tidak ditandai akan dipertahankan.
                </p>

                @foreach ($productImages as $image)
                    <div class="border rounded p-3 mb-3">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                @if (
                                    is_string($image->path)
                                    && Storage::disk('public')->exists($image->path)
                                )
                                    <img src="{{ Storage::disk('public')->url($image->path) }}"
                                         alt="{{ $image->alt_text ?: $product->name }}"
                                         class="img-fluid"
                                         style="max-height:140px; object-fit:contain">
                                @else
                                    <p class="text-soft">File tidak ditemukan</p>
                                @endif
                            </div>

                            <div class="col-md-5">
                                <p class="mb-1">{{ $image->alt_text ?: $image->path }}</p>
                                <small class="text-soft">{{ $image->path }}</small>
                            </div>

                            <div class="col-md-4">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio"
                                           name="primary_image_id"
                                           id="primary-{{ $image->id }}"
                                           value="{{ $image->id }}"
                                           @checked((string) old('primary_image_id', $primaryImage?->id) === (string) $image->id)>
                                    <label class="form-check-label"
                                           for="primary-{{ $image->id }}">
                                        Jadikan gambar utama
                                    </label>
                                </div>

                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox"
                                           name="delete_images[]"
                                           id="delete-{{ $image->id }}"
                                           value="{{ $image->id }}"
                                           @checked(in_array(
                                               $image->id,
                                               old('delete_images', [])
                                           ))>
                                    <label class="form-check-label"
                                           for="delete-{{ $image->id }}">
                                        Hapus gambar ini
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif

            <div class="form-group">
                <label for="images" class="control-label">Tambahkan gambar baru</label>
                <input class="form-control" type="file" id="images"
                       name="images[]" multiple
                       accept=".jpg,.jpeg,.png,.webp">
                <small class="text-soft">
                    Maksimal 10 gambar baru, JPG/JPEG/PNG/WEBP, 5 MB per file.
                    Gambar lama tetap dipertahankan kecuali ditandai untuk dihapus.
                </small>
            </div>

            <div class="form-group">
                <label for="image_alt_text">Teks alternatif gambar baru</label>
                <input class="form-control" id="image_alt_text"
                       name="image_alt_text" value="{{ old('image_alt_text') }}">
            </div>

            <hr>
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
        const selectedCategory = category.value;

        Array.from(subCategory.options).forEach(function (option) {
            if (!option.value) return;

            option.hidden = option.dataset.category !== selectedCategory;

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