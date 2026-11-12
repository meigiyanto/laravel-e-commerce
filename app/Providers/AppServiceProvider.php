<?php

namespace App\Providers;

use App\Support\Tenancy\TenantContext;
use App\View\Composers\StorefrontComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use App\Models\Inventory;
use App\Policies\InventoryPolicy;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(
            TenantContext::class,
            fn () => new TenantContext()
        );
    }

    public function boot(): void
    {
        Gate::policy(Inventory::class, InventoryPolicy::class);
        
        Paginator::useBootstrapFive();

        View::composer(
            'layouts.storefront',
            StorefrontComposer::class
        );
    }
}