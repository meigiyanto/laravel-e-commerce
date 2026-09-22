<style>
    @keyframes skeleton-loading {
        0% {
            background-position: -200% 0;
        }
        100% {
            background-position: 200% 0;
        }
    }

    .skeleton-loading {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: skeleton-loading 1.5s infinite;
    }

    .skeleton-sidebar {
        width: 100%;
        padding: 20px;
    }

    .skeleton-category-title {
        height: 30px;
        width: 150px;
        border-radius: 4px;
        margin-bottom: 20px;
    }

    .skeleton-subcategory {
        height: 20px;
        width: 120px;
        border-radius: 4px;
        margin-bottom: 10px;
    }

    .skeleton-product-item {
        padding: 15px;
    }

    .skeleton-product-image {
        width: 100%;
        padding-bottom: 100%;
        position: relative;
        border-radius: 4px;
        margin-bottom: 10px;
    }

    .skeleton-product-title {
        height: 40px;
        border-radius: 4px;
        margin-bottom: 8px;
    }

    .skeleton-product-price {
        height: 24px;
        width: 60%;
        border-radius: 4px;
    }
</style>

<div class="ps-page--shop">
    <div class="ps-container">
        <div class="ps-layout--shop">
            <div class="ps-layout__left">
                <aside class="widget widget_shop">
                    <div class="skeleton-sidebar">
                        <div class="skeleton-category-title skeleton-loading"></div>
                        @for ($i = 0; $i < 3; $i++)
                            <div class="skeleton-subcategory skeleton-loading"></div>
                        @endfor
                    </div>
                </aside>
            </div>
            <div class="ps-layout__right">
                <div class="ps-shopping ps-tab-root">
                    <div class="ps-shopping__header">
                        <div class="skeleton-loading" style="height: 40px; width: 100%; border-radius: 4px;"></div>
                    </div>
                    <div class="ps-tabs">
                        <div class="ps-shopping__content">
                            <div class="row">
                                @for ($i = 0; $i < 8; $i++)
                                    <div class="col-xl-3 col-lg-4 col-md-4 col-sm-6 col-6">
                                        <div class="ps-product skeleton-product-item">
                                            <div class="skeleton-product-image skeleton-loading"></div>
                                            <div class="skeleton-product-title skeleton-loading"></div>
                                            <div class="skeleton-product-price skeleton-loading"></div>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>