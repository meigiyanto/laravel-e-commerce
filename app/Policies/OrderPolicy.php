<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

class OrderPolicy
{
    private function belongsToCurrentTenant(Order $order): bool
    {
        $tenantId = app(TenantContext::class)->current()?->getKey();

        return $tenantId !== null
            && (int) $order->tenant_id === (int) $tenantId;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasTenantPermission('orders.view');
    }

    public function view(User $user, Order $order): bool
    {
        return $this->belongsToCurrentTenant($order)
            && $user->hasTenantPermission('orders.view');
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $this->belongsToCurrentTenant($order)
            && $user->hasTenantPermission('orders.manage');
    }
}