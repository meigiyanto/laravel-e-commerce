<div class="nk-header nk-header-fixed is-light">
    <div class="container-fluid">
        <div class="nk-header-wrap">
            <div class="nk-menu-trigger d-xl-none ms-n1">
                <a href="#" class="nk-nav-toggle nk-quick-nav-icon" data-target="sidebarMenu"><em class="icon ni ni-menu"></em></a>
            </div>

            <div class="nk-header-brand d-xl-none">
                <a href="{{ route('admin.dashboard') }}" class="logo-link">
                    <img class="logo-light logo-img" src="{{ asset('/images/logo.png') }}" alt="logo">
                    <img class="logo-dark logo-img" src="{{ asset('/images/logo-dark.png') }}" alt="logo-dark">
                </a>
            </div>

            <!--
            <div class="nk-header-search ms-3 ms-xl-0">
                <em class="icon ni ni-search"></em>
                <input type="text" class="form-control border-transparent form-focus-none" placeholder="Search anything">
            </div>
            -->

            <div class="nk-header-tools">
                <ul class="nk-quick-nav">
                    <!-- .dropdown -->
                    <li class="dropdown user-dropdown">
                        <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                            <div class="user-toggle">
                                <div class="user-avatar sm">
                                    <em class="icon ni ni-user-alt"></em>
                                </div>
                                <div class="user-info d-none d-md-block">
                                    <div class="user-status">
                                        {{ Auth::user()->isAdmin() ? 'Administrator' : 'Customer' }}
                                    </div>
                                    <div class="user-name dropdown-indicator">
                                        {{ Auth::user()->name }}
                                    </div>

                                </div>
                            </div>
                        </a>

                        <div class="dropdown-menu dropdown-menu-md dropdown-menu-end">
                            <div class="dropdown-inner user-card-wrap bg-lighter d-none d-md-block">
                                <div class="user-card">
                                    <div class="user-avatar">
                                        <span>
                                            {{ strtoupper(substr(Auth::user()->name, 0, 2)) }}
                                        </span>
                                    </div>

                                    <div class="user-info">
                                        <span class="lead-text">
                                            {{ Auth::user()->name }}
                                        </span>
                                        <span class="sub-text">
                                            {{ Auth::user()->email }}
                                        </span>

                                    </div>
                                </div>
                            </div>

                            <div class="dropdown-inner">
                                <ul class="link-list">
                                @if (Auth::user()->isAdmin())
                                    <li>
                                        <a href="{{ route('admin.dashboard') }}">
                                            <em class="icon ni ni-dashboard"></em>
                                            <span>Admin Dashboard</span>
                                        </a>
                                    </li>
                                @endif
                                    <li>
                                        <a href="{{ route('admin.profile') }}">
                                            <em class="icon ni ni-user-alt"></em>
                                            <span>View Profile</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a href="{{ route('admin.profile') }}">
                                            <em class="icon ni ni-setting-alt"></em>
                                            <span>Account Setting</span>
                                        </a>
                                    </li>
                                    <!--
                                    <li>
                                        <a href="#">
                                            <em class="icon ni ni-activity-alt"></em>
                                            <span>Login Activity</span>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dark-switch" href="#">
                                            <em class="icon ni ni-moon"></em>
                                            <span>Dark Mode</span>
                                        </a>
                                    </li>
                                    -->
                                </ul>
                            </div>

                            <div class="dropdown-inner">
                                <ul class="link-list">
                                    <li>
                                        <form
                                            method="POST"
                                            action="{{ route('logout') }}"
                                        >
                                            @csrf

                                            <button
                                                type="submit"
                                                class="btn btn-link p-0 border-0 bg-transparent w-100 text-start"
                                            >
                                                <em class="icon ni ni-signout"></em>
                                                <span>Sign out</span>
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>

                        </div>
                    </li><!-- .dropdown -->
                </ul><!-- .nk-quick-nav -->
            </div><!-- .nk-header-tools -->
        </div><!-- .nk-header-wrap -->
    </div><!-- .container-fliud -->
</div>
<!-- main header @e -->
