<?php

namespace App\Http\Middleware;

use App\Support\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        // Single Store menggunakan role langsung dari tabel users.
        if (config('operational.mode', 'single_store') === 'single_store') {
            abort_unless(
                $roles === [] || $user->hasAnyRole(...$roles),
                403,
                'Insufficient role.'
            );

            return $next($request);
        }

        // Multi-tenant tetap menggunakan tenant membership.
        $tenant = app(TenantContext::class)->current();

        abort_unless($tenant, 404);

        if ($user->hasPlatformTenantAccess((int) $tenant->id)) {
            return $next($request);
        }

        $membership = $user->activeTenantMembership((int) $tenant->id);

        abort_unless(
            $membership,
            403,
            'Tenant membership required.'
        );

        abort_unless(
            $roles === [] || in_array($membership->role, $roles, true),
            403,
            'Insufficient role.'
        );

        return $next($request);
    }
}
