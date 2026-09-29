<section>

    <header class="section-header">
        <h2>Profile Information</h2>

        <p>
            Update informasi profile dan alamat email akun Anda.
        </p>
    </header>

    <form
        method="POST"
        action="{{ route('profile.update') }}"
        class="auth-form"
    >
        @csrf
        @method('PATCH')

        <div class="form-group">
            <label for="name" class="form-label">
                Nama
            </label>

            <input
                id="name"
                name="name"
                type="text"
                class="form-input"
                value="{{ old('name', $user->name) }}"
                required
                autocomplete="name"
            >

            @error('name')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group">
            <label for="email" class="form-label">
                Email
            </label>

            <input
                id="email"
                name="email"
                type="email"
                class="form-input"
                value="{{ old('email', $user->email) }}"
                required
                autocomplete="username"
            >

            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary">
            Simpan Perubahan
        </button>

        @if (session('status') === 'profile-updated')
            <p class="success-message">
                Profile berhasil diperbarui.
            </p>
        @endif

    </form>

</section>
