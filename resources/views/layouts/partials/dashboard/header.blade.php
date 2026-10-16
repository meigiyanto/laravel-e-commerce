<div class="nk-header nk-header-fixed is-light">
    <div class="container-fluid">
        <div class="nk-header-wrap">
            
            <div class="nk-menu-trigger d-xl-none ms-n1">
                <a href="#" class="nk-nav-toggle nk-quick-nav-icon" data-target="sidebarMenu">
                    <em class="icon ni ni-menu"></em>
                </a>
            </div>
            <div class="nk-header-brand d-xl-none">
                <a href="{{ route('admin.dashboard') }}" class="logo-link">
                    <img class="logo-light logo-img" src="{{ asset('images/logo.png') }}" srcset="{{ asset('images/logo2x.png 2x') }}" alt="logo">
                    <img class="logo-dark logo-img" src="{{ asset('/images/logo-dark.png') }}" srcset="{{ asset('images/logo-dark2x.png 2x') }}" alt="logo-dark">
                </a>
            </div>
            
            <div class="nk-header-brand">
                <a href="{{ route('storefront.home') }}" title="Visit Store">
                    <span class="nk-menu-icon">
                        <em class="icon ni ni-home"></em>
                    </span>
                </a>
            </div>

            <div class="nk-header-tools">
                <ul class="nk-quick-nav">
                    @php
                        $pendingCancellationNotifications = Auth::user()->unreadNotifications()
                            ->where('type', \App\Notifications\OrderCancellationRequestedNotification::class)
                            ->count();
                    @endphp
                    <li class="dropdown notification-dropdown me-n1">
                        <a href="#" class="dropdown-toggle nk-quick-nav-icon" data-bs-toggle="dropdown">
                            <div class="icon-status icon-status-info">
                                <em class="icon ni ni-bell"></em>
                                {{--
                                @if($pendingCancellationNotifications > 0)
                                    <span class="badge bg-danger">{{ $pendingCancellationNotifications > 99 ? '99+' : $pendingCancellationNotifications }}</span>
                                @endif
                                --}}
                            </div>
                        </a>
                        
                        <div class="dropdown-menu dropdown-menu-xl dropdown-menu-end">
                            @if($pendingCancellationNotifications > 0)
                                <div class="dropdown-head">
                                    <span class="sub-title nk-dropdown-title">Notifications</span>
                                    <a href="#">Mark All as Read</a>
                                </div>
                                <div class="dropdown-body">
                                    <div class="nk-notification">
                                        <div class="nk-notification-item dropdown-inner">
                                            <div class="nk-notification-icon">
                                                <em class="icon icon-circle bg-warning-dim ni ni-curve-down-right"></em>
                                            </div>
                                            <div class="nk-notification-content">
                                                <div class="nk-notification-text">
                                                    You have requested to <span>Widthdrawl</span>
                                                </div>
                                                <div class="nk-notification-time">
                                                    2 hrs ago
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="dropdown-foot center">
                                    <a href="{{ route('admin.order-cancellation-requests.index') }}">Lihat Semua</a>
                                </div>
                            @endif
                        </div>
                    </li>

                    <li class="dropdown user-dropdown">
                        <a href="#" class="dropdown-toggle" data-bs-toggle="dropdown">
                            <div class="user-toggle">
                                <div class="user-avatar sm"><em class="icon ni ni-user-alt"></em></div>
                                <div class="user-info d-none d-md-block">
                                    <div class="user-status">
                                        @if (Auth::user()->isAdmin())
                                            Administrator
                                        @elseif (Auth::user()->isStaff())
                                            Staff
                                        @else
                                            Customer
                                        @endif
                                    </div>
                                    <div class="user-name dropdown-indicator">{{ Auth::user()->name }}</div>
                                </div>
                            </div>
                        </a>

                        <div class="dropdown-menu dropdown-menu-md dropdown-menu-end">
                            <div class="dropdown-inner user-card-wrap bg-lighter d-none d-md-block">
                                <div class="user-card">
                                    <div class="user-avatar">
                                        <span>{{ strtoupper(substr(Auth::user()->name, 0, 2)) }}</span>
                                    </div>
                                    <div class="user-info">
                                        <span class="lead-text">{{ Auth::user()->name }}</span>
                                        <span class="sub-text">{{ Auth::user()->email }}</span>
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
                                        <li>
                                            <a href="{{ route('admin.profile') }}">
                                                <em class="icon ni ni-user-alt"></em>
                                                <span>Account Setting</span>
                                            </a>
                                        </li>
                                    @else
                                        <li>
                                            <a href="{{ route('profile.edit') }}">
                                                <em class="icon ni ni-user-alt"></em>
                                                <span>My Profile</span>
                                            </a>
                                        </li>
                                    @endif
                                </ul>
                            </div>

                            <div class="dropdown-inner">
                                <ul class="link-list">
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="btn btn-link p-0 border-0 bg-transparent w-100 text-start">
                                                <em class="icon ni ni-signout"></em>
                                                <span>Sign out</span>
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
            
        </div>
    </div>
</div>
