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
use App\Http\Controllers\Admin\ProductGalleryController;
use App\Http\Controllers\Admin\AttributeController;
use App\Http\Controllers\Admin\AttributeValueController;
use App\Http\Controllers\OrderCancellationRequestController as CustomerOrderCancellationRequestController;
use App\Http\Controllers\Admin\OrderCancellationRequestController as AdminOrderCancellationRequestController;

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
use App\Http\Controllers\AccountController;
use App\Http\Controllers\UserAddressController;

Route::get('/', [StorefrontController::class, 'home'])->name('storefront.home');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'store'])->name('cart.store');

Route::patch('/cart/{product}', [CartController::class, 'update'])->name('cart.product.update');
Route::delete('/cart/{product}', [CartController::class, 'destroy'])->name('cart.product.destroy');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');

Route::get('/cart/drawer', [CartController::class, 'drawer'])->name('cart.drawer');

Route::get('/shop', [StorefrontController::class, 'shop'])->name('storefront.shop');
Route::get('/shop/{slug}', [StorefrontController::class, 'product'])->name('storefront.product');
Route::get('/category/{slug}', [StorefrontController::class, 'category'])->name('storefront.category');

Route::post('/payment/midtrans/notification', [MidtransPaymentController::class, 'notification'])->name('payment.midtrans.notification');
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handle'])->name('stripe.webhook');

/*
|-----------------------------------------------------------| 
| Authenticated User Routes
|-----------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {
    // Payment
    Route::get('/payment/{order}', [PaymentController::class, 'show'])->name('payment.show');
    Route::post('/payment/{order}/confirm', [PaymentController::class, 'confirm'])->name('payment.confirm');
    Route::get('/payment/midtrans/{order}', [MidtransPaymentController::class, 'show'])->name('payment.midtrans.show');
    Route::post('/payment/midtrans/{order}/confirm', [MidtransPaymentController::class, 'confirm'])->name('payment.midtrans.confirm');

    // Product reviews
    Route::post('/shop/{product}/review', [ReviewController::class, 'store'])->name('reviews.store');
    Route::patch('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
    
    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    
    // Profile
    Route::get('account/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('account/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('account/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Address
    Route::prefix('account/addresses')->name('account.addresses.')->controller(UserAddressController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        
        Route::post('/', 'store')->name('store');
        Route::get('/{address}/edit', 'edit')->name('edit');
        Route::put('/{address}', 'update')->name('update');
        Route::delete('/{address}', 'destroy')->name('destroy');
        Route::patch('/{address}/default', 'makeDefault')->name('default');
    });
    
    // Wishlist
    Route::get('account/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('account/wishlist/{product}', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('account/wishlist/{product}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');

    // Product comparison
    Route::get('account/compare', [CompareController::class, 'index'])->name('compare.index');
    Route::post('account/compare/{product}', [CompareController::class, 'store'])->name('compare.store');
    Route::delete('account/compare/{product}', [CompareController::class, 'destroy'])->name('compare.destroy');
    Route::delete('account/compare', [CompareController::class, 'clear'])->name('compare.clear');
    
    // Orders
    Route::get('account/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('account/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    // Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // Cancel Orders
    Route::post('account/orders/{order}/cancellation-requests', [CustomerOrderCancellationRequestController::class, 'store'])->name('orders.cancellation-requests.store');
    Route::get('/notifications', [\App\Http\Controllers\CustomerNotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}/read', [\App\Http\Controllers\CustomerNotificationController::class, 'markRead'])->name('notifications.read');

    // Refund
    Route::post('account/orders/{order}/refund', [RefundController::class, 'store'])->name('orders.refund.store');
});

/*
|-----------------------------------------------------------
| Admin and Staff Operational Routes
|----------------------------------------------------------
| Admin and staff share the operational dashboard. 
| User/account administration remains restricted to 
| administrators below.
|----------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin,staff'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Attribute Product management
    Route::resource('attributes', AttributeController::class)->except(['show']);
    Route::post('attributes/{attribute}/values', [AttributeValueController::class, 'store'])->name('attributes.values.store');
    Route::put('attributes/{attribute}/values/{attributeValue}', [AttributeValueController::class, 'update'])->name('attributes.values.update');
    Route::delete('attributes/{attribute}/values/{attributeValue}', [AttributeValueController::class, 'destroy'])->name('attributes.values.destroy');
    
    // Gallery management
    Route::get('/product-gallery', [ProductGalleryController::class,'index'])->name('product-gallery.index');
    Route::post('/product-gallery/upload', [ProductGalleryController::class, 'upload'])->name('product-gallery.upload');
    Route::put('/product-gallery/{filename}', [ProductGalleryController::class, 'replace'])->where('filename', '[^/]+')->name('product-gallery.replace');
    Route::delete('/product-gallery/{filename}', [ProductGalleryController::class, 'destroy'])->where('filename', '[^/]+')->name('product-gallery.destroy');

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
    
    Route::get('/order-cancellation-requests', [AdminOrderCancellationRequestController::class, 'index'])->name('order-cancellation-requests.index');
    Route::get('/order-cancellation-requests/{cancellationRequest}', [AdminOrderCancellationRequestController::class, 'show'])->name('order-cancellation-requests.show');
    Route::post('/order-cancellation-requests/{cancellationRequest}/decision', [AdminOrderCancellationRequestController::class, 'decide'])->name('order-cancellation-requests.decide');

    // Catalog management
    Route::resource('sub-categories', SubCategoryController::class)->parameters(['sub-categories' => 'subCategory'])->except(['show']);
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::patch('/products/{product}/images', [ProductController::class, 'updateImages'])->name('products.images.update');
    Route::resource('products', ProductController::class)->except(['show']);

    // Only administrators can manage user accounts or access the admin profile page.
    Route::middleware('role:admin')->group(function () {
        Route::get('/profile', [AdminController::class, 'profile'])->name('profile');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role.update');
        Route::patch('/profile', [AdminController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [AdminController::class, 'updatePassword'])->name('profile.password.update');
    });
});

require __DIR__.'/auth.php';
