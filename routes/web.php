<?php

use Illuminate\Support\Facades\Route;

/**
 * Store Front
 */
use App\Http\Controllers\StorefrontController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\CompareController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MidtransPaymentController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\RefundController;

/**
 * Dashboard
 */
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SubCategoryController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\RefundController as AdminRefundController;

Route::get('/', [StorefrontController::class, 'home'])->name('storefront.home');
Route::get('/shop', [StorefrontController::class, 'shop'])->name('storefront.shop');
Route::get('/shop/{slug}', [StorefrontController::class, 'product'])->name('storefront.product');
Route::get('/category/{slug}', [StorefrontController::class, 'category'])->name('storefront.category');
Route::post('/payment/midtrans/notification', [MidtransPaymentController::class, 'notification'])->name('payment.midtrans.notification');
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

/*
|------------------------------------------------------------
| Cart Routes
|-----------------------------------------------------------*/

Route::middleware('auth')->group(function () {
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

    // Product Compare
    Route::get('/compare', [CompareController::class, 'index'])->name('compare.index');
    Route::post('/compare/{product}', [CompareController::class, 'store'])->name('compare.store');
    Route::delete('/compare/{product}', [CompareController::class, 'destroy'])->name('compare.destroy');
    Route::delete('/compare', [CompareController::class, 'clear'])->name('compare.clear');

    // Product Review
    Route::post('/shop/{product}/review', [ReviewController::class, 'store'])->name('reviews.store');
    Route::patch('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Cart
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/{cartItem}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
});

/*
|------------------------------------------------------------| Authenticated User Routes
|-----------------------------------------------------------*/

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () { return view('dashboard'); })->name('dashboard');

    // My Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // My Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
});

/*
|------------------------------------------------------------| Admin Routes
|----------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/dashboard', function () { return view('admin.dashboard'); })->name('dashboard');

    // Refund Management
    Route::get('/refunds', [AdminRefundController::class, 'index'])->name('refunds.index');
    Route::get('/refunds/{refund}', [AdminRefundController::class, 'show'])->name('refunds.show');
    Route::post('/refunds/{refund}/process', [AdminRefundController::class, 'process'])->name('refunds.process');
    Route::post('/refunds/{refund}/reject', [AdminRefundController::class, 'reject'])->name('refunds.reject');    
    
    // Stock Management
    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::patch('/inventory/{product}/stock', [InventoryController::class, 'updateStock',])->name('inventory.update-stock');
    Route::post('/inventory/{product}/adjust', [InventoryController::class, 'adjustStock',])->name('inventory.adjust-stock');

    // Order Management
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');

    // User Management
    Route::get('/users', [UserController::class, 'index'])->name('users.index');

    // Sub Category CRUD
    Route::resource('sub-categories', SubCategoryController::class)->except(['show']);

    // Category CRUD
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Product CRUD
    Route::resource('products', ProductController::class)->except(['show']);
});

require __DIR__.'/auth.php';
