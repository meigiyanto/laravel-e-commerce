<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    /**
     * Display the authenticated customer's account overview.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $user->loadCount(['orders', 'addresses']);
        $user->load('defaultAddress');

        $recentOrders = $user->orders()
            ->latest()
            ->limit(5)
            ->get();

        return view('storefront.account.index', compact('user', 'recentOrders'));
    }
}