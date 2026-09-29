@extends('layouts.app')

@section('title', 'Edit Product')

@section('header', 'Edit Product')

@section('content')

<div class="page-container">

    <div class="page-title">
        <h1>Edit Product</h1>
        <p>Perbarui informasi produk.</p>
    </div>

    <div class="content-card form-card">

        <form
            method="POST"
            action="{{ route('admin.products.update', $product) }}"
        >

            @csrf
            @method('PUT')

            <div class="form-group">

                <label for="category_id">
                    Category
                </label>

                <select
                    id="category_id"
                    name="category_id"
                    required
                >

                    <option value="">
                        -- Select Category --
                    </option>

                    @foreach($categories as $category)

                        <option
                            value="{{ $category->id }}"
                            @selected(
                                old(
                                    'category_id',
                                    $product->subCategory->category_id
                                ) == $category->id
                            )
                        >
                            {{ $category->name }}
                        </option>

                    @endforeach

                </select>

                @error('category_id')
                    <span class="error-text">
                        {{ $message }}
                    </span>
                @enderror

            </div>


            <div class="form-group">

                <label for="sub_category_id">
                    Sub-Category
                </label>

                <select
                    id="sub_category_id"
                    name="sub_category_id"
                    required
                >

                    <option value="">
                        -- Select Sub-Category --
                    </option>

                    @foreach($categories as $category)

                        @foreach($category->subCategories as $subCategory)

                            <option
                                value="{{ $subCategory->id }}"
                                data-category="{{ $category->id }}"
                                @selected(
                                    old(
                                        'sub_category_id',
                                        $product->sub_category_id
                                    ) == $subCategory->id
                                )
                            >
                                {{ $subCategory->name }}
                            </option>

                        @endforeach

                    @endforeach

                </select>

                @error('sub_category_id')
                    <span class="error-text">
                        {{ $message }}
                    </span>
                @enderror

            </div>


            <div class="form-group">

                <label for="name">
                    Product Name
                </label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $product->name) }}"
                    required
                >

                @error('name')
                    <span class="error-text">
                        {{ $message }}
                    </span>
                @enderror

            </div>


            <div class="form-group">

                <label for="slug">
                    Slug
                </label>

                <input
                    type="text"
                    id="slug"
                    name="slug"
                    value="{{ old('slug', $product->slug) }}"
                >

                @error('slug')
                    <span class="error-text">
                        {{ $message }}
                    </span>
                @enderror

            </div>


            <div class="form-group">

                <label for="description">
                    Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="5"
                >{{ old('description', $product->description) }}</textarea>

                @error('description')
                    <span class="error-text">
                        {{ $message }}
                    </span>
                @enderror

            </div>


            <div class="form-group">

                <label for="price">
                    Price
                </label>

                <input
                    type="number"
                    id="price"
                    name="price"
                    value="{{ old('price', $product->price) }}"
                    min="0"
                    step="0.01"
                    required
                >

                @error('price')
                    <span class="error-text">
                        {{ $message }}
                    </span>
                @enderror

            </div>


            <div class="form-group">

                <label for="stock">
                    Stock
                </label>

                <input
                    type="number"
                    id="stock"
                    name="stock"
                    value="{{ old('stock', $product->stock) }}"
                    min="0"
                    required
                >

                @error('stock')
                    <span class="error-text">
                        {{ $message }}
                    </span>
                @enderror

            </div>


            <div class="form-group">

                <label for="image">
                    Image Path
                </label>

                <input
                    type="text"
                    id="image"
                    name="image"
                    value="{{ old('image', $product->image) }}"
                >

                @error('image')
                    <span class="error-text">
                        {{ $message }}
                    </span>
                @enderror

            </div>


            <div class="form-actions">

                <a
                    href="{{ route('admin.products.index') }}"
                    class="btn btn-secondary"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    Update Product
                </button>

            </div>

        </form>

    </div>

</div>


<script>
document.addEventListener('DOMContentLoaded', function () {

    const categorySelect =
        document.getElementById('category_id');

    const subCategorySelect =
        document.getElementById('sub_category_id');

    function filterSubCategories() {

        const categoryId = categorySelect.value;

        Array.from(subCategorySelect.options).forEach(function (option) {

            if (!option.dataset.category) {
                option.hidden = false;
                return;
            }

            option.hidden =
                option.dataset.category !== categoryId;

        });

        const selectedOption =
            subCategorySelect.options[
                subCategorySelect.selectedIndex
            ];

        if (
            selectedOption &&
            selectedOption.dataset.category &&
            selectedOption.dataset.category !== categoryId
        ) {
            subCategorySelect.value = '';
        }
    }

    categorySelect.addEventListener(
        'change',
        filterSubCategories
    );

    filterSubCategories();

});
</script>

@endsection
