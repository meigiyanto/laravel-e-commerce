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

        $tenant = app(TenantContext::class)->current();

        abort_unless($tenant, 404);

        // Platform admin hanya boleh memasuki operasional tenant
        // melalui mekanisme akses tenant yang sudah disediakan.
        if ($user->hasPlatformTenantAccess((int) $tenant->id)) {
            return $next($request);
        }

        // Admin lama yang belum pernah menjadi anggota tenant.
        // Aksesnya dibatasi pada route yang memang menerima role admin.
        $isLegacyAdmin = $user->isAdmin()
            && ! $user->tenantMemberships()->exists()
            && in_array('admin', $roles, true);

        if ($isLegacyAdmin) {
            return $next($request);
        }

        $membership = $user->activeTenantMembership((int) $tenant->id);

        abort_unless(
            $membership,
            403,
            'Tenant membership required.'
        );

        if (
            $roles !== []
            && ! in_array($membership->role, $roles, true)
        ) {
            abort(403, 'Insufficient tenant role.');
        }

        return $next($request);
    }
}