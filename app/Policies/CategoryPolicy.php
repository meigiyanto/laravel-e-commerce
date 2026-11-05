<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

class CategoryPolicy
{
    private function belongsToCurrentTenant(Category $category): bool
    {
        $tenantId = app(TenantContext::class)->current()?->getKey();

        return $tenantId !== null
            && (int) $category->tenant_id === (int) $tenantId;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasTenantPermission('products.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $this->belongsToCurrentTenant($category)
            && $user->hasTenantPermission('products.view');
    }

    public function create(User $user): bool
    {
        return $user->hasTenantPermission('products.manage');
    }

    public function update(User $user, Category $category): bool
    {
        return $this->belongsToCurrentTenant($category)
            && $user->hasTenantPermission('products.manage');
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->belongsToCurrentTenant($category)
            && $user->hasTenantPermission('products.manage');
    }
}