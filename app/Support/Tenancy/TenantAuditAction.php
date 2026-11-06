<?php

namespace App\Support\Tenancy;

final class TenantAuditAction
{
    public const DOMAIN_CREATED = 'domain.created';
    public const DOMAIN_VERIFIED = 'domain.verified';

    public const MEMBERSHIP_ROLE_CHANGED = 'membership.role_changed';
    public const MEMBERSHIP_DEACTIVATED = 'membership.deactivated';

    public const INVENTORY_STOCK_UPDATED = 'inventory.stock_updated';
    public const INVENTORY_STOCK_ADJUSTED = 'inventory.stock_adjusted';

    public const ALL = [
        self::DOMAIN_CREATED,
        self::DOMAIN_VERIFIED,
        self::MEMBERSHIP_ROLE_CHANGED,
        self::MEMBERSHIP_DEACTIVATED,
        self::INVENTORY_STOCK_UPDATED,
        self::INVENTORY_STOCK_ADJUSTED,
    ];
}