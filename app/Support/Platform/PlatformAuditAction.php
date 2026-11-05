<?php

namespace App\Support\Platform;

final class PlatformAuditAction
{
    public const TENANT_PROVISIONED = 'tenant.provisioned';
    public const TENANT_STATUS_CHANGED = 'tenant.status_changed';
    public const TENANT_ACCESS_REQUESTED = 'tenant.access_requested';
    public const TENANT_ACCESS_GRANTED = 'tenant.access_granted';

    public const ALL = [
        self::TENANT_PROVISIONED,
        self::TENANT_STATUS_CHANGED,
        self::TENANT_ACCESS_REQUESTED,
        self::TENANT_ACCESS_GRANTED,
    ];
}
