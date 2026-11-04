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
use App\Support\Tenancy\TenantContext;
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
        $service->accept($token, $request->user());

        return redirect()
            ->route('storefront.home')
            ->with('success', 'Undangan tenant berhasil diterima.');
    }

    public function createDomain(
        Request $request,
        DomainVerificationService $service
    ): RedirectResponse {
        $tenant = app(TenantContext::class)->require();

        abort_unless(
            $request->user()->hasTenantRole('owner', 'admin'),
            403
        );

        $data = $request->validate([
            'domain' => ['required', 'string', 'max:253'],
        ]);

        $store = Store::query()
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $challenge = $service->create($store, $data['domain']);

        return back()->with('verification_record', [
            'name' => $challenge->record_name,
            'type' => 'TXT',
            'value' => $challenge->record_value,
            'expires_at' => $challenge->expires_at->toDateTimeString(),
        ]);
    }

    public function verifyDomain(
        Request $request,
        StoreDomain $domain,
        DomainVerificationService $service
    ): RedirectResponse {
        $tenant = app(TenantContext::class)->require();

        abort_unless(
            $request->user()->hasTenantRole('owner', 'admin'),
            403
        );

        abort_unless(
            (int) $domain->store?->tenant_id === (int) $tenant->id,
            404
        );

        $challenge = $domain->verificationChallenge;

        abort_unless($challenge, 404);

        if (!$service->verify($challenge)) {
            return back()->withErrors([
                'domain' => 'DNS TXT record belum ditemukan atau sudah kedaluwarsa.',
            ]);
        }

        return back()->with('success', 'Domain berhasil diverifikasi.');
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

    public function plans()
    {
        return response()->json([
            'plans' => SubscriptionPlan::query()
                ->where('is_active', true)
                ->with('features')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }
}
