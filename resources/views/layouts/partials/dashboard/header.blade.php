<div class="nk-header nk-header-fixed is-light">
    <div class="container-fluid">
        <div class="nk-header-wrap">
            <div class="nk-menu-trigger d-xl-none ms-n1">
                <a href="{{ route('admin.dashboard') }}" class="nk-nav-toggle nk-quick-nav-icon" data-target="sidebarMenu">
                    <em class="icon ni ni-menu"></em>
                </a>
            </div>
            <div class="nk-header-brand d-xl-none">
                <a href="{{ route('admin.dashboard') }}" class="logo-link">
                    <img class="logo-light logo-img" src="/demo2/images/logo.png" srcset="/demo2/images/logo2x.png 2x" alt="logo">
                    <img class="logo-dark logo-img" src="/demo2/images/logo-dark.png" srcset="/demo2/images/logo-dark2x.png 2x" alt="logo-dark">
                </a>
            </div>
            
            <a href="{{ route('storefront.home') }}">
                <em class="icon ni ni-home"></em>
                <span aria-label="Visit Store">Visit Store</span>
            </a>

            <!--
            <div class="nk-header-search ms-3 ms-xl-0">
                <em class="icon ni ni-search"></em>
                <input type="text" class="form-control border-transparent form-focus-none" placeholder="Search anything">
            </div>
            -->
            
            <div class="nk-header-tools">
                <ul class="nk-quick-nav">
                    @php
                        $currentUser = Auth::user();
                    
                        $pendingCancellationNotifications = $currentUser
                            ? $currentUser->unreadNotifications()
                                ->where(
                                    'type',
                                    \App\Notifications\OrderCancellationRequestedNotification::class
                                )
                                ->count()
                            : 0;
                    
                        $recentCancellationNotifications = $currentUser
                            ? $currentUser->unreadNotifications()
                                ->where(
                                    'type',
                                    \App\Notifications\OrderCancellationRequestedNotification::class
                                )
                                ->latest()
                                ->take(5)
                                ->get()
                            : collect();
                    @endphp
                    
                    <li class="dropdown notification-dropdown me-n1">
                        <a href="#" class="dropdown-toggle nk-quick-nav-icon" data-bs-toggle="dropdown">
                            <div class="icon-status icon-status-info">
                                <em class="icon ni ni-bell"></em>
                                @if($pendingCancellationNotifications > 0)
                                    <span class="badge bg-danger">{{ $pendingCancellationNotifications > 99 ? '99+' : $pendingCancellationNotifications }}</span>
                                @endif
                            </div>
                        </a>
                        
                        <div class="dropdown-menu dropdown-menu-xl dropdown-menu-end">
                            <div class="dropdown-head">
                                <span class="sub-title nk-dropdown-title">
                                    Permintaan Pembatalan
                                </span>
                            </div>
                    
                            <div class="dropdown-body">
                                <div class="nk-notification">
                                    @forelse ($recentCancellationNotifications as $notification)
                                        @php
                                            $requestId = $notification->data['cancellation_request_id'] ?? null;
                                        @endphp
                    
                                        @if ($requestId)
                                            <a class="nk-notification-item dropdown-inner" href="{{ route('admin.order-cancellation-requests.show', $requestId) }}">
                                                <div class="nk-notification-icon">
                                                    <em class="icon icon-circle bg-warning-dim ni ni-curve-down-right"></em>
                                                </div>
                    
                                                <div class="nk-notification-content">
                                                    <div class="nk-notification-text">
                                                        {{ $notification->data['message'] ?? 'Permintaan pembatalan baru.' }}
                                                    </div>
                    
                                                    <div class="nk-notification-text">
                                                        Pesanan #{{ $notification->data['order_number'] ?? $notification->data['order_id'] ?? '-' }}
                                                    </div>
                    
                                                    <div class="nk-notification-time">
                                                        {{ $notification->created_at?->diffForHumans() }}
                                                    </div>
                                                </div>
                                            </a>
                                        @endif
                                    @empty
                                        <div class="dropdown-inner">
                                            <div class="nk-notification-content">
                                                <div class="nk-notification-text text-soft">
                                                    Tidak ada notifikasi pembatalan baru.
                                                </div>
                                            </div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                    
                            <div class="dropdown-foot center">
                                <a href="{{ route('admin.order-cancellation-requests.index') }}">
                                    Lihat Semua Permintaan
                                </a>
                            </div>
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
