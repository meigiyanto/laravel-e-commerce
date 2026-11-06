<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformAccessToken;
use App\Services\Platform\PlatformAuditLogger;
use App\Support\Platform\PlatformAuditAction;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::query()
        ->with(['store.verifiedDomains' => function ($query) {
            $query->orderByDesc('is_primary')->orderBy('id');
        }])
        ->latest()
        ->paginate(20);

        return view('platform.tenants.index', compact('tenants'));
    }

    public function enter(
        Request $request,
        Tenant $tenant,
        PlatformAuditLogger $auditLogger
    ): RedirectResponse 
    {
        abort_unless($tenant->status === 'active', 404);

        $domain = $tenant->store?->verifiedDomains()->orderByDesc('is_primary')->orderBy('id')->first();
        abort_unless($domain, 409, 'Tenant belum memiliki domain terverifikasi.');

        // Domain harus berupa hostname, bukan URL atau path bebas.
        abort_unless(filter_var($domain->domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME), 409, 'Domain tenant tidak valid.');

        $plainToken = Str::random(64);

        DB::transaction(function () use ($request, $tenant, $plainToken, $domain, $auditLogger) {
            PlatformAccessToken::create([
                'token_hash' => hash('sha256', $plainToken),
                'user_id' => $request->user()->id,
                'tenant_id' => $tenant->id,
                'expires_at' => now()->addMinutes(2),
            ]);
            
            $action = $auditLogger->record(
                actor: $request->user(),
                action: PlatformAuditAction::TENANT_ACCESS_REQUESTED,
                tenant: $tenant,
                metadata: ['domain' => $domain->domain],
            );
        });

        $scheme = app()->environment('local') ? 'http' : 'https';

        return redirect()->away($scheme.'://'.$domain->domain.'/platform/tenant-access/'.$plainToken);
    }

    public function consume(
        Request $request,
        string $token,
        PlatformAuditLogger $auditLogger
    ): RedirectResponse
    {
        // Tenant harus berasal dari domain terverifikasi yang sedang diakses.
        $tenant = app(TenantContext::class)->require();

        $user = DB::transaction(function () use ($request, $token, $tenant, $auditLogger) {
            $accessToken = PlatformAccessToken::query()
                                ->where('token_hash', hash('sha256', $token))
                                ->where('tenant_id', $tenant->id)
                                ->whereNull('consumed_at')
                                ->where('expires_at', '>', now())
                                ->first();

            abort_unless($accessToken, 404);

            // Update bersyarat untuk mencegah pemakaian token dua kali.
            $consumed = PlatformAccessToken::query()
                            ->whereKey($accessToken->id)
                            ->whereNull('consumed_at')
                            ->where('expires_at', '>', now())
                            ->update(['consumed_at' => now()]);

            abort_unless($consumed === 1, 404);

            $user = User::query()->find($accessToken->user_id);

            abort_unless($user && $user->isPlatformAdmin(), 404);
            
            $action = $auditLogger->record(
                actor: $user,
                action: PlatformAuditAction::TENANT_ACCESS_GRANTED,
                tenant: $tenant,
                metadata: [
                    'access_token_id' => $accessToken->id,
                    'domain' => $request->getHost(),
                ],
            );
            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        $request->session()->put('platform_tenant_access', (int) $tenant->id);

        return redirect()->route('admin.dashboard');
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load([
            'creator:id,name,email',
            'store.verifiedDomains' => function ($query) {
                $query->orderByDesc('is_primary')->orderBy('id');
            },
        ]);

        $tenant->loadCount([
            'memberships as active_memberships_count' => function ($query) {
                $query->where('status', 'active');
            },
            'invitations as pending_invitations_count' => function ($query) {
                $query->where('status', 'pending')
                ->where('expires_at', '>', now());
            },
            'invitations',
            'invoices',
        ]);

        $memberships = $tenant->activeMemberships()
                        ->with('user:id,name,email')
                        ->latest()
                        ->paginate(10, ['*'], 'memberships_page');

        $invitations = $tenant->invitations()
        ->select([
            'id',
            'tenant_id',
            'invited_by',
            'email',
            'role',
            'status',
            'expires_at',
            'accepted_at',
            'created_at',
        ])
        ->with('inviter:id,name,email')
        ->latest()
        ->paginate(10, ['*'], 'invitations_page');

        $invoices = $tenant->invoices()
        ->select([
            'id',
            'tenant_id',
            'invoice_number',
            'status',
            'currency',
            'total',
            'issued_at',
            'due_at',
            'paid_at',
        ])
        ->latest('issued_at')
        ->latest('id')
        ->paginate(10, ['*'], 'invoices_page');

        return view('platform.tenants.show', compact(
            'tenant',
            'memberships',
            'invitations',
            'invoices'
        ));
    }
}