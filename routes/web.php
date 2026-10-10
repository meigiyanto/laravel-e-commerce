<?php

use Illuminate\Support\Facades\Route;

/**
 * Dashboard
 */
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\RefundController as AdminRefundController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SubCategoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminController;

/**
 * Store Front
 */
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\MidtransPaymentController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\WishlistController;

Route::get('/', [StorefrontController::class, 'home'])->name('storefront.home');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
Route::get('/cart/drawer', [CartController::class, 'drawer'])->name('cart.drawer');
Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{product}', [CartController::class, 'destroy'])->name('cart.destroy');

Route::get('/shop', [StorefrontController::class, 'shop'])->name('storefront.shop');
Route::get('/shop/{slug}', [StorefrontController::class, 'product'])->name('storefront.product');
Route::get('/category/{slug}', [StorefrontController::class, 'category'])->name('storefront.category');

Route::post('/payment/midtrans/notification', [MidtransPaymentController::class, 'notification'])->name('payment.midtrans.notification');
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

/*
|-----------------------------------------------------------| Authenticated User Routes
|-----------------------------------------------------------*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Refund
    Route::post('/orders/{order}/refund', [RefundController::class, 'store'])->name('orders.refund.store');

    // Payment
    Route::get('/payment/{order}', [PaymentController::class, 'show'])->name('payment.show');
    Route::post('/payment/{order}/confirm', [PaymentController::class, 'confirm'])->name('payment.confirm');
    Route::get('/payment/midtrans/{order}', [MidtransPaymentController::class, 'show'])->name('payment.midtrans.show');
    Route::post('/payment/midtrans/{order}/confirm', [MidtransPaymentController::class, 'confirm'])->name('payment.midtrans.confirm');

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{product}', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/{product}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

    // Product comparison
    Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
    Route::post('/compare/{product}', [CompareController::class, 'store'])->name('compare.store');
    Route::delete('/compare/{product}', [CompareController::class, 'destroy'])->name('compare.destroy');
    Route::delete('/compare', [CompareController::class, 'clear'])->name('compare.clear');

    // Product reviews
    Route::post('/shop/{product}/review', [ReviewController::class, 'store'])->name('reviews.store');
    Route::patch('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');

    // Customer orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

    // Account profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/account', function () {
        return view('storefront.account.index');
    })->name('account.index');
});

/*
|-----------------------------------------------------------| Admin and Staff Operational Routes
|----------------------------------------------------------
|
| Admin and staff share the operational dashboard. User/account
| administration remains restricted to administrators below.
*/

Route::middleware(['auth', 'role:admin,staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Refund management
    Route::get('/refunds', [AdminRefundController::class, 'index'])->name('refunds.index');
    Route::get('/refunds/{refund}', [AdminRefundController::class, 'show'])->name('refunds.show');
    Route::post('/refunds/{refund}/process', [AdminRefundController::class, 'process'])->name('refunds.process');
    Route::post('/refunds/{refund}/reject', [AdminRefundController::class, 'reject'])->name('refunds.reject');

    // Inventory management
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::patch('/inventory/{product}/stock', [InventoryController::class, 'updateStock'])->name('inventory.update-stock');
    Route::post('/inventory/{product}/adjust', [InventoryController::class, 'adjustStock'])->name('inventory.adjust-stock');

    // Order management
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');

    // Catalog management
    Route::resource('sub-categories', SubCategoryController::class)
        ->parameters(['sub-categories' => 'subCategory'])
        ->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('products', ProductController::class)->except(['show']);

    // Only administrators can manage user accounts or access the admin profile page.
    Route::middleware('role:admin')->group(function () {
        Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
    });
});

require __DIR__.'/auth.php';
