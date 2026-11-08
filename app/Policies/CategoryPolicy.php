<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

class CategoryPolicy
{
    private function isMultiTenant(): bool
    {
        return config('operational.mode', 'single_store') === 'multi_tenant';
    }

    private function belongsToCurrentTenant(Category $category): bool
    {
        if (! $this->isMultiTenant()) {
            return true;
        }

        $tenantId = app(TenantContext::class)->current()?->getKey();

        return $tenantId !== null && (int) $category->tenant_id === (int) $tenantId;
    }

    private function canView(User $user): bool
    {
        return $this->isMultiTenant()
            ? $user->hasTenantPermission('products.view')
            : $user->hasAnyRole('admin', 'staff');
    }

    private function canManage(User $user): bool
    {
        return $this->isMultiTenant()
            ? $user->hasTenantPermission('products.manage')
            : $user->hasAnyRole('admin');
    }

    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, Category $category): bool
    {
        return $this->belongsToCurrentTenant($category) && $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->belongsToCurrentTenant($category) && $this->canManage($user);
    }

    public function delete(User $user, Category $category): bool
    {
        return $this->belongsToCurrentTenant($category) && $this->canManage($user);
    }
}