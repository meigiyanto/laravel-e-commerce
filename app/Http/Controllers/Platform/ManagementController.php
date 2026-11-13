<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreDomain;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;
use App\Notifications\TenantOwnerInvitation;
use App\Services\Tenancy\DomainVerificationService;
use App\Services\Tenancy\OwnerInvitationService;
use App\Services\Tenancy\TenantLifecycleService;
use App\Services\Tenancy\TenantProvisioningService;
// use App\Support\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class ManagementController extends Controller
{
    public function provisionTenant(
        Request $request,
        TenantProvisioningService $service
    ): RedirectResponse {
        $data = $request->validate([
            'tenant_name' => ['required', 'string', 'max:150'],
            'store_name' => ['required', 'string', 'max:150'],
            'owner_email' => ['required', 'email', 'max:255'],
        ]);

        $result = $service->provision(
            $request->user(),
            $data['tenant_name'],
            $data['store_name'],
            $data['owner_email']
        );

        Notification::route('mail', $data['owner_email'])
        ->notify(new TenantOwnerInvitation(
            $result['tenant']->name,
            $result['plain_token']
        ));

        return back()->with('success', 'Tenant dibuat dan undangan owner dikirim.');
    }

    public function acceptInvitation(
        Request $request,
        string $token,
        OwnerInvitationService $service
    ): RedirectResponse {
        $membership = $service->accept($token, $request->user());

        return redirect()
        ->route('tenant.setup.domain.show', $membership->tenant_id)
        ->with('success', 'Undangan diterima. Silakan daftarkan domain toko.');
    }

    public function showDomainSetup(Request $request, Tenant $tenant) {
        $this->authorizeTenantSetup($request, $tenant);

        $store = Store::query()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

        $domains = $store->domains()
        ->with('verificationChallenge')
        ->orderByDesc('is_primary')
        ->latest('id')
        ->get();

        return view('tenant.setup.domain', compact('tenant', 'store', 'domains'));
    }

    public function createDomain(
        Request $request,
        Tenant $tenant,
        DomainVerificationService $service
    ): RedirectResponse {
        $this->authorizeTenantSetup($request, $tenant);

        $data = $request->validate([
            'domain' => ['required', 'string', 'max:253'],
        ]);

        $store = Store::query()
        ->where('tenant_id', $tenant->id)
        ->firstOrFail();

        $challenge = $service->create($store, $data['domain']);

        return redirect()
        ->route('tenant.setup.domain.show', $tenant)
        ->with('success', 'Instruksi DNS TXT berhasil dibuat.')
        ->with('verification_record', [
            'name' => $challenge->record_name,
            'type' => 'TXT',
            'value' => $challenge->record_value,
            'expires_at' => $challenge->expires_at->toDateTimeString(),
        ]);
    }

    public function verifyDomain(
        Request $request,
        Tenant $tenant,
        StoreDomain $domain,
        DomainVerificationService $service
    ): RedirectResponse {
        $this->authorizeTenantSetup($request, $tenant);

        $store = Store::query()
                    ->where('tenant_id', $tenant->id)
                    ->firstOrFail();

        abort_unless((int) $domain->store_id === (int) $store->id, 404);

        $challenge = $domain->verificationChallenge;

        abort_unless($challenge, 404);

        if (! $service->verify($challenge)) {
            return back()->withErrors([
                'domain' => 'DNS TXT record belum ditemukan atau sudah kedaluwarsa.',
            ]);
        }

        return redirect()->route('tenant.setup.domain.show', $tenant)->with('success', 'Domain berhasil diverifikasi. Tunggu aktivasi dari platform administrator.');
    }

    private function authorizeTenantSetup(Request $request, Tenant $tenant): void
    {
        abort_unless(
            in_array($tenant->status, ['pending', 'active'], true),
            404
        );

        $membership = $request->user()
        ->activeTenantMembership((int) $tenant->id);

        abort_unless(
            $membership && in_array($membership->role, ['owner', 'admin'], true),
            403
        );
    }
    
    public function changeTenantStatus(
        Request $request,
        Tenant $tenant,
        TenantLifecycleService $service
    ): RedirectResponse {
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'suspended'])],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['status'] === 'suspended') {
            $service->suspend(
                $tenant,
                $request->user(),
                $data['reason'] ?? ''
            );
        } else {
            $service->activate($tenant, $request->user());
        }

        return back()->with('success', 'Status tenant berhasil diperbarui.');
    }

    public function plans() {
        return response()->json([
            'plans' => SubscriptionPlan::query()
            ->where('is_active', true)
            ->with('features')
            ->orderBy('sort_order')
            ->get(),
        ]);
    }
}