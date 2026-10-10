<section>

    <header class="section-header">
        <h2>Profile Information</h2>
        <p>Update your profile information and account email address</p>
    </header>

    @if (session('status') === 'profile-updated')
        <div class="alert alert-success" role="alert">
            Profile updated successfully.
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('profile.update') }}"
        class="auth-form"
    >
        @csrf
        @method('PATCH')

        <div class="form-group mb-3">
            <label for="name" class="form-label">Name</label>
            <input
                id="name"
                name="name"
                type="text"
                class="form-control"
                value="{{ old('name', $user->name) }}"
                required
                autocomplete="name"
            >

            @error('name')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group mb-3">
            <label for="email" class="form-label">Email</label>

            <input
                id="email"
                name="email"
                type="email"
                class="form-control"
                value="{{ old('email', $user->email) }}"
                required
                autocomplete="username"
            >

            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary">
            Save Changes
        </button>

    </form>
</section>
