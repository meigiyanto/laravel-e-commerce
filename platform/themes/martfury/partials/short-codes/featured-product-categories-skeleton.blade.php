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

    .skeleton-category-block {
        padding: 20px;
        text-align: center;
    }

    .skeleton-category-image {
        width: 100%;
        height: 120px;
        border-radius: 8px;
        margin-bottom: 10px;
    }

    .skeleton-category-name {
        height: 20px;
        width: 80%;
        margin: 0 auto;
        border-radius: 4px;
    }
</style>

<div class="ps-top-categories mt-40 mb-40">
    <div class="ps-container">
        <h3 class="skeleton-loading" style="height: 30px; width: 200px; border-radius: 4px;"></h3>
        <div class="row justify-content-center">
            @for ($i = 0; $i < 6; $i++)
                <div class="col-xl-2 col-lg-3 col-md-3 col-sm-3 col-6">
                    <div class="ps-block--category skeleton-category-block">
                        <div class="skeleton-category-image skeleton-loading"></div>
                        <div class="skeleton-category-name skeleton-loading"></div>
                    </div>
                </div>
            @endfor
        </div>
    </div>
</div>