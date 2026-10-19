<div class="nk-sidebar nk-sidebar-fixed is-light " data-content="sidebarMenu">
            
    <div class="nk-sidebar-element nk-sidebar-head">
        <div class="nk-sidebar-brand">
            <a href="{{ route('admin.dashboard') }}" class="logo-link nk-sidebar-logo">
                <h3>{{ config('app.name') }}</h3>
            </a>
        </div>

        <div class="nk-menu-trigger mr-n2">
            <a href="#" class="nk-nav-toggle nk-quick-nav-icon d-xl-none" data-target="sidebarMenu" aria-label="Tutup navigasi admin">
                <em class="icon ni ni-chevron-left" aria-hidden="true"></em>
            </a>
            
            <a href="#" class="nk-nav-compact nk-quick-nav-icon d-none d-xl-inline-flex" data-target="sidebarMenu" aria-label="Perkecil navigasi admin">
                <em class="icon ni ni-chevron-left" aria-hidden="true"></em>
            </a>
        </div>
    </div>

    <div class="nk-sidebar-element">
        <div class="nk-sidebar-content">
            <div class="nk-sidebar-menu">
                <ul class="nk-menu">
                    <li class="nk-menu-heading">
                        <h6 class="overline-title text-primary-alt">Workspace</h6>
                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('admin.dashboard') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-dashboard"></em></span>
                            <span class="nk-menu-text">Dashboard</span>
                        </a>
                    </li>

                    <li class="nk-menu-heading">
                        <h6 class="overline-title text-primary-alt">Catalog</h6>
                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.products.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-package"></em></span>
                            <span class="nk-menu-text">Products</span>
                        </a>
                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.categories.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-list"></em></span>
                            <span class="nk-menu-text">Categories</span>
                        </a>
                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.sub-categories.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.sub-categories.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-list-thumb"></em></span>
                            <span class="nk-menu-text">Sub Categories</span>
                        </a>
                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.attributes.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.attributes.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon">
                                <em class="icon ni ni-list"></em>
                            </span>
                            <span class="nk-menu-text">Product Attributes</span>
                        </a>
                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.inventory.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-truck"></em></span>
                            <span class="nk-menu-text">Inventory</span>
                        </a>
                    </li>
                    <li class="nk-menu-heading">
                        <h6 class="overline-title text-primary-alt">Sales</h6>
                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.orders.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-cart"></em></span>
                            <span class="nk-menu-text">Orders</span>
                        </a>
                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.refunds.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.refunds.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-reply"></em></span>
                            <span class="nk-menu-text">Refunds</span>
                        </a>
                    </li>
                    
                    <li class="nk-menu-heading">
                        <h6 class="overline-title text-primary-alt">Media</h6>
                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.product-gallery.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.product-gallery.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-img"></em></span>
                            <span class="nk-menu-text">Product Gallery</span>
                        </a>
                    </li>
                    
                    <li class="nk-menu-item {{ request()->routeIs('admin.order-cancellation-requests.*') ? 'active' : '' }}">
                        <a href="{{ route('admin.order-cancellation-requests.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-bell"></em></span>
                            <span class="nk-menu-text">Cancellation Requests</span>
                        </a>
                    </li>
                    
                    @if (auth()->user()?->isAdmin())
                        <li class="nk-menu-heading">
                            <h6 class="overline-title text-primary-alt">Administration</h6>
                        </li>
                        <li class="nk-menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.users.index') }}" class="nk-menu-link">
                                <span class="nk-menu-icon"><em class="icon ni ni-users"></em></span>
                                <span class="nk-menu-text">User Role Management</span>
                            </a>
                        </li>
                    @endif

                </ul>
            </div>
        </div>
    </div>

</div>
