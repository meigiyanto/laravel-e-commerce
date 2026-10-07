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
    /**
     * Cart service.
     */
    public function __construct(
        protected CartService $cartService
    ) {
    }

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        /*
         * Regenerate the session after successful authentication
         * to prevent session fixation attacks.
         */
        $request->session()->regenerate();

        /*
         * Merge the guest cart into the authenticated user's
         * database cart.
         *
         * This allows products added before login to remain
         * available after authentication.
         */
        $this->cartService->mergeGuestCartIntoUserCart(
            $request->user()->id
        );

        /*
         * Admin users go to the admin dashboard.
         * Regular users go to the regular dashboard.
         */
        if ($request->user()->isAdmin()) {
            return redirect()->intended(
                route('admin.dashboard', absolute: false)
            );
        }

        return redirect()->intended(
            route('dashboard', absolute: false)
        );
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        /*
         * Completely invalidate the current session.
         */
        $request->session()->invalidate();

        /*
         * Generate a new CSRF token.
         */
        $request->session()->regenerateToken();

        /*
         * Send the user back to login.
         */
        return redirect()->route('login');
    }
}
