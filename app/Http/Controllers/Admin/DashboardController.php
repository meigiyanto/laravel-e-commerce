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

        $products = Product::query();
        $orders = Order::query();
        $categories = Category::query();

        if ($isMultiTenant) {
            $products->where('tenant_id', $tenantId);
            $orders->where('tenant_id', $tenantId);
            $categories->where('tenant_id', $tenantId);
        } else {
            $products->whereNull('tenant_id');
            $orders->whereNull('tenant_id');
            $categories->whereNull('tenant_id');
        }

        $productsCount = $products->count();
        $ordersCount = $orders->count();
        $categoriesCount = $categories->count();

        $usersCount = User::query()
            ->whereIn('id', (clone $orders)
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