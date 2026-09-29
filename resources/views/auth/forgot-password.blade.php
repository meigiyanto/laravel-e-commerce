<x-guest-layout>

    <div class="auth-header">
        <h1>Forgot Password?</h1>

        <p>
            Masukkan email Anda. Kami akan mengirimkan
            link untuk mengatur ulang password.
        </p>
    </div>

    <x-auth-session-status
        class="alert alert-success"
        :status="session('status')"
    />

    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf

        <div class="form-group">
            <label for="email" class="form-label">
                Email
            </label>

            <input
                id="email"
                type="email"
                name="email"
                value="{{ old('email') }}"
                required
                autofocus
                class="form-input"
            >

            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary btn-full">
            Kirim Link Reset Password
        </button>
    </form>

    <div class="auth-footer">
        <a href="{{ route('login') }}" class="auth-link">
            ← Kembali ke Login
        </a>
    </div>

</x-guest-layout>
