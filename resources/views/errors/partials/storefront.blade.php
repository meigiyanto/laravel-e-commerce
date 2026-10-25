<section class="store-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12 col-md-9 col-lg-7">
                <div class="card border-0 shadow-sm text-center">
                    <div class="card-body p-4 p-md-5">
                        <div class="display-1 fw-bold text-primary mb-2">{{ $status }}</div>

                        <h1 class="h3 fw-bold mb-3">{{ $title }}</h1>
                        <p class="text-muted mb-4">{{ $message }}</p>
                        
                        <div class="d-flex flex-wrap justify-content-center gap-2">
                            <a href="{{ route('storefront.home') }}"
                               class="store-btn-primary">
                                <i class="bi bi-house me-1"></i>
                                Kembali ke Beranda
                            </a>

                            <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">
                                <i class="bi bi-arrow-left me-1"></i>
                                Halaman Sebelumnya
                            </button>
                        </div>

                        <p class="small text-muted mt-4 mb-0">
                            Jika masalah terus terjadi, silakan coba kembali
                            beberapa saat lagi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
