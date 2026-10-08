@php
    $passwordErrors = $errors->getBag('updatePassword');
@endphp

<section>
    <form
        method="POST"
        action="{{ route('password.update') }}"
        class="auth-form"
    >
        @csrf
        @method('PUT')

        <div class="form-group mb-3">
            <label for="current_password" class="form-label">
                Current Password
            </label>

            <input
                id="current_password"
                name="current_password"
                type="password"
                class="form-control"
                autocomplete="current-password"
                required
            >

            @if ($passwordErrors->has('current_password'))
                <p class="form-error">
                    {{ $passwordErrors->first('current_password') }}
                </p>
            @endif
        </div>

        <div class="form-group mb-3">
            <label for="password" class="form-label">
                New Password
            </label>

            <input
                id="password"
                name="password"
                type="password"
                class="form-control"
                autocomplete="new-password"
                required
            >

            @if ($passwordErrors->has('password'))
                <p class="form-error">
                    {{ $passwordErrors->first('password') }}
                </p>
            @endif
        </div>

        <div class="form-group mb-3">
            <label for="password_confirmation" class="form-label">
                Password Confirm
            </label>

            <input
                id="password_confirmation"
                name="password_confirmation"
                type="password"
                class="form-control"
                autocomplete="new-password"
                required
            >

            @if ($passwordErrors->has('password'))
                <p class="form-error">
                    {{ $passwordErrors->first('password') }}
                </p>
            @endif
        </div>

        <button type="submit" class="btn btn-primary">
            Save Changes
        </button>

        @if (session('status') === 'password-updated')
            <p class="success-message">
                Password updated successfully.
            </p>
        @endif

    </form>

</section>
