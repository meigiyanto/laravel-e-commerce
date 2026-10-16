{{-- Quick View Product Modal --}}
<div class="modal fade store-quick-view-modal" id="storeProductQuickView" tabindex="-1" aria-labelledby="storeQuickViewTitle" data-bs-backdrop="static" aria-hidden="true" data-placeholder="{{ $placeholder }}" data-product-base-url="{{ url('/shop') }}">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <span class="store-quick-view-eyebrow">DETAIL PRODUK</span>
                    <h2 class="modal-title" id="storeQuickViewTitle">Informasi produk</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="store-quick-view-main">
                    <div class="store-quick-view-image-wrap">
                        <img id="storeQuickViewImage" class="store-quick-view-image" src="{{ asset('storage/products/placeholder.png') }}" alt="">
                    </div>
                    <div class="store-quick-view-info">
                        <span id="storeQuickViewCategory" class="store-quick-view-category">Produk</span>
                        <h3 id="storeQuickViewName"></h3>
                        <div class="store-quick-view-rating">
                            <span class="store-quick-view-stars">
                                <i class="bi bi-star-fill"></i>
                            </span>
                            <span id="storeQuickViewRating">0.0</span>
                            <span id="storeQuickViewReviews"></span>
                        </div>
                        <div id="storeQuickViewPrice" class="store-quick-view-price"></div>
                        <p id="storeQuickViewDescription" class="store-quick-view-description"></p>
                    </div>
                </div>
                <section class="store-quick-view-section">
                    <div class="store-quick-view-section-heading">
                        <h3>Varian produk</h3>
                        <span id="storeQuickViewVariantCount"></span>
                    </div>
                    <div id="storeQuickViewVariants"></div>
                </section>
                <section class="store-quick-view-section">
                    <h3>Spesifikasi produk</h3>
                    <div class="mt-2" id="storeQuickViewSpecifications"></div>
                </section>
            </div>
            <div class="modal-footer">
                <a id="storeQuickViewDetailLink" href="#" class="store-quick-view-detail-link">
                    Lihat halaman produk
                    <i class="bi bi-arrow-up-right"></i>
                </a>
                <button type="button" class="store-quick-view-close" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
