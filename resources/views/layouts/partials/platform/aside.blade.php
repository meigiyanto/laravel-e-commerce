<div class="nk-sidebar nk-sidebar-fixed is-light " data-content="sidebarMenu">
    <div class="nk-sidebar-element nk-sidebar-head">
        <div class="nk-sidebar-brand">
            <a href="{{ route('platform.dashboard') }}" class="logo-link nk-sidebar-logo">
                <img class="logo-light logo-img" src="/demo2/images/logo.png" srcset="/demo2/images/logo2x.png 2x" alt="logo">
                <img class="logo-dark logo-img" src="/demo2/images/logo-dark.png" srcset="/demo2/images/logo-dark2x.png 2x" alt="logo-dark">
                <img class="logo-small logo-img logo-img-small" src="/demo2/images/logo-small.png" srcset="/demo2/images/logo-small2x.png 2x" alt="logo-small">
            </a>
        </div>
        <div class="nk-menu-trigger me-n2">
            <a href="#" class="nk-nav-toggle nk-quick-nav-icon d-xl-none" data-target="sidebarMenu">
                <em class="icon ni ni-arrow-left"></em>
            </a>
            <a href="#" class="nk-nav-compact nk-quick-nav-icon d-none d-xl-inline-flex" data-target="sidebarMenu">
                <em class="icon ni ni-menu"></em>
            </a>
        </div>
    </div>
    
    <div class="nk-sidebar-element nk-sidebar-body">
        <div class="nk-sidebar-content">
            <div class="nk-sidebar-menu" data-simplebar>
                <ul class="nk-menu">
                    <li class="nk-menu-heading">
                        <h6 class="overline-title text-primary-alt">Workspace</h6>
                    </li>
                    
                    <li class="nk-menu-item {{ request()->routeIs('platform.dashboard') ? 'active' : '' }}">
                        <a href="{{ route('platform.dashboard') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-dashboard"></em></span>
                            <span class="nk-menu-text">Dashboard</span>
                        </a>
                    </li>

                    <li class="nk-menu-heading">
                        <h6 class="overline-title text-primary-alt">Tenant</h6>
                    </li>
                    <li class="nk-menu-item {{ request()->routeIs('platform.tenants.*') ? 'active' : '' }}">
                        <a href="{{ route('platform.tenants.index') }}" class="nk-menu-link">
                            <span class="nk-menu-icon"><em class="icon ni ni-package"></em></span>
                            <span class="nk-menu-text">Tenants Management</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
