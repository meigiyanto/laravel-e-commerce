<div class="nk-block nk-block-middle wide-md mx-auto">
    <div class="nk-block-content nk-error-ld text-center">
        <img class="nk-error-gfx" src="{{ asset('images/gfx/error-'. $status .'.svg') }}" alt="{{ $status }}">
        <div class="wide-xs mx-auto">
            <div class="display-1 fw-bold text-primary mb-2">{{ $status }}</div>

            <h3 class="nk-error-title">{{ $title }}</h3>
            <p class="nk-error-text">{{ $message }}</p>
            
            <div class="d-flex flex-wrap justify-content-center gap-2">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary">
                    <i class="ni ni-home me-1"></i>
                    Kembali ke Dashboard</a>
                <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">
                    <i class="ni ni-arrow-left me-1"></i>
                    Halaman Sebelumnya
                </button>
            </div>
            
        </div>
    </div>
</div>