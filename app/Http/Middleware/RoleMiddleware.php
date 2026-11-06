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

        $tenant = app(TenantContext::class)->current();

        abort_unless($tenant, 404);

        if ($user->hasPlatformTenantAccess((int) $tenant->id)) {
            return $next($request);
        }

        $membership = $user->activeTenantMembership((int) $tenant->id);

        abort_unless($membership, 403, 'Tenant membership required.');

        if ($roles !== [] && ! in_array($membership->role, $roles, true)) {
            abort(403, 'Insufficient tenant role.');
        }

        return $next($request);
    }
}