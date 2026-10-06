<section>

    <header class="section-header">
        <h2>Update Password</h2>
        <p>Update your password</p>
    </header>

    <form
        method="POST"
        action="{{ route('profile.update') }}"
        class="auth-form"
    >
        @csrf
        @method('PATCH')

        <div class="form-group mb-3">
            <label for="current_password" class="form-label">Current Password</label>
            <input
                id="current_password"
                name="current_password"
                type="password"
                class="form-control"
                value=""
                required
            >

            @error('current_password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group mb-3">
            <label for="new_password" class="form-label">New Password</label>
            <input
                id="new_password"
                name="new_password"
                type="password"
                class="form-control"
                value=""
                required
            >

            @error('new_password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <div class="form-group mb-3">
            <label for="confirm_password" class="form-label">Password Confirm</label>

            <input
                id="confirm_password"
                name="confirm_password"
                type="password"
                class="form-control"
                value=""
                required
            >

            @error('confirm_password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary">
            Save Changes
        </button>

        @if (session('status') === 'profile-updated')
            <p class="success-message">
                Profile updated successfully.
            </p>
        @endif

    </form>

</section>
