<?php

namespace App\Providers;

use App\Support\Tenancy\TenantContext;
use App\View\Composers\StorefrontComposer;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        Paginator::useBootstrapFive();

        View::composer(
            'layouts.storefront',
            StorefrontComposer::class
        );
    }
}