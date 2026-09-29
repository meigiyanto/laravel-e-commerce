<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SubCategoryController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return view('welcome');
});

/*
|------------------------------------------------------------------| Authenticated User Routes
|------------------------------------------------------------------*/

Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');
});

/*
|------------------------------------------------------------------| Admin Routes
|-----------------------------------------------------------------
*/

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', function () {
            return view('admin.dashboard');
        })->name('dashboard');

        // User Management
        Route::get('/users', [UserController::class, 'index'])
            ->name('users.index');

        // Sub Category CRUD
        Route::resource('sub-categories', SubCategoryController::class)
            ->except(['show']);

        // Category CRUD
        Route::resource('categories', CategoryController::class)
            ->except(['show']);

        // Product CRUD
        Route::resource('products', ProductController::class)
            ->except(['show']);
    });

require __DIR__.'/auth.php';
