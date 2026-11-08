<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

class OrderPolicy
{
    private function isMultiTenant(): bool
    {
        return config('operational.mode', 'single_store') === 'multi_tenant';
    }

    private function belongsToCurrentTenant(Order $order): bool
    {
        if (! $this->isMultiTenant()) {
            return $order->tenant_id === null;
        }

        $tenantId = app(TenantContext::class)->current()?->getKey();

        return $tenantId !== null && (int) $order->tenant_id === (int) $tenantId;
    }

    private function canView(User $user): bool
    {
        return $this->isMultiTenant()
            ? $user->hasTenantPermission('orders.view')
            : $user->hasAnyRole('admin', 'staff');
    }

    private function canManage(User $user): bool
    {
        return $this->isMultiTenant()
            ? $user->hasTenantPermission('orders.manage')
            : $user->hasAnyRole('admin');
    }

    public function viewAny(User $user): bool
    {
        return $this->canView($user);
    }

    public function view(User $user, Order $order): bool
    {
        return $this->belongsToCurrentTenant($order) && $this->canView($user);
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $this->belongsToCurrentTenant($order) && $this->canManage($user);
    }
}
