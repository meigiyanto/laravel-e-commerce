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

        $tenant = $storeDomain?->store?->tenant;
        if (! $tenant && in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            $tenant = Tenant::query()->where('slug', 'default')->where('status', 'active')->first();
        }

        if (! $tenant || $tenant->status !== 'active') {
            abort(404);
        }

        app(TenantContext::class)->set($tenant);
        return $next($request);
    }

    private function isPlatformOrCallbackPath(string $path): bool
    {
        return $path === 'up'
            || $path === 'stripe/webhook'
            || $path === 'payment/midtrans/notification'
            || str_starts_with($path, 'marketplace')
            || str_starts_with($path, 'payment/');
    }
}
