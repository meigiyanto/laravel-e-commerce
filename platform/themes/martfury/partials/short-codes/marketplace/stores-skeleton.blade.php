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

    .skeleton-store-card {
        background: #fff;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-align: center;
    }

    .skeleton-store-logo {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin: 0 auto 15px;
    }

    .skeleton-store-name {
        height: 24px;
        width: 70%;
        margin: 0 auto 10px;
        border-radius: 4px;
    }

    .skeleton-store-rating {
        height: 20px;
        width: 100px;
        margin: 0 auto 10px;
        border-radius: 4px;
    }

    .skeleton-store-products {
        height: 16px;
        width: 80px;
        margin: 0 auto;
        border-radius: 4px;
    }
</style>

<div class="ps-vendor-store mt-40 mb-40">
    <div class="ps-container">
        <div class="ps-section__header">
            <h3 class="skeleton-loading" style="height: 30px; width: 200px; border-radius: 4px;"></h3>
        </div>
        <div class="ps-section__content">
            <div class="row">
                @for ($i = 0; $i < 6; $i++)
                    <div class="col-xl-4 col-lg-4 col-md-6 col-sm-6 col-12">
                        <div class="skeleton-store-card">
                            <div class="skeleton-store-logo skeleton-loading"></div>
                            <div class="skeleton-store-name skeleton-loading"></div>
                            <div class="skeleton-store-rating skeleton-loading"></div>
                            <div class="skeleton-store-products skeleton-loading"></div>
                        </div>
                    </div>
                @endfor
            </div>
        </div>
    </div>
</div>