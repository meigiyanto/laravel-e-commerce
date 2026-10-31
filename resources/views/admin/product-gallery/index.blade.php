@extends('layouts.admin')

@section('title', 'Product Gallery')
@section('header', 'Product Gallery')

@section('content')
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">
                    Product Gallery
                </h3>
                <p class="text-soft">Upload images before assigning them to products.</p>
            </div>

            <div class="nk-block-head-content">
                <a href="{{ route('admin.products.index') }}" class="btn btn-outline-primary">
                    <em class="icon ni ni-list"></em>
                    <span>Product List</span>
                </a>
            </div>

        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif
    
    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif


    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card card-bordered">
        <div class="card-inner">
            <h5>Upload Images</h5>

            <form method="POST" action="{{ route('admin.product-gallery.upload') }}" enctype="multipart/form-data">
                @csrf

                <div class="form-group">
                    <label for="images" class="form-label">Choose images</label>
                    <input type="file" name="images[]" id="images" class="form-control" accept=".jpg,.jpeg,.png,.webp" multiple required>
                    <small class="text-soft">JPG, PNG, or WebP; maximum 5 MB per image and 20 images per upload.</small>
                </div>

                <button type="submit" class="btn btn-primary">
                    <em class="icon ni ni-upload"></em>
                    <span>Upload Images</span>
                </button>
            </form>
        </div>
    </div>

    <div class="card card-bordered mt-4">
        <div class="card-inner">
            <form method="GET" action="{{ route('admin.product-gallery.index') }}">
                <div class="row align-items-end">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="search">Search filename</label>
                            <input type="search" name="search" id="search" class="form-control" value="{{ $search }}" placeholder="Enter filename">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="status">Image status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="all" @selected($status === 'all')>All images</option>
                                <option value="unused" @selected($status === 'unused')>Not used</option>
                                <option value="used" @selected($status === 'used')>
                                    Used by products
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="{{ route('admin.product-gallery.index') }}" class="btn btn-outline-secondary">Reset</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="nk-block mt-4">
        <p class="text-soft">{{ $gallery->total() }} image files found</p>
        @if ($gallery->isEmpty())
            <div class="card card-bordered">
                <div class="card-inner text-center">
                    <h5>No images found</h5>
                    <p class="text-soft">Upload an image or change the selected filter.</p>
                </div>
            </div>
        @else
            <div class="row g-gs">
                @foreach ($gallery as $image)
                    <div class="col-6 col-md-4 col-xl-3">
                        <div class="card card-bordered h-100">
                            
                            <div class="card-inner">
                                <div class="text-center mb-3">
                                    <img src="{{ $image['url'] }}" alt="{{ $image['filename'] }}" class="img-fluid" loading="lazy" style="width: 100%; height: 180px; object-fit: contain;">
                                </div>

                                @if (count($image['product_names']))
                                    @foreach ($image['product_names'] as $productName)
                                        <h6 class="title mb-1">{{ $productName }}</h6>
                                    @endforeach
                                @else
                                    <p class="title mb-1">Belum terhubung ke produk</p>
                                @endif

                                 @if ($image['is_used'])
                                    <span class="badge bg-success">Used by a product or variant</span>
                                 @else
                                     <span class="badge bg-light text-dark">Not used</span>
                                 @endif
 
                                <div class="border-top mt-3 pt-3">
                                    <form method="POST" action="{{ route('admin.product-gallery.replace', ['filename' => $image['filename']]) }}" enctype="multipart/form-data" class="mb-2">
                                        @csrf
                                        @method('PUT')
                                        <label class="form-label small" for="replace-{{ md5($image['path']) }}">Replace image</label>
                                        <input type="file" name="image" id="replace-{{ md5($image['path']) }}" class="form-control form-control-sm mb-2" accept=".jpg,.jpeg,.png,.webp" required>
                                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">Replace</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.product-gallery.destroy', ['filename' => $image['filename']]) }}" onsubmit="return confirm('Delete this image permanently?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100" @disabled($image['is_used'])>Delete</button>
                                        @if ($image['is_used'])
                                            <small class="text-soft d-block mt-1">Cannot delete while this image is in use.</small>
                                        @endif
                                    </form>
                                </div>

                                
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $gallery->links() }}
            </div>
        @endif
    </div>
@endsection
