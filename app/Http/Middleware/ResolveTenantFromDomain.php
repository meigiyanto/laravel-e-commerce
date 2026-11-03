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
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(TenantContext::class);
        $context->clear();

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

            if (! $tenant && in_array($host, [
                'localhost',
                '127.0.0.1',
                '::1',
            ], true)) {
                $tenant = Tenant::query()
                    ->where('slug', 'default')
                    ->where('status', 'active')
                    ->first();
            } else {
                abort_unless(
                    $store && $store->status === 'active',
                    404
                );
            }

            abort_unless($tenant && $tenant->status === 'active', 404);

            $context->set($tenant);

            return $next($request);
        } finally {
            $context->clear();
        }
    }

    private function isPlatformOrCallbackPath(string $path): bool
    {
        return $path === 'stripe/webhook'
            || $path === 'payment/midtrans/notification'
            || $path === 'platform'
            || str_starts_with($path, 'platform/');
    }
}
