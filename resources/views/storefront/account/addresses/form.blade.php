@extends('layouts.storefront')

@php
    $isEditing = $address->exists;
    $pageTitle = $isEditing ? 'Edit Address' : 'Add Address';
@endphp

@section('title', config('app.name') . ' - ' . $pageTitle)

@section('content')
<div class="store-breadcrumb">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('storefront.home') }}"><i class="bi bi-house me-1"></i> Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('account.index') }}">Account</a></li>
                <li class="breadcrumb-item"><a href="{{ route('account.addresses.index') }}">My Addresses</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $pageTitle }}</li>
            </ol>
        </nav>
    </div>
</div>

@include('components.account.page-header', [
    'title' => $pageTitle,
    'description' => $isEditing
        ? 'Update the recipient and shipping details for this address.'
        : 'Add a shipping destination to your address book.',
    'eyebrow' => 'My Account',
])

<section class="pb-5">
    <div class="container">
        <div class="row g-4">
            <aside class="col-lg-4">
                @include('components.account.sidebar', ['user' => $user])
            </aside>

            <div class="col-lg-8">
                <div class="store-profile-panel">
                    <form method="POST" action="{{ $isEditing ? route('account.addresses.update', $address) : route('account.addresses.store') }}">
                        @csrf
                        @if ($isEditing)
                            @method('PUT')
                        @endif

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="label" class="form-label">Address Label</label>
                                <input id="label" name="label" type="text" class="form-control @error('label') is-invalid @enderror" value="{{ old('label', $address->label) }}" placeholder="Home, Office, etc." maxlength="50" required>
                                @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="recipient_name" class="form-label">Recipient Name</label>
                                <input id="recipient_name" name="recipient_name" type="text" class="form-control @error('recipient_name') is-invalid @enderror" value="{{ old('recipient_name', $address->recipient_name) }}" autocomplete="name" maxlength="100" required>
                                @error('recipient_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input id="phone" name="phone" type="tel" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $address->phone) }}" autocomplete="tel" maxlength="30" required>
                                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label for="address_line" class="form-label">Street Address</label>
                                <textarea id="address_line" name="address_line" class="form-control @error('address_line') is-invalid @enderror" rows="3" autocomplete="street-address" maxlength="5000" required>{{ old('address_line', $address->address_line) }}</textarea>
                                @error('address_line')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="province" class="form-label">Province</label>
                                <input id="province" name="province" type="text" class="form-control @error('province') is-invalid @enderror" value="{{ old('province', $address->province) }}" autocomplete="address-level1" maxlength="100" required>
                                @error('province')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="city" class="form-label">City / Regency</label>
                                <input id="city" name="city" type="text" class="form-control @error('city') is-invalid @enderror" value="{{ old('city', $address->city) }}" autocomplete="address-level2" maxlength="100" required>
                                @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="district" class="form-label">District</label>
                                <input id="district" name="district" type="text" class="form-control @error('district') is-invalid @enderror" value="{{ old('district', $address->district) }}" maxlength="100" required>
                                @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="village" class="form-label">Village / Subdistrict</label>
                                <input id="village" name="village" type="text" class="form-control @error('village') is-invalid @enderror" value="{{ old('village', $address->village) }}" maxlength="100" required>
                                @error('village')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="postal_code" class="form-label">Postal Code</label>
                                <input id="postal_code" name="postal_code" type="text" class="form-control @error('postal_code') is-invalid @enderror" value="{{ old('postal_code', $address->postal_code) }}" autocomplete="postal-code" maxlength="20" required>
                                @error('postal_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-md-6">
                                <label for="country" class="form-label">Country</label>
                                <input id="country" name="country" type="text" class="form-control @error('country') is-invalid @enderror" value="{{ old('country', $address->country ?: 'Indonesia') }}" autocomplete="country-name" maxlength="100" required>
                                @error('country')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label">Delivery Notes <span class="text-muted">(Optional)</span></label>
                                <textarea id="notes" name="notes" class="form-control @error('notes') is-invalid @enderror" rows="2" maxlength="2000" placeholder="Landmark, gate instructions, or other details">{{ old('notes', $address->notes) }}</textarea>
                                @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>

                            <div class="col-12">
                                @if ($isEditing && $address->is_default)
                                    <div class="alert alert-success mb-0">
                                        <i class="bi bi-check-circle me-1"></i>
                                        This is your default shipping address.
                                    </div>
                                @else
                                    <div class="form-check">
                                        <input id="is_default" name="is_default" type="checkbox" value="1" class="form-check-input" @checked(old('is_default', $address->is_default))>
                                        <label for="is_default" class="form-check-label">Set as my default shipping address</label>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="store-profile-form-actions">
                            <a href="{{ route('account.addresses.index') }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="store-btn-primary">
                                <i class="bi bi-check2 me-1"></i>
                                {{ $isEditing ? 'Save Address' : 'Add Address' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
