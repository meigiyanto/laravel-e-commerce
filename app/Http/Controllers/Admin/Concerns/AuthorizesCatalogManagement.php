<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Support\Tenancy\TenantContext;

trait AuthorizesCatalogManagement
{
    protected function authorizeCatalogView(): void
    {
        $user = request()->user();

        abort_unless($user, 403);

        if (config('operational.mode', 'single_store') === 'multi_tenant') {
            abort_unless(
                app(TenantContext::class)->current()
                && $user->hasTenantPermission('products.view'),
                403
            );

            return;
        }

        abort_unless($user->hasAnyRole('admin', 'staff'), 403);
    }

    protected function authorizeCatalogManagement(): void
    {
        $user = request()->user();

        abort_unless($user, 403);

        if (config('operational.mode', 'single_store') === 'multi_tenant') {
            abort_unless(
                app(TenantContext::class)->current()
                && $user->hasTenantPermission('products.manage'),
                403
            );

            return;
        }

        abort_unless($user->hasAnyRole('admin'), 403);
    }
}
