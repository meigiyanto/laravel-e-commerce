@extends('layouts.storefront')

@section('title', config('app.name') . ' - My Addresses')

@section('content')
<div class="store-breadcrumb">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('storefront.home') }}"><i class="bi bi-house me-1"></i> Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('account.index') }}">Account</a></li>
                <li class="breadcrumb-item active" aria-current="page">My Addresses</li>
            </ol>
        </nav>
    </div>
</div>

@include('components.account.page-header', [
    'title' => 'My Addresses',
    'description' => 'Manage your saved shipping addresses and choose your default destination.',
    'eyebrow' => 'My Account',
    'action' => '<a href="' . route('account.addresses.create') . '" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i> Add Address</a>',
])

<section class="pb-5">
    <div class="container">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="row g-4">
            <aside class="col-lg-4">
                @include('components.account.sidebar', ['user' => $user])
            </aside>

            <div class="col-lg-8">
                @forelse ($addresses as $address)
                    <article class="store-account-card mb-3">
                        <div class="d-flex flex-column flex-sm-row justify-content-between gap-3">
                            <div class="store-account-card-body">
                                <div class="d-flex align-items-center flex-wrap gap-2 mb-2">
                                    <h2 class="store-account-card-title mb-0">{{ $address->label }}</h2>
                                    @if ($address->is_default)
                                        <span class="badge bg-success">Default Address</span>
                                    @endif
                                </div>

                                <p class="store-account-card-text mb-1"><strong>{{ $address->recipient_name }}</strong></p>
                                <p class="store-account-card-text mb-1">{{ $address->phone }}</p>
                                <p class="store-account-card-text mb-1">{{ $address->address_line }}</p>
                                <p class="store-account-card-text mb-1">
                                    {{ $address->village }}, {{ $address->district }},
                                    {{ $address->city }}, {{ $address->province }}
                                    {{ $address->postal_code }}
                                </p>
                                <p class="store-account-card-text mb-0">{{ $address->country }}</p>

                                @if ($address->notes)
                                    <p class="small text-muted mt-2 mb-0">
                                        <strong>Delivery notes:</strong> {{ $address->notes }}
                                    </p>
                                @endif
                            </div>

                            <div class="d-flex flex-sm-column gap-2 align-items-start">
                                <a href="{{ route('account.addresses.edit', $address) }}" class="btn btn-outline-primary btn-sm">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </a>

                                @unless ($address->is_default)
                                    <form method="POST" action="{{ route('account.addresses.default', $address) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn btn-outline-success btn-sm">Set as Default</button>
                                    </form>
                                @endunless

                                <form method="POST" action="{{ route('account.addresses.destroy', $address) }}" onsubmit="return confirm('Delete this address?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="bi bi-trash me-1"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="store-account-card text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-geo-alt display-4 text-muted"></i>
                        </div>
                        <h2 class="store-account-card-title">No saved addresses yet</h2>
                        <p class="store-account-card-text mb-4">
                            Add a shipping address to make your checkout process faster.
                        </p>
                        <a href="{{ route('account.addresses.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-lg me-1"></i> Add Your First Address
                        </a>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</section>
@endsection
