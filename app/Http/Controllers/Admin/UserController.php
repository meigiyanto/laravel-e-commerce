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
use App\Models\TenantMembership;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    private function isMultiTenant(): bool
    {
        return config('operational.mode', 'single_store') === 'multi_tenant';
    }
    
    /** Display users and their assigned roles. */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', TenantMembership::class);

        if (! $this->isMultiTenant()) {
            $search = trim((string) $request->query('search', ''));
            $role = (string) $request->query('role', '');
        
            $users = User::query()
                ->whereIn('role', [
                    User::ROLE_ADMIN,
                    User::ROLE_STAFF,
                    User::ROLE_CUSTOMER,
                ])
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                })
                ->when(
                    in_array($role, [
                        User::ROLE_ADMIN,
                        User::ROLE_STAFF,
                        User::ROLE_CUSTOMER,
                    ], true),
                    fn ($query) => $query->where('role', $role)
                )
                ->orderBy('name')
                ->paginate(15)
                ->withQueryString();
        
            $roleCounts = [
                User::ROLE_ADMIN => User::where('role', User::ROLE_ADMIN)->count(),
                User::ROLE_STAFF => User::where('role', User::ROLE_STAFF)->count(),
                User::ROLE_CUSTOMER => User::where('role', User::ROLE_CUSTOMER)->count(),
            ];
        
            return view('admin.users.index', compact(
                'users',
                'search',
                'role',
                'roleCounts'
            ));
        }
        
        $tenantId = (int) app(TenantContext::class)->require()->getKey();
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
        $allowedRoles = $this->isMultiTenant()
            ? [
                TenantMembership::ROLE_ADMIN,
                TenantMembership::ROLE_PRODUCT_MANAGER,
                TenantMembership::ROLE_INVENTORY_MANAGER,
                TenantMembership::ROLE_ORDER_MANAGER,
                TenantMembership::ROLE_STAFF,
                User::ROLE_CUSTOMER,
            ]
            : [
                User::ROLE_ADMIN,
                User::ROLE_STAFF,
                User::ROLE_CUSTOMER,
            ];
        
        $validated = $request->validate([
            'role' => ['required', Rule::in($allowedRoles)],
        ]);
        
        if (! $this->isMultiTenant()) {
            abort_unless($request->user()->hasAnyRole(User::ROLE_ADMIN), 403);
            abort_if($user->isPlatformAdmin(), 403);
            abort_if($request->user()->is($user), 403);
        
            $user->update([
                'role' => $validated['role'],
            ]);
        
            return back()->with(
                'success',
                "Role {$user->name} berhasil diperbarui."
            );
        }
        
        $tenant = app(TenantContext::class)->require();
        $tenantId = (int) $tenant->id;
        $role = $validated['role'];

        $membership = TenantMembership::query()
                    ->where('tenant_id', $tenantId)
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->first();

        if ($role === User::ROLE_CUSTOMER) {
            if (! $membership) {
                return back()->with('success', 'Pengguna tidak memiliki membership aktif.');
            }

            Gate::authorize('delete', $membership);

            $membership->update([
                'status' => 'inactive',
            ]);

            return back()->with('success', 'Membership pengguna berhasil dinonaktifkan.');
        }

        Gate::authorize('assignRole', [
            TenantMembership::class,
            $user,
            $role,
        ]);

        TenantMembership::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
            ],
            [
                'role' => $role,
                'status' => 'active',
                'accepted_at' => now(),
            ]
        );

        return back()->with(
            'success',
            "Role membership {$user->name} berhasil diubah."
        );
    }
}