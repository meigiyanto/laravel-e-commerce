<?php

use Botble\Theme\Facades\Theme;
use Illuminate\Support\Facades\Route;
use Theme\Martfury\Http\Controllers\MartfuryController;

Theme::registerRoutes(function (): void {
    Route::group(['controller' => MartfuryController::class], function (): void {
        Route::group(['prefix' => 'ajax', 'as' => 'public.ajax.'], function (): void {
            Route::get('products', 'ajaxGetProducts')
                ->name('products');

            Route::get('cart', 'ajaxCart')
                ->name('cart');

            Route::get('search-products', 'ajaxSearchProducts')
                ->name('search-products');

            Route::post('send-download-app-links', 'ajaxSendDownloadAppLinks')
                ->name('send-download-app-links');

            Route::get('products-by-collection/{id}', 'ajaxGetProductsByCollection')
                ->name('products-by-collection')
                ->wherePrimaryKey();

            Route::get('products-by-category/{id}', 'ajaxGetProductsByCategory')
                ->name('products-by-category')
                ->wherePrimaryKey();
        });
    });
});

Theme::routes();
