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

    .skeleton-brand-item {
        width: 100%;
        height: 80px;
        border-radius: 4px;
        margin: 0 10px;
    }
</style>

<div class="mt-40 mb-40">
    <div class="ps-container">
        <div class="ps-top-categories">
            <div class="ps-section__header">
                <h3 class="skeleton-loading" style="height: 30px; width: 200px; border-radius: 4px;"></h3>
            </div>
        </div>
        <div style="display: flex; justify-content: space-between; gap: 20px;">
            @for ($i = 0; $i < 6; $i++)
                <div class="skeleton-brand-item skeleton-loading"></div>
            @endfor
        </div>
    </div>
</div>