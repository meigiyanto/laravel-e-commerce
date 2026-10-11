@extends('layouts.admin')

@section('title', 'Edit Product')
@section('header', 'Edit Product')

@section('content')
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">Edit Product</h3>
            <p class="text-soft">
                Lengkapi informasi, varian, gambar, dan stok produk.
            </p>
        </div>
        <div class="nk-block-head-content">
            <a href="{{ route('admin.products.index') }}"
               class="btn btn-outline-primary">
                Kembali
            </a>
        </div>
    </div>
</div>

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
                <input class="form-control" id="name" name="name"
 value="{{ old('name', $product->name) }}" required>
            </div>

            <div class="form-group">
                <label for="slug">Slug</label>
                <input class="form-control" id="slug" name="slug" value="{{ old('slug', $product->slug) }}" placeholder="Dibuat otomatis jika kosong">
            </div>

            <div class="form-group">
                <label for="category_id">Kategori *</label>
                <select class="form-control" id="category_id" name="category_id" required>
                    <option value="">Category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}"
                            @selected(old('category_id') == $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="sub_category_id">Subkategori *</label>
                <select class="form-control" id="sub_category_id" name="sub_category_id" required>
                    <option value="">Subcategories</option>
                    @foreach ($categories as $category)
                        @foreach ($category->subCategories as $subCategory)
                            <option value="{{ $subCategory->id }}" data-category="{{ $category->id }}" @selected(old('sub_category_id', $product->sub_category_id) == $subCategory->id)>{{ $category->name }} — {{ $subCategory->name }}</option>
                        @endforeach
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description"
 name="description" rows="5">{{ old('description', $product->description) }}</textarea>
            </div>

            <hr>
            <h5>2. Varian dan Harga</h5>
            <p class="text-soft">
                Form ini membuat satu varian awal sebagai varian default.
            </p>

            <div class="form-group">
                <label for="variant_name">Variant Name</label>
                <input class="form-control" id="variant_name" name="variant_name" value="{{ old('variant_name', $product->variant_name) }}" placeholder="e.g.: Blacj / Medium">
            </div>

            <div class="form-group">
                <label for="sku">SKU *</label>
                <input class="form-control" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" required>
            </div>

            <div class="form-group">
                <label for="barcode">Barcode</label>
                <input class="form-control" id="barcode" name="barcode" value="{{ old('barcode') }}">
            </div>

            <div class="form-group">
                <label for="price">Price Sell *</label>
                <input class="form-control" type="number" step="0.01" min="0" id="price" name="price" value="{{ old('price', 0) }}" required>p
            </div>

            <div class="form-group">
                <label for="compare_at_price">Price before discount</label>
                <input class="form-control" type="number" step="0.01"
                       min="0" id="compare_at_price"
                       name="compare_at_price"
                       value="{{ old('compare_at_price') }}">
            </div>

            <div class="form-group">
                <label for="cost_price">Harga Modal</label>
                <input class="form-control" type="number" step="0.01"
                       min="0" id="cost_price" name="cost_price"
                       value="{{ old('cost_price') }}">
            </div>

            <div class="form-group">
                <label for="weight">Weight (kg)</label>
                <input class="form-control" type="number" step="0.001"
                       min="0" id="weight" name="weight"
                       value="{{ old('weight', $product->weight) }}">
            </div>

            <div class="form-group">
                <label for="status">Variant Status *</label>
                <select class="form-control" id="status" name="status" required>
                    <option value="active"
                        @selected(old('status', 'active') === 'active')>
                        Aktif
                    </option>
                    <option value="inactive"
                        @selected(old('status') === 'inactive')>
                        Nonaktif
                    </option>
                </select>
            </div>

            <h5>4. Product Specification</h5>
            <div class="form-group">
                <label for="product_specification">Product Specification</label>
                @forelse ($product->specifications as $specification)
                    <div class="specification-row">
                        <div>
                            <strong>
                                {{ $specification->specification_name }}
                            </strong>

                            <div>
                                {{ $specification->specification_value }}
                            </div>
                        </div>

                        <small>
                            {{ $specification->specification_group }}
                        </small>
                    </div>
                @empty
                    <p>No product specification yet.</p>
                @endforelse

                <p class="help-text">
                    Bagian ini hanya menampilkan spesifikasi yang tersimpan.
                    Formulir untuk menambah, mengubah, dan menghapus spesifikasi
                    perlu dibuat terpisah.
                </p>
        </section>
            <hr>
            <h5>4. Variant Attribute</h5>

            @forelse ($attributes as $attribute)
                <div class="form-group">
                    <label for="attribute_{{ $attribute->id }}">{{ $attribute->name }}</label>

                    <select class="form-control"
                            id="attribute_{{ $attribute->id }}"
                            name="attribute_value_ids[]">
                        <option value="">Tidak dipilih</option>
                        @foreach ($attribute->values as $value)
                            <option value="{{ $value->id }}"
                                @selected(in_array($value->id, old('attribute_value_ids', [])))>
                                {{ $value->value }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @empty
                <p class="text-soft">Belum ada atribut. Tambahkan atribut dan nilainya terlebih dahulu.</p>
            @endforelse

            <hr>
            <h5>5. Gambar Produk</h5>

            <div class="form-group">
                <label for="images">Unggah Gambar (maksimal 10)</label>
                <input class="form-control" type="file" id="images"
                       name="images[]" accept=".jpg,.jpeg,.png,.webp"
                       multiple>
                <small class="text-soft">
                    Format JPG, PNG, atau WebP; maksimal 5 MB per gambar.
                    Gambar pertama menjadi gambar utama.
                </small>
            </div>

            <div class="form-group">
                <label for="image_alt_text">Teks Alternatif Gambar</label>
                <input class="form-control" id="image_alt_text"
                       name="image_alt_text"
                       value="{{ old('image_alt_text') }}">
            </div>

            <hr>
            <h5>6. Inventaris</h5>

            <div class="form-group">
                <label for="quantity">Stok Awal *</label>
                <input class="form-control" type="number" min="0"
                       id="quantity" name="quantity"
                       value="{{ old('quantity', 0) }}" required>
            </div>

            <div class="form-group">
                <label for="reserved_quantity">Stok Reservasi *</label>
                <input class="form-control" type="number" min="0"
                       id="reserved_quantity" name="reserved_quantity"
                       value="{{ old('reserved_quantity', 0) }}" required>
            </div>

            <div class="form-group">
                <label for="reorder_level">Batas Minimum Stok *</label>
                <input class="form-control" type="number" min="0"
                       id="reorder_level" name="reorder_level"
                       value="{{ old('reorder_level', 0) }}" required>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.products.index') }}"
                   class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Update product
                </button>
            </div>
        </form>
    </div>
</div>

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
});
</script>
@endsection
