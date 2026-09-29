<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Laravel E-Commerce Store') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    <!-- <link rel="stylesheet" href="{{ asset('css/app.css') }}"> -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <div class="app-layout">

        {{-- Sidebar --}}
        <aside class="sidebar" id="sidebar">

            <div class="sidebar-logo">
                <a href="{{ route('dashboard') }}">
                    Laravel Store
                </a>
            </div>

            <nav class="sidebar-nav">

                <div class="nav-section">
                    <span class="nav-section-title">
                        MAIN
                    </span>

                    <a
                        href="{{ route('dashboard') }}"
                        class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                    >
                        <span class="sidebar-icon">⌂</span>
                        <span>Dashboard</span>
                    </a>
                </div>

                <div class="nav-section">
                    <span class="nav-section-title">
                        MANAGEMENT
                    </span>

                    <a
                        href="{{ route('admin.users.index') }}"
                        class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}"
                    >
                        <span class="sidebar-icon">👤</span>
                        <span>Users</span>
                    </a>

                    <a
                        href="{{ route('admin.products.index') }}"
                        class="sidebar-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}"
                    >
                        <span class="sidebar-icon">□</span>
                        <span>Products</span>
                    </a>

                    <a
                        href="{{ route('admin.categories.index') }}"
                        class="sidebar-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}"
                    >
                        <span class="sidebar-icon">▦</span>
                        <span>Categories</span>
                    </a>

                    <a
                        href="{{ route('admin.sub-categories.index') }}"
                        class="sidebar-link {{ request()->routeIs('admin.sub-categories.*') ? 'active' : '' }}"
                    >
                        <span class="sidebar-icon">▤</span>
                        <span>Sub-Categories</span>
                    </a>

                </div>

                <div class="nav-section">
                    <span class="nav-section-title">
                        ACCOUNT
                    </span>

                    <a
                        href="{{ route('profile.edit') }}"
                        class="sidebar-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
                    >
                        <span class="sidebar-icon">⚙</span>
                        <span>Profile</span>
                    </a>
                </div>

            </nav>

        </aside>


        {{-- Main Area --}}
        <div class="main-area">

            {{-- Header --}}
            <header class="top-header">

                <div class="header-left">

                    <button
                        type="button"
                        class="sidebar-toggle"
                        id="sidebarToggle"
                        aria-label="Toggle sidebar"
                    >
                        ☰
                    </button>

                    <div class="header-title">
                        @yield('header', 'Dashboard')
                    </div>

                </div>


                <div class="header-right">

                    @auth
                        <a
                            href="{{ route('profile.edit') }}"
                            class="header-user"
                        >
                            {{ Auth::user()->name }}
                        </a>

                        <form
                            method="POST"
                            action="{{ route('logout') }}"
                            class="logout-form"
                        >
                            @csrf

                            <button type="submit" class="logout-button">
                                Logout
                            </button>
                        </form>
                    @endauth

                </div>

            </header>


            {{-- Content --}}
            <main class="main-content">
                @yield('content')
            </main>

        </div>

    </div>


    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const toggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');

            if (!toggle || !sidebar) {
                return;
            }

            toggle.addEventListener('click', function () {
                // Desktop
                if (window.innerWidth > 900) {
                    document.body.classList.toggle('sidebar-collapsed');
                }
                // Mobile
                else {
                    sidebar.classList.toggle('open');
                }
            });
        });
    </script>

</body>
</html>
