<?php

namespace App\Jobs\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;

class SetTenantContext
{
    public function __construct(
        private readonly int $tenantId
    ) {}

    public function handle(object $job, callable $next): mixed
    {
        $context = app(TenantContext::class);

        try {
            $tenant = Tenant::query()
                ->whereKey($this->tenantId)
                ->where('status', 'active')
                ->firstOrFail();

            $context->set($tenant);

            return $next($job);
        } finally {
            $context->clear();
        }
    }
}
