<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {
    }

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $this->cartService->mergeGuestCartIntoUserCart(
            $request->user()->id
        );

        if ($request->user()->isAdmin() || $request->user()->isStaff()) {
            return redirect()->intended(
                route('admin.dashboard', absolute: false)
            );
        }

        return redirect()->intended(
            route('account.index', absolute: false)
        );
    }

    public function destroy(Request $request): RedirectResponse
    {
        $wasAdmin = $request->user()?->isAdmin() ?? false;

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $wasAdmin
            ? redirect()->route('admin.login')
            : redirect()->route('login');
    }

    public function createAdmin(): View
    {
        return view('auth.admin-login');
    }

    public function storeAdmin(LoginRequest $request): RedirectResponse
    {
        $request->authenticateAdmin();
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }
}

