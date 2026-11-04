<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

class InventoryPolicy
{
    private function belongsToCurrentTenant(Product $product): bool
    {
        $tenantId = app(TenantContext::class)->current()?->getKey();

        return $tenantId !== null
            && (int) $product->tenant_id === (int) $tenantId;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasTenantPermission('inventory.view');
    }

    public function update(User $user, Product $product): bool
    {
        return $this->belongsToCurrentTenant($product)
            && $user->hasTenantPermission('inventory.manage');
    }
}