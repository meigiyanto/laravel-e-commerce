<section>

    <header class="section-header">
        <h2>Delete Account</h2>
        <p>Once an account is deleted, all data and resources associated with this account cannot be recovered.</p>
    </header>

    <form
        method="POST"
        action="{{ route('profile.destroy') }}"
    >
        @csrf
        @method('DELETE')

        <div class="form-group mb-3">
            <label for="password" class="form-label">Password</label>

            <input
                id="password"
                name="password"
                type="password"
                class="form-control"
                placeholder="Input your password for confirmation"
            >

            @error('password', 'userDeletion')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="btn btn-danger"
            onclick="return confirm('Are you sure want to delete your account?')"
        >
            Hapus Akun
        </button>

    </form>
</section>
