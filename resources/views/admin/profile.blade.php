@extends('layouts.admin')

@section('title', 'Admin Profile')

@section('content')
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">My Profile</h3>
                <div class="nk-block-des text-soft"><p>Manage your personal information, profile photo, and account password.</p></div>
            </div>
        </div>
    </div>
 
    @if (session('status') === 'admin-profile-updated')
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Profile information updated successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @elseif (session('status') === 'admin-password-updated')
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Password changed successfully.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    
    <div class="nk-block">
        <div class="card card-bordered mt-3">
            <div class="card-inner">
                <h6 class="title">Account Details</h6>
                <p class="text-soft mb-1">Role: <strong>{{ strtoupper($user->role) }}</strong></p>
                <p class="text-soft mb-0">Member since: <strong>{{ $user->created_at?->format('d M Y') ?? '—' }}</strong></p>
             </div>
        </div>
    </div>
    
    <div class="nk-block">
        <div class="card card-bordered">
            <div class="card-inner">
                <h5 class="card-title">Personal Information</h5>
                <p class="text-soft">Update the information associated with your account.</p>

                <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
                            @csrf
                            @method('PATCH')
                            
                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label class="form-label" for="avatar">Profile Photo</label>
                                    <div class="user-avatar xl">
                                        @if ($user->avatar)
                                            <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }} avatar" style="width: 120px; height: 120px; object-fit: cover; border-radius: 50%;">
                                        @else
                                            <span>{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="col-md-9">
                                    <div class="form-group">
                                        <input class="form-control @error('avatar', 'profile') is-invalid @enderror" type="file" id="avatar" name="avatar" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                        <div class="form-note">JPG, PNG, or WebP. Maximum size: 2 MB.</div>
                                        @error('avatar', 'profile') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label" for="name">Full Name</label>
                                        <input class="form-control @error('name', 'profile') is-invalid @enderror" type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name">
                                        @error('name', 'profile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>

                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="email">Email Address</label>
                                        <input class="form-control @error('email', 'profile') is-invalid @enderror" type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email">
                                        @error('email', 'profile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                        <div class="form-note">Changing your email will reset its verification status.</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="phone">Phone Number</label>
                                        <input class="form-control @error('phone', 'profile') is-invalid @enderror" type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" maxlength="30" autocomplete="tel">
                                        @error('phone', 'profile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="birth_date">Date of Birth</label>
                                        <input class="form-control @error('birth_date', 'profile') is-invalid @enderror" type="date" id="birth_date" name="birth_date" value="{{ old('birth_date', $user->birth_date?->format('Y-m-d')) }}" max="{{ now()->toDateString() }}" autocomplete="bday">
                                        @error('birth_date', 'profile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="city">City / Regency</label>
                                        <input class="form-control @error('city', 'profile') is-invalid @enderror" type="text" id="city" name="city" value="{{ old('city', $user->city) }}" maxlength="100" autocomplete="address-level2">
                                        @error('city', 'profile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="province">Province</label>
                                        <input class="form-control @error('province', 'profile') is-invalid @enderror" type="text" id="province" name="province" value="{{ old('province', $user->province) }}" maxlength="100" autocomplete="address-level1">
                                        @error('province', 'profile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="form-label" for="postal_code">Postal Code</label>
                                        <input class="form-control @error('postal_code', 'profile') is-invalid @enderror" type="text" id="postal_code" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}" maxlength="20" autocomplete="postal-code">
                                        @error('postal_code', 'profile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label" for="address">Address</label>
                                <textarea class="form-control @error('address', 'profile') is-invalid @enderror" id="address" name="address" rows="3" maxlength="5000" autocomplete="street-address">{{ old('address', $user->address) }}</textarea>
                                @error('address', 'profile') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            
                            <button type="submit" class="btn btn-primary"><em class="icon ni ni-save"></em><span>Save Changes</span></button>
                        </form>
            </div>
        </div>
    </div>
    
    <div class="nk-block">
        <div class="card card-bordered">
            <div class="card-inner">
                <h5>Change Password</h5>
                <p class="text-soft">Enter your current password before choosing a new one.</p>
                
                <form method="POST" action="{{ route('admin.profile.password.update') }}">
                                @csrf
                                @method('PUT')
                                 <div class="form-group">
                                     <label class="form-label" for="current_password">Current Password</label>
                                     <div class="form-control-wrap">
                                        <input class="form-control @error('current_password', 'passwordUpdate') is-invalid @enderror" type="password" id="current_password" name="current_password" required autocomplete="current-password">
                                        @error('current_password', 'passwordUpdate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                     </div>
                                 </div>
                                 <div class="form-group">
                                    <label class="form-label" for="password">New Password</label>
                                     <div class="form-control-wrap">
                                        <input class="form-control @error('password', 'passwordUpdate') is-invalid @enderror" type="password" id="password" name="password" required autocomplete="new-password">
                                        @error('password', 'passwordUpdate') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                     </div>
                                 </div>
                                 <div class="form-group">
                                    <label class="form-label" for="password_confirmation">Confirm New Password</label>
                                     <div class="form-control-wrap">
                                        <input class="form-control" type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
                                     </div>
                                 </div>
                                <button type="submit" class="btn btn-primary mt-3"><em class="icon ni ni-lock-alt"></em><span>Update Password</span></button>
                             </form>
            </div>
        </div>
    </div>

@endsection
