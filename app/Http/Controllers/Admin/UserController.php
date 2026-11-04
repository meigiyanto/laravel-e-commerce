<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /** Display users and their assigned roles. */
    public function index(Request $request): View
    {
        $tenantId = (int) app(TenantContext::class)->require()->getKey();
        $search = trim((string) $request->query('search', ''));
        $role = (string) $request->query('role', '');

        $customerIds = Order::query()
        ->where('tenant_id', $tenantId)
        ->whereNotNull('user_id')
        ->select('user_id')
        ->distinct();

        $users = User::query()
        ->where(function ($query) use ($tenantId, $customerIds) {
            $query->whereHas('tenantMemberships', function ($membership) use ($tenantId) {
                $membership->where('tenant_id', $tenantId)
                ->where('status', 'active');
            })->orWhereIn('id', $customerIds);
        })
        ->when($search !== '', function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%");
            });
        })
        ->when(
            in_array($role, [User::ROLE_ADMIN, User::ROLE_STAFF], true),
            fn ($query) => $query->whereHas('tenantMemberships', function ($membership) use ($tenantId, $role) {
                $membership->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('role', $role);
            })
        )
        ->when(
            $role === User::ROLE_CUSTOMER,
            fn ($query) => $query->whereIn('id', $customerIds)
        )
        ->orderBy('name')
        ->paginate(15)
        ->withQueryString();

        $roleCounts = [
            User::ROLE_ADMIN => User::whereHas('tenantMemberships', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('role', User::ROLE_ADMIN);
            })->count(),

            User::ROLE_STAFF => User::whereHas('tenantMemberships', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('role', User::ROLE_STAFF);
            })->count(),

            User::ROLE_CUSTOMER => User::whereIn('id', $customerIds)->count(),
        ];

        return view('admin.users.index', compact(
            'users',
            'search',
            'role',
            'roleCounts'
        ));
    }

    /** Change a user's role. This endpoint is only registered inside the admin-only route group. */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::in([
                User::ROLE_ADMIN,
                User::ROLE_STAFF,
                User::ROLE_CUSTOMER,
            ])],
        ]);

        $actor = $request->user();

        if (! $actor || ! $actor->isAdmin()) {
            abort(403);
        }

        if ($actor->is($user) && $validated['role'] !== User::ROLE_ADMIN) {
            return back()->with('error', 'Anda tidak dapat mengubah role akun sendiri.');
        }

        if ($user->isAdmin() && $validated['role'] !== User::ROLE_ADMIN) {
            $adminCount = User::where('role', User::ROLE_ADMIN)->count();

            if ($adminCount <= 1) {
                return back()->with('error', 'Role Admin terakhir tidak dapat diturunkan. Tetapkan Admin lain terlebih dahulu.');
            }
        }

        if ($user->role === $validated['role']) {
            return back()->with('success', 'Role pengguna tidak berubah.');
        }

        $user->role = $validated['role'];
        $user->save();

        return back()->with('success', "Role {$user->name} berhasil diubah menjadi ".strtoupper($validated['role']).'.');
    }
}