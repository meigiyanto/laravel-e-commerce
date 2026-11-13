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
    
        $tenant = Tenant::query()
            ->whereKey($this->tenantId)
            ->where('status', 'active')
            ->firstOrFail();
    
        return $context->run(
            $tenant,
            fn () => $next($job)
        );
    }
}