<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\CartService;
use App\Models\TenantMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = $request->user();

        $this->cartService->mergeGuestCartIntoUserCart($user->id);

        if ($user->isPlatformAdmin()) {
            return redirect()->route('platform.dashboard');
        }

        if ($user->activeTenantMembership()) {
            return redirect()->route('admin.dashboard');
        }

        return redirect()->route('account.index');
    }

    public function createAdmin(): View
    {
        return view('auth.admin-login');
    }

    public function storeAdmin(LoginRequest $request): RedirectResponse
    {
        $request->authenticateAdmin();
        $request->session()->regenerate();

        if ($request->user()->isPlatformAdmin()) {
            return redirect()->route('platform.dashboard');
        }

        return redirect()->route('admin.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}