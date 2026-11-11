<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Closure;
use LogicException;

class TenantContext
{
    private ?Tenant $tenant = null;

    public function current(): ?Tenant
    {
        return $this->tenant;
    }

    public function require(): Tenant
    {
        return $this->tenant ?? throw new LogicException(
            'Tenant context is not initialized.'
        );
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function run(Tenant $tenant, Closure $callback): mixed
    {
        if ($tenant->status !== 'active') {
            throw new LogicException(
                'Tenant context requires an active tenant.'
            );
        }

        $previous = $this->tenant;
        $this->tenant = $tenant;

        try {
            return $callback($tenant);
        } finally {
            $this->tenant = $previous;
        }
    }

    public function clear(): void
    {
        $this->tenant = null;
    }
}
