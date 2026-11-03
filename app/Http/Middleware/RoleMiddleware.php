<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if ($user->isPlatformAdmin()) {
            return $next($request);
        }

        if ($roles === [] || ! $user->hasAnyRole(...$roles)) {
            abort(403, 'You are not authorized to access this resource.');
        }

        $tenant = app(TenantContext::class)->current();

        abort_unless($tenant, 404);

        $hasActiveMembership = $user->tenantMemberships()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->exists();

        abort_unless($hasActiveMembership, 403);

        return $next($request);
    }
}
