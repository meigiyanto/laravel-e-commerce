<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Models\Category;

class DashboardController extends Controller
{
    public function index()
    {
        $products = Product::get();
        $orders = Order::get();
        $users = User::get();
        $categories = Category::get();
        return view('admin.dashboard', compact('products', 'orders', 'users', 'categories'));
    }
}
