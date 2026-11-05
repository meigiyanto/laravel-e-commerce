<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Store;

class DashboardController extends Controller
{
    public function index()
    {
        return view('platform.dashboard', [
            'tenantsCount' => Tenant::query()->count(),
            'activeTenantsCount' => Tenant::query()->where('status', 'active')->count(),
            'storesCount' => Store::query()->count(),
            'usersCount' => User::query()->count(),
        ]);
    }
}