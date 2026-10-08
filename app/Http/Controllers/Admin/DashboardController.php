<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Models\Category;

class DashboardController extends Controller
{
    public function index()
    {
        $productsCount = Product::count();
        $ordersCount = Order::count();
        $usersCount = User::count();
        $categoriesCount = Category::count();
        return view('admin.dashboard', compact('productsCount', 'ordersCount', 'usersCount', 'categoriesCount'));
    }
}
