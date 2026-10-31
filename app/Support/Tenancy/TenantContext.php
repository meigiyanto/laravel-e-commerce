<?php

namespace app/Support/Tenancy;

class TenantContext
{
    private ?Tenant $tenant = null;

    public function set(Tenant $tenant): void
    {
        $this->tenant = $tenant;
    }

    public function current(): ?Tenant
    {
        return $this->tenant;
    }

    public function require(): Tenant
    {
        return $this->tenant
            ?? throw new LogicException(
                'Tenant context is not initialized.'
            );
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }
}