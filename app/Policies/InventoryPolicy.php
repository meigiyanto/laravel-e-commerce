<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

class InventoryPolicy
{
    private function isMultiTenant(): bool
    {
        return config('operational.mode', 'single_store') === 'multi_tenant';
    }

    private function belongsToCurrentTenant(Product $product): bool
    {
        if (! $this->isMultiTenant()) {
            return true;
        }

        $tenantId = app(TenantContext::class)->current()?->getKey();

        return $tenantId !== null && (int) $product->tenant_id === (int) $tenantId;
    }

    public function viewAny(User $user): bool
    {
        return $this->isMultiTenant()
            ? $user->hasTenantPermission('inventory.view')
            : $user->hasAnyRole('admin', 'staff');
    }

    public function update(User $user, Product $product): bool
    {
        $canManage = $this->isMultiTenant()
            ? $user->hasTenantPermission('inventory.manage')
            : $user->hasAnyRole('admin');

        return $this->belongsToCurrentTenant($product) && $canManage;
    }
}