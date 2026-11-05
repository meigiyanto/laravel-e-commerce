<?php

namespace App\Policies;

use App\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

class TenantMembershipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasTenantPermission('users.manage');
    }

    public function assignRole(User $actor, User $target, string $role): bool
    {
        $tenantId = app(TenantContext::class)->current()?->getKey();

        if (! $tenantId || $actor->is($target)) {
            return false;
        }

        $actorMembership = $actor->activeTenantMembership((int) $tenantId);

        if (! $actorMembership) {
            return false;
        }

        if (! in_array($role, TenantMembership::ROLES, true)) {
            return false;
        }

        // Ownership tidak boleh diberikan melalui endpoint role biasa.
        if ($role === TenantMembership::ROLE_OWNER) {
            return false;
        }

        $targetMembership = $target->tenantMemberships()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->first();

        // Owner yang sudah ada tidak dapat diubah melalui endpoint ini.
        if ($targetMembership?->isOwner()) {
            return false;
        }

        if ($actorMembership->role === TenantMembership::ROLE_OWNER) {
            return true;
        }

        // Admin tenant tidak boleh mengangkat admin atau owner baru.
        if ($actorMembership->role === TenantMembership::ROLE_ADMIN) {
            return in_array($role, [
                TenantMembership::ROLE_PRODUCT_MANAGER,
                TenantMembership::ROLE_INVENTORY_MANAGER,
                TenantMembership::ROLE_ORDER_MANAGER,
                TenantMembership::ROLE_STAFF,
            ], true);
        }

        return false;
    }

    public function delete(User $actor, TenantMembership $membership): bool
    {
        $tenantId = app(TenantContext::class)->current()?->getKey();

        if (
            ! $tenantId
            || (int) $membership->tenant_id !== (int) $tenantId
            || (int) $membership->user_id === (int) $actor->id
            || $membership->isOwner()
        ) {
            return false;
        }

        $actorMembership = $actor->activeTenantMembership((int) $tenantId);

        if (! $actorMembership) {
            return false;
        }

        if ($actorMembership->role === TenantMembership::ROLE_OWNER) {
            return true;
        }

        return $actorMembership->role === TenantMembership::ROLE_ADMIN
            && ! in_array($membership->role, [
                TenantMembership::ROLE_OWNER,
                TenantMembership::ROLE_ADMIN,
            ], true);
    }
}