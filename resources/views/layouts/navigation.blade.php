<nav class="navbar">
    <div class="container navbar-container">

        {{-- Logo --}}
        <a href="{{ route('dashboard') }}" class="navbar-brand">
            E-Commerce Store
        </a>

        {{-- Desktop Navigation --}}
        <div class="navbar-menu">

            <a href="{{ route('dashboard') }}"
               class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                Dashboard
            </a>

            <a href="{{ route('admin.users.index') }}" class="nav-link {{ request()->routeIs('admin.users.index') ? 'active' : '' }}">
                Users
            </a>

            <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                Categories
            </a>

            <a href="{{ route('admin.products.index') }}" class="nav-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                Products
            </a>

            <a href="{{ route('profile.edit') }}"
               class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                Profile
            </a>

            {{-- Logout --}}
            <form
                method="POST"
                action="{{ route('logout') }}"
                class="logout-form"
            >
                @csrf

                <button type="submit" class="nav-button">
                    Logout
                </button>
            </form>

        </div>
    </div>
</nav>
