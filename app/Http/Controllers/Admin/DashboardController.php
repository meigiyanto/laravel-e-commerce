<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Models\Category;
use App\Support\Tenancy\TenantContext;

class DashboardController extends Controller
{
    public function index()
    {
        $tenantId = (int) app(TenantContext::class)->require()->getKey();

        $productsCount = Product::query()
            ->where('tenant_id', $tenantId)
            ->count();

        $ordersCount = Order::query()
            ->where('tenant_id', $tenantId)
            ->count();

        $usersCount = User::query()
            ->whereIn('id', Order::query()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('user_id')
                ->select('user_id')
                ->distinct())
            ->count();

        $categoriesCount = Category::query()
            ->where('tenant_id', $tenantId)
            ->count();

        return view('admin.dashboard', compact(
            'productsCount',
            'ordersCount',
            'usersCount',
            'categoriesCount'
        ));
    }
}