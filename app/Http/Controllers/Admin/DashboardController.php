<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

class DashboardController extends Controller
{
    public function index()
    {
        $isMultiTenant = config('operational.mode', 'single_store') === 'multi_tenant';

        $tenantId = $isMultiTenant
            ? app(TenantContext::class)->require()->getKey()
            : null;

        $scope = function ($query) use ($isMultiTenant, $tenantId) {
            return $isMultiTenant
                ? $query->where('tenant_id', $tenantId)
                : $query->whereNull('tenant_id');
        };

        $productsCount = $scope(Product::query())->count();
        $ordersQuery = $scope(Order::query());
        $ordersCount = (clone $ordersQuery)->count();
        $categoriesCount = $scope(Category::query())->count();

        $usersCount = User::query()
            ->whereIn('id', (clone $ordersQuery)
                ->whereNotNull('user_id')
                ->select('user_id')
                ->distinct())
            ->count();

        return view('admin.dashboard', compact(
            'productsCount',
            'ordersCount',
            'usersCount',
            'categoriesCount'
        ));
    }
}
