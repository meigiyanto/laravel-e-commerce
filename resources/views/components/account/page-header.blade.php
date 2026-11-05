<section class="store-section pb-3">
    <div class="container">
        <div class="store-section-header">
            <div>
                <span class="store-section-eyebrow">
                    {{ $eyebrow ?? 'My Account' }}
                </span>
                <h1 class="store-section-title">{{ $title }}</h1>
                <p class="store-section-subtitle">{{ $description }}</p>
            </div>

            @if (!empty($action))
                <div>
                    {!! $action !!}
                </div>
            @endif
        </div>
    </div>
</section>