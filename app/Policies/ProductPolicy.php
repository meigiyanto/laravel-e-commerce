<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

class ProductPolicy
{
    private function isMultiTenant(): bool
    {
        return config('operational.mode', 'single_store') === 'multi_tenant';
    }

    private function belongsToCurrentTenant(Product $product): bool
    {
        if (! $this->isMultiTenant()) {
            return $product->tenant_id === null;
        }

        $tenantId = app(TenantContext::class)->current()?->getKey();

        return $tenantId !== null && (int) $product->tenant_id === (int) $tenantId;
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

    private function canManageInventory(User $user): bool
    {
        return $this->isMultiTenant()
        ? $user->hasTenantPermission('inventory.manage')
        : $user->hasAnyRole('admin');
    }

    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function viewAnyInventory(User $user): bool
    {
        return $this->isMultiTenant()
        ? $user->hasTenantPermission('inventory.view')
        : $user->hasAnyRole('admin', 'staff');
    }

    public function view(User $user, Product $product): bool
    {
        return $this->belongsToCurrentTenant($product) && $this->canView($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Product $product): bool
    {
        return $this->belongsToCurrentTenant($product) && $this->canManage($user);
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->belongsToCurrentTenant($product) && $this->canManage($user);
    }

    public function updateStock(User $user, Product $product): bool
    {
        return $this->belongsToCurrentTenant($product) && $this->canManageInventory($user);
    }

    public function adjustStock(User $user, Product $product): bool
    {
        return $this->belongsToCurrentTenant($product) && $this->canManageInventory($user);
    }
}