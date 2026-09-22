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

    .skeleton-flash-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
    }

    .skeleton-countdown {
        display: flex;
        gap: 10px;
    }

    .skeleton-countdown-item {
        width: 60px;
        height: 60px;
        border-radius: 4px;
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

<div class="ps-deal-of-day">
    <div class="ps-container">
        <div class="ps-section__header skeleton-flash-header">
            <div class="ps-block--countdown-deal">
                <div class="ps-block__left">
                    <h3 class="skeleton-loading" style="height: 30px; width: 150px; border-radius: 4px;"></h3>
                </div>
                <div class="ps-block__right">
                    <div class="skeleton-countdown">
                        @for ($i = 0; $i < 4; $i++)
                            <div class="skeleton-countdown-item skeleton-loading"></div>
                        @endfor
                    </div>
                </div>
            </div>
            <div class="skeleton-loading" style="height: 20px; width: 80px; border-radius: 4px;"></div>
        </div>
        <div class="ps-section__content">
            <div class="row">
                @for ($i = 0; $i < 6; $i++)
                    <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-12">
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