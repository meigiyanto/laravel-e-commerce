<section>

    <header class="section-header">
        <h2>Delete Account</h2>

        <p>
            Setelah akun dihapus, seluruh data dan resource
            yang terkait dengan akun ini tidak dapat dikembalikan.
        </p>
    </header>

    <form
        method="POST"
        action="{{ route('profile.destroy') }}"
    >
        @csrf
        @method('DELETE')

        <div class="form-group">
            <label for="password" class="form-label">
                Password
            </label>

            <input
                id="password"
                name="password"
                type="password"
                class="form-input"
                placeholder="Masukkan password untuk konfirmasi"
            >

            @error('password', 'userDeletion')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="btn btn-danger"
            onclick="return confirm('Apakah Anda yakin ingin menghapus akun?')"
        >
            Hapus Akun
        </button>

    </form>

</section>
