<?php

namespace App\Http\Middleware;

use App\Models\StoreDomain;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantFromDomain
{
    private function isPlatformOrCallbackPath(string $path): bool
    {
        if (in_array($path, [
            'stripe/webhook',
            'payment/midtrans/notification',
        ], true)) {
            return true;
        }

        if ($path === 'tenant/invitations' || str_starts_with($path, 'tenant/invitations/')) {
            return true;
        }

        if ($path === 'platform' || str_starts_with($path, 'platform/')) {
            return preg_match(
                '#^platform/tenant-access/[^/]+$#',
                $path
            ) !== 1;
        }
        
        if ($path === 'tenant/setup' || str_starts_with($path, 'tenant/setup/')) {
            return true;
        }

        return false;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $context = app(TenantContext::class);
        $context->clear();

        if (config('operational.mode', 'single_store') !== 'multi_tenant') {
            return $next($request);
        }

        try {
            $path = trim($request->path(), '/');

            if ($this->isPlatformOrCallbackPath($path)) {
                return $next($request);
            }

            $host = strtolower(rtrim($request->getHost(), '.'));

            $storeDomain = StoreDomain::query()
            ->with('store.tenant')
            ->whereRaw('LOWER(domain) = ?', [$host])
            ->where('verification_status', 'verified')
            ->first();

            $store = $storeDomain?->store;
            $tenant = $store?->tenant;

            if (
                ! $tenant && in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            ) {
                $tenant = Tenant::query()
                ->where('slug', 'default')
                ->where('status', 'active')
                ->first();
            } else {
                abort_unless($store && $store->status === 'active', 404);
            }

            abort_unless($tenant && $tenant->status === 'active', 404);

            return $context->run(
                $tenant,
                fn () => $next($request)
            );
        } finally {
            $context->clear();
        }
    }
}