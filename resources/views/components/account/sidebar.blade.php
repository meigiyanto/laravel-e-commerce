@php
    $navigation = [
        ['route' => 'account.index', 'label' => 'Account Overview', 'icon' => 'bi-grid'],
        ['route' => 'profile.edit', 'label' => 'Profile Settings', 'icon' => 'bi-person'],
        ['route' => 'orders.index', 'label' => 'My Orders', 'icon' => 'bi-bag'],
        ['route' => 'account.addresses.index', 'label' => 'My Addresses', 'icon' => 'bi-geo-alt'],
        ['route' => 'wishlist.index', 'label' => 'My Wishlist', 'icon' => 'bi-heart'],
        ['route' => 'compare.index', 'label' => 'Compare Products', 'icon' => 'bi-bar-chart-line'],
    ];
@endphp

<div class="store-profile-sidebar">
    <div class="store-profile-sidebar-item">
        <div class="store-profile-sidebar-icon">
            @if ($user->avatar)
                <img src="{{ asset('storage/' . $user->avatar) }}"
                    alt="{{ $user->name }}"
                    class="rounded-circle"
                    width="48" height="48"
                    style="object-fit: cover;">
            @else
                <i class="bi bi-person-circle"></i>
            @endif
        </div>
        <div>
            <strong>{{ $user->name }}</strong>
            <span>{{ $user->email }}</span>
        </div>
    </div>

    <div class="store-profile-sidebar-divider"></div>

    @foreach ($navigation as $item)
        <a href="{{ route($item['route']) }}"
            class="store-profile-sidebar-link {{ request()->routeIs($item['route']) ? 'active' : '' }}"
            @if (request()->routeIs($item['route'])) aria-current="page" @endif>
            <i class="bi {{ $item['icon'] }}"></i>
            <span>{{ $item['label'] }}</span>
            <i class="bi bi-chevron-right"></i>
        </a>
    @endforeach
</div>