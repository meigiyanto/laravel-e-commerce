<div class="nk-sidebar" data-content="sidebarMenu">
    <div class="nk-sidebar-element nk-sidebar-head">
        <div class="nk-sidebar-brand">
            <a
                href="{{ route('admin.dashboard') }}"
                class="logo-link nk-sidebar-logo"
            >
                <span class="logo-text">
                    {{ config('app.name') }}
                </span>
            </a>
        </div>

        <div class="nk-menu-trigger mr-n2">
            <a href="#" class="nk-nav-toggle nk-quick-nav-icon d-xl-none" data-target="sidebarMenu">
                <em class="icon ni ni-arrow-left"></em>
            </a>
            <a href="#" class="nk-nav-compact nk-quick-nav-icon d-none d-xl-inline-flex" data-target="sidebarMenu">
                <em class="icon ni ni-chevron-left"></em>
            </a>
        </div>
    </div>


    <div class="nk-sidebar-element">
        <div class="nk-sidebar-content">
            <div class="nk-sidebar-menu">
                <ul class="nk-menu">
                    <li class="nk-menu-heading">
                        <h6 class="overline-title text-primary-alt">
                            Administration
                        </h6>
                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('admin.dashboard') }}" class="nk-menu-link">
                            <span class="nk-menu-icon">
                                <em class="icon ni ni-dashboard"></em>
                            </span>
                            <span class="nk-menu-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="nk-menu-heading">
                        <h6 class="overline-title text-primary-alt">
                            Catalog
                        </h6>
                    </li>

                    <li class="nk-menu-item {{ request()->routeIs('admin.products.index') ? 'active' : '' }}">
                        <a href="{{ route('admin.products.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon">
                                <em class="icon ni ni-package"></em>
                            </span>
                            <span class="nk-menu-text">Products</span>
                        </a>
                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.categories.index') ? 'active' : '' }}">
                        <a href="{{ route('admin.categories.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon">
                                <em class="icon ni ni-list"></em>
                            </span>
                            <span class="nk-menu-text">Categories</span>
                        </a>
                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.sub-categories.index') ? 'active' : '' }}">
                        <a
                            href="{{ route('admin.sub-categories.index') }}"
                            class="nk-menu-link"
                        >
                            <span class="nk-menu-icon">
                                <em class="icon ni ni-list-thumb"></em>
                            </span>
                            <span class="nk-menu-text">
                                Sub Categories
                            </span>
                        </a>
                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.inventory.index') ? 'active' : '' }}">
                        <a
                            href="{{ route('admin.inventory.index') }}"
                            class="nk-menu-link"
                        >
                            <span class="nk-menu-icon">
                                <em class="icon ni ni-truck"></em>
                            </span>
                            <span class="nk-menu-text">
                                Inventory
                            </span>
                        </a>
                    </li>
                    <li class="nk-menu-heading">

                        <h6 class="overline-title text-primary-alt">
                            Sales
                        </h6>

                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.orders.index') ? 'active' : '' }}">

                        <a
                            href="{{ route('admin.orders.index') }}"
                            class="nk-menu-link"
                        >
                            <span class="nk-menu-icon">
                                <em class="icon ni ni-cart"></em>
                            </span>
                            <span class="nk-menu-text">
                                Orders
                            </span>
                        </a>
                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.refunds.index') ? 'active' : '' }}">
                        <a
                            href="{{ route('admin.refunds.index') }}"
                            class="nk-menu-link"
                        >
                            <span class="nk-menu-icon">
                                <em class="icon ni ni-reply"></em>
                            </span>
                            <span class="nk-menu-text">
                                Refunds
                            </span>
                        </a>
                    </li>
                    <li class="nk-menu-heading">
                        <h6 class="overline-title text-primary-alt">
                            Users
                        </h6>

                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('admin.users.index') ? 'active' : '' }}">
                        <a
                            href="{{ route('admin.users.index') }}"
                            class="nk-menu-link"
                        >
                            <span class="nk-menu-icon">
                                <em class="icon ni ni-users"></em>
                            </span>
                            <span class="nk-menu-text">
                                Users
                            </span>
                        </a>
                    </li>
                    <li class="nk-menu-heading">
                        <h6 class="overline-title text-primary-alt">
                            Store
                        </h6>
                    </li>
                    <li class="nk-menu-item">
                        <a
                            href="{{ route('storefront.home') }}"
                            class="nk-menu-link"
                        >
                            <span class="nk-menu-icon">
                                <em class="icon ni ni-home"></em>
                            </span>
                            <span class="nk-menu-text">
                                Visit Store
                            </span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
