<!-- sidebar @s -->

<div
    class="nk-sidebar nk-sidebar-fixed is-light"
    data-content="sidebarMenu"
>

    {{-- Sidebar header --}}
    <div class="nk-sidebar-element nk-sidebar-head">
        <div class="nk-sidebar-brand">
            <a
                href="{{ url('/') }}"
                class="logo-link nk-sidebar-logo"
            >
                <img
                    class="logo-light logo-img"
                    src="{{ asset('images/logo.png') }}"
                    srcset="{{ asset('images/logo2x.png') }} 2x"
                    alt="{{ config('app.name') }}"
                >

                <img
                    class="logo-dark logo-img"
                    src="{{ asset('images/logo-dark.png') }}"
                    srcset="{{ asset('images/logo-dark2x.png') }} 2x"
                    alt="{{ config('app.name') }}"
                >

                <img
                    class="logo-small logo-img logo-img-small"
                    src="{{ asset('images/logo-small.png') }}"
                    srcset="{{ asset('images/logo-small2x.png') }} 2x"
                    alt="{{ config('app.name') }}"
                >

            </a>

        </div>

        {{-- Sidebar controls --}}
        <div class="nk-menu-trigger me-n2">
            {{-- Mobile --}}
            <a
                href="#"
                class="nk-nav-toggle nk-quick-nav-icon d-xl-none"
                data-target="sidebarMenu"
                aria-label="Open sidebar"
            >

                <em class="icon ni ni-arrow-left"></em>            </a>

            {{-- Desktop compact --}}
            <a
                href="#"
                class="nk-nav-compact nk-quick-nav-icon d-none d-xl-inline-flex"
                data-target="sidebarMenu"
                aria-label="Compact sidebar"
            >

                <em class="icon ni ni-menu"></em>

            </a>
        </div>
    </div>


    {{-- Sidebar body --}}
    <div class="nk-sidebar-element nk-sidebar-body">
        <div class="nk-sidebar-content">
            <div
                class="nk-sidebar-menu"
                data-simplebar
            >

                <ul class="nk-menu">
                    {{-- Dashboard --}}
                    <li class="nk-menu-heading">

                        <h6 class="overline-title text-primary-alt">
                            Dashboard &amp; Panels
                        </h6>

                    </li>


                    <li class="nk-menu-item">
                        <a
                            href="{{ url('/') }}"
                            class="nk-menu-link"
                        >
                            <span class="nk-menu-icon">
                                <em class="icon ni ni-home"></em>

                            </span>

                            <span class="nk-menu-text">
                                Dashboard
                            </span>

                        </a>

                    </li>


                    {{-- Products --}}
                    <li class="nk-menu-heading">

                        <h6 class="overline-title text-primary-alt">
                            E-Commerce
                        </h6>

                    </li>


                    <li class="nk-menu-item has-sub">
                        <a
                            href="#"
                            class="nk-menu-link nk-menu-toggle"
                            aria-expanded="false"
                        >

                            <span class="nk-menu-icon">

                                <em class="icon ni ni-package"></em>

                            </span>

                            <span class="nk-menu-text">
                                Products
                            </span>
                        </a>


                        <ul class="nk-menu-sub">
                            <li class="nk-menu-item">
                                <a
                                    href="{{ url('/products') }}"
                                    class="nk-menu-link"
                                >

                                    <span class="nk-menu-text">
                                        All Products
                                    </span>

                                </a>

                            </li>
                            <li class="nk-menu-item">
                                <a
                                    href="{{ url('/admin/products') }}"
                                    class="nk-menu-link"
                                >

                                    <span class="nk-menu-text">
                                        Manage Products
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </li>


                    {{-- Categories --}}
                    <li class="nk-menu-item has-sub">
                        <a
                            href="#"
                            class="nk-menu-link nk-menu-toggle"
                            aria-expanded="false"
                        >

                            <span class="nk-menu-icon">
                                <em class="icon ni ni-list-round"></em>
                            </span>

                            <span class="nk-menu-text">
                                Categories
                            </span>

                        </a>


                        <ul class="nk-menu-sub">
                            <li class="nk-menu-item">
                                <a
                                    href="{{ url('/categories') }}"
                                    class="nk-menu-link"
                                >

                                    <span class="nk-menu-text">
                                        All Categories
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </li>


                    {{-- Orders --}}
                    <li class="nk-menu-item">
                        <a
                            href="{{ url('/orders') }}"
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


                    {{-- Users --}}
                    <li class="nk-menu-heading">

                        <h6 class="overline-title text-primary-alt">
                            Management
                        </h6>

                    </li>
                    <li class="nk-menu-item">
                        <a
                            href="{{ url('/users') }}"
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
                </ul>
            </div>
        </div>
    </div>
</div>
<!-- sidebar @e -->
