@extends('layouts.admin')

@section('title', 'Tambah Produk')
@section('header', 'Tambah Produk')

@section('content')
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">Tambah Produk</h3>
            <p class="text-soft">Informasi produk, varian, spesifikasi, harga, dan inventaris.</p>
        </div>
        <div class="nk-block-head-content">
            <a href="{{ route('admin.products.index') }}" class="btn btn-white btn-outline-light">
                <em class="icon ni ni-arrow-left"></em> Kembali
            </a>
        </div>
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <strong>Periksa kembali input berikut:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card card-bordered">
    <div class="card-inner">
        <form method="POST"
              action="{{ route('admin.products.store') }}"
              enctype="multipart/form-data">
            @csrf

            <h5>1. Informasi Produk</h5>

            <div class="form-group">
                <label for="name">Nama produk *</label>
                <input class="form-control" id="name" name="name"
                       value="{{ old('name') }}" maxlength="255" required>
            </div>

            <div class="form-group">
                <label for="slug">Slug</label>
                <input class="form-control" id="slug" name="slug"
                       value="{{ old('slug') }}"
                       placeholder="Kosongkan untuk dibuat otomatis">
            </div>

            <div class="row">
                <div class="col-md-6 mb-2">
                    <div class="form-group">
                        <label for="category_id">Kategori *</label>
                        <select class="form-control" id="category_id"
                                name="category_id" required>
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    @selected(old('category_id') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-md-6 mb-2">
                    <div class="form-group">
                        <label for="sub_category_id">Subkategori *</label>
                        <select class="form-control" id="sub_category_id"
                                name="sub_category_id" required>
                            <option value="">Pilih subkategori</option>
                            @foreach ($categories as $category)
                                @foreach ($category->subCategories as $subCategory)
                                    <option value="{{ $subCategory->id }}"
                                            data-category="{{ $category->id }}"
                                        @selected(old('sub_category_id') == $subCategory->id)>
                                        {{ $category->name }} — {{ $subCategory->name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="description">Deskripsi</label>
                <textarea class="form-control" id="description"
                          name="description" rows="5">{{ old('description') }}</textarea>
            </div>

            <hr>
            <h5>2. Varian Default</h5>
            <p class="text-soft">
                Produk tanpa pilihan varian di toko tetap menggunakan satu varian
                internal untuk SKU, harga, dan inventaris.
            </p>

            <div class="row">
                <div class="col-md-6 mb-2">
                    <div class="form-group">
                        <label for="variant_name">Nama varian</label>
                        <input class="form-control" id="variant_name"
                               name="variant_name" value="{{ old('variant_name') }}"
                               placeholder="Contoh: Default">
                    </div>
                </div>
                <div class="col-md-6 mb-2">
                    <div class="form-group">
                        <label for="sku">SKU *</label>
                        <input class="form-control" id="sku" name="sku"
                               value="{{ old('sku') }}" maxlength="100" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4 mb-2">
                    <div class="form-group">
                        <label for="barcode">Barcode</label>
                        <input class="form-control" id="barcode" name="barcode"
                               value="{{ old('barcode') }}">
                    </div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="form-group">
                        <label for="weight">Berat (kg)</label>
                        <input class="form-control" type="number" id="weight"
                               name="weight" min="0" step="0.001"
                               value="{{ old('weight') }}">
                    </div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="active" @selected(old('status', 'active') === 'active')>
                                Aktif
                            </option>
                            <option value="inactive" @selected(old('status') === 'inactive')>
                                Nonaktif
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <h6>Atribut Varian</h6>
            @forelse ($attributes as $attribute)
                <div class="form-group">
                    <label for="attribute-{{ $attribute->id }}">
                        {{ $attribute->name }}
                    </label>
                    <select class="form-control variant-attribute"
                            id="attribute-{{ $attribute->id }}"
                            data-attribute-id="{{ $attribute->id }}"
                            name="attribute_value_ids[]">
                        <option value="">Tidak dipilih</option>
                        @foreach ($attribute->values as $value)
                            <option value="{{ $value->id }}"
                                @selected(in_array(
                                    $value->id,
                                    old('attribute_value_ids', [])
                                ))>
                                {{ $value->value }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @empty
                <p class="text-soft">Belum ada atribut yang tersedia.</p>
            @endforelse

            <hr>
            <h5>3. Harga</h5>

            <div class="row">
                <div class="col-md-4 mb-2">
                    <div class="form-group">
                        <label for="price">Harga jual *</label>
                        <input class="form-control" type="number" id="price"
                               name="price" min="0" step="0.01"
                               value="{{ old('price', '0.00') }}" required>
                    </div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="form-group">
                        <label for="compare_at_price">Harga sebelum diskon</label>
                        <input class="form-control" type="number"
                               id="compare_at_price" name="compare_at_price"
                               min="0" step="0.01"
                               value="{{ old('compare_at_price') }}">
                    </div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="form-group">
                        <label for="cost_price">Harga modal</label>
                        <input class="form-control" type="number" id="cost_price"
                               name="cost_price" min="0" step="0.01"
                               value="{{ old('cost_price') }}">
                    </div>
                </div>
            </div>

            <hr>
            <h5>4. Inventaris</h5>

            <div class="row">
                <div class="col-md-4 mb-2">
                    <div class="form-group">
                        <label for="quantity">Stok awal *</label>
                        <input class="form-control" type="number" id="quantity"
                               name="quantity" min="0" step="1"
                               value="{{ old('quantity', 0) }}" required>
                    </div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="form-group">
                        <label for="reserved_quantity">Stok direservasi *</label>
                        <input class="form-control" type="number"
                               id="reserved_quantity" name="reserved_quantity"
                               min="0" step="1"
                               value="{{ old('reserved_quantity', 0) }}" required>
                    </div>
                </div>
                <div class="col-md-4 mb-2">
                    <div class="form-group">
                        <label for="reorder_level">Batas stok minimum *</label>
                        <input class="form-control" type="number"
                               id="reorder_level" name="reorder_level"
                               min="0" step="1"
                               value="{{ old('reorder_level', 0) }}" required>
                    </div>
                </div>
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
                <!-- <div class="table-responsive"> -->
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
                        @foreach (old('specifications', []) as $index => $spec)
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
                                        class="form-control form-control-sm"
                                        name="specifications[{{ $index }}][specification_group]"
                                        value="{{ $spec['specification_group'] ?? '' }}"
                                        maxlength="255"
                                        required
                                    >
                                </td>
                        
                                <td>
                                    <input
                                        class="form-control form-control-sm"
                                        name="specifications[{{ $index }}][specification_name]"
                                        value="{{ $spec['specification_name'] ?? '' }}"
                                        maxlength="255"
                                        required
                                    >
                                </td>
                        
                                <td>
                                    <input
                                        class="form-control form-control-sm"
                                        name="specifications[{{ $index }}][specification_value]"
                                        value="{{ $spec['specification_value'] ?? '' }}"
                                        maxlength="2000"
                                        required
                                    >
                                </td>
                        
                                <td>
                                    <input
                                        type="number"
                                        class="form-control form-control-sm"
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
                <!-- </div> -->
            </div>
            
            <hr>
            <h5>6. Gambar Produk</h5>
            <div class="form-group">
                <label for="images">Unggah gambar (maksimal 10 file)</label>
                <input class="form-control" type="file" id="images"
                       name="images[]" accept=".jpg,.jpeg,.png,.webp"
                       multiple>
                <small class="text-soft">
                    Format JPG, JPEG, PNG, atau WEBP. Maksimal 5 MB per gambar.
                    Gambar pertama menjadi gambar utama.
                </small>
            </div>

            <div class="form-group">
                <label for="image_alt_text">Teks alternatif gambar</label>
                <input class="form-control" id="image_alt_text"
                       name="image_alt_text" value="{{ old('image_alt_text') }}">
            </div>

            <hr>
            <div class="form-actions mt-4">
                <a href="{{ route('admin.products.index') }}"
                   class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">
                    Simpan Produk
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
                class="form-control form-control-sm"
                data-field="specification_group"
                maxlength="255"
                required
            >
        </td>

        <td>
            <input
                class="form-control form-control-sm"
                data-field="specification_name"
                maxlength="255"
                required
            >
        </td>

        <td>
            <input
                class="form-control form-control-sm"
                data-field="specification_value"
                maxlength="2000"
                required
            >
        </td>

        <td>
            <input
                type="number"
                class="form-control form-control-sm"
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
    /**
     * Kategori & Sub Kategori
     */
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
    
    /**
     * Produk Spesifikasi
     */
    const container = document.getElementById('specifications-container');
    const template = document.getElementById('specification-template');
    const tbody = container.querySelector('tbody');
    let nextIndex = {{ count(old('specifications', [])) }};
    
    document.getElementById('add-specification').addEventListener('click', function () {
        const fragment = template.content.cloneNode(true);
        const row = fragment.querySelector('.specification-item');

        row.querySelectorAll('[data-field]').forEach(function (input) {
            input.name = `specifications[${nextIndex}][${input.dataset.field}]`;
        });
        
        tbody.children[0].style.display = 'none';
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