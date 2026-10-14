@extends('layouts.admin')

@section('title', 'Add Product')
@section('header', 'Add Product')

@section('content')
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">Add Product</h3>
            <p class="text-soft">
                Complete product information, variants, images and stock
            </p>
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
        <strong>Please double check the following input:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="card card-bordered">
    <div class="card-inner">
        <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
            @csrf
            <h5>1. Product Information</h5>

            <div class="form-group">
                <label for="name">Product Name *</label>
                <input class="form-control" id="name" name="name" value="{{ old('name') }}" required>
            </div>

            <div class="form-group">
                <label for="slug">Slug</label>
                <input class="form-control" id="slug" name="slug" value="{{ old('slug') }}" placeholder="Automatically generated if empty">
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="category_id">Category *</label>
                        <select class="form-control" id="category_id" name="category_id" required>
                            <option value="">Select category</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}"
                                    @selected(old('category_id') == $category->id)>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sub_category_id">Subcategory *</label>
                        <select class="form-control" id="sub_category_id" name="sub_category_id" required>
                            <option value="">Select subcategory</option>
                            @foreach ($categories as $category)
                                @foreach ($category->subCategories as $subCategory)
                                    <option value="{{ $subCategory->id }}" data-category="{{ $category->id }}" @selected(old('sub_category_id') == $subCategory->id)>
                                        {{ $category->name }} — {{ $subCategory->name }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>


            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="5">{{ old('description') }}</textarea>
            </div>

            <hr>
            <h5>2. Variant</h5>
            <p class="text-soft">
                This form creates one initial variant as the default variant.</p>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="variant_name">Variant Name</label>
                        <input class="form-control" id="variant_name" name="variant_name" value="{{ old('variant_name') }}" placeholder="Example: Black / Medium">
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label for="sku">SKU *</label>
                        <input class="form-control" id="sku" name="sku" value="{{ old('sku') }}" required>
                    </div>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="barcode">Barcode</label>
                        <input class="form-control" id="barcode" name="barcode" value="{{ old('barcode') }}">
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="weight">Weight (kg)</label>
                        <input class="form-control" type="number" step="0.001" min="0" id="weight" name="weight" value="{{ old('weight') }}">
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="status">Variant Status *</label>
                        <select class="form-control" id="status" name="status" required>
                            <option value="active" @selected(old('status', 'active') === 'active')>
                                Active
                            </option>
                            <option value="inactive" @selected(old('status') === 'inactive')>
                                Non-active
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <hr>
            <h5>3. Variant Attribute</h5>

            @forelse ($attributes as $attribute)
                <div class="form-group">
                    <label for="attribute_{{ $attribute->id }}">{{ $attribute->name }}</label>
                    <select class="form-control" id="attribute_{{ $attribute->id }}" name="attribute_value_ids[]">
                        <option value="">Not selected</option>
                        @foreach ($attribute->values as $value)
                            <option value="{{ $value->id }}" @selected(in_array($value->id, old('attribute_value_ids',[])))>
                                {{ $value->value }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @empty
                <p class="text-soft">There are no attributes yet. Add attributes and their values first.</p>
            @endforelse

            <hr>
            <h5>4. Price</h5>
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="price">Selling Price *</label>
                        <input class="form-control" type="number" step="0.01" min="0" id="price" name="price" value="{{ old('price', 0) }}" required>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label for="compare_at_price">Price Before Discount</label>
                        <input class="form-control" type="number" step="0.01" min="0" id="compare_at_price" name="compare_at_price" value="{{ old('compare_at_price') }}">
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="cost_price">Capital Price</label>
                        <input class="form-control" type="number" step="0.01" min="0" id="cost_price" name="cost_price" value="{{ old('cost_price') }}">
                    </div>
                </div>
            </div>

            <hr>
            <h5>5. Inventory</h5>
            <div class="row mb-3">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="quantity">Initial Stock *</label>
                        <input class="form-control" type="number" min="0" id="quantity" name="quantity" value="{{ old('quantity', 0) }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="reserved_quantity">Reserved Stock *</label>
                        <input class="form-control" type="number" min="0"
                               id="reserved_quantity" name="reserved_quantity"
                               value="{{ old('reserved_quantity', 0) }}" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="reorder_level">Minimum Stock Limit *</label>
                        <input class="form-control" type="number" min="0" id="reorder_level" name="reorder_level" value="{{ old('reorder_level', 0) }}" required>
                    </div>
                </div>
            </div>

            <hr>
            <h5>6. Product Image</h5>
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="images">Upload Image (maximal 10)</label>
                        <input type="text" id="image" name="image" class="form-control" value="{{ old('image') }}">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="image_alt_text">Alt Text Image</label>
                        <input class="form-control" id="image_alt_text" name="image_alt_text" value="{{ old('image_alt_text') }}">
                    </div>
                </div>
                
                <div class="form-group mt-2">
                    <label>Choose Existing Gallery Images</label>
                    @if (count($availableGalleryImages))
                    <div class="gallery-images-container" style="height: 320px; overflow-y: auto; overflow-x: hidden; border: 2px solid lightgrey; border-radius: 4px; padding: 8px; margin: 4px;">
                        <div class="row">
                            @foreach ($availableGalleryImages as $image)
                                <div class="col-md-6 col-lg-2">
                                    <div class="custom-control custom-checkbox image-control">
                                        <input type="checkbox" class="custom-control-input" id="gallery-image-{{ $loop->index }}" name="existing_image_paths[]" value="{{ $image['path'] }}" @checked(in_array($image['path'], old('existing_image_paths', [])))>
                                        <label class="custom-control-label" for="gallery-image-{{ $loop->index }}">
                                            <img src="{{ $image['url'] }}" alt="" class="img-fluid img-responsive mb-2" loading="lazy">
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @else
                        <p class="text-soft">
                            No unused gallery images available.
                        </p>
                    @endif
                </div>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.products.index') }}"
                   class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary">
                    Save Product
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
