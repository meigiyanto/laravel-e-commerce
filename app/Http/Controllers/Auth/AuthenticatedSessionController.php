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
    public function __construct(protected CartService $cartService) {}

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticateStorefront();
        $request->session()->regenerate();

        $user = $request->user();

        $this->cartService->mergeGuestCartIntoUserCart($user->id);
        return redirect()->route('account.index');
    }

    public function storeAdmin(LoginRequest $request): RedirectResponse
    {
        $request->authenticateAdmin();
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard');
    }

    public function createPlatform(): View
    {
        return view('auth.platform-login');
    }

    public function storePlatform(LoginRequest $request): RedirectResponse
    {
        $request->authenticatePlatform();
        $request->session()->regenerate();

        return redirect()->route('platform.dashboard');
    }

    public function createAdmin(): View
    {
        return view('auth.admin-login');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}