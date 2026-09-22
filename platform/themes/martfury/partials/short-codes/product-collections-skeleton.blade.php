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

    .skeleton-tab {
        height: 40px;
        width: 100px;
        border-radius: 4px;
        margin-right: 10px;
        display: inline-block;
    }
</style>

<div class="ps-product-list mt-40 mb-40">
    <div class="ps-container">
        <div class="ps-section__header">
            <h3 class="skeleton-loading" style="height: 30px; width: 200px; border-radius: 4px; display: inline-block;"></h3>
            <ul class="ps-section__links">
                <li><div class="skeleton-loading" style="height: 20px; width: 80px; border-radius: 4px;"></div></li>
            </ul>
        </div>
        <div class="ps-section__content">
            <div class="ps-tabs">
                <div class="ps-tab-list">
                    @for ($i = 0; $i < 3; $i++)
                        <div class="skeleton-tab skeleton-loading"></div>
                    @endfor
                </div>
                <div class="ps-carousel--nav owl-slider" style="display: flex; gap: 15px;">
                    @for ($i = 0; $i < 7; $i++)
                        <div class="ps-product skeleton-product-item" style="flex: 1;">
                            <div class="skeleton-product-image skeleton-loading"></div>
                            <div class="skeleton-product-title skeleton-loading"></div>
                            <div class="skeleton-product-price skeleton-loading"></div>
                        </div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
</div>