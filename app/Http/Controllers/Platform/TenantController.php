<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::query()
            ->with(['store.domains' => function ($query) {
                $query->where('verification_status', 'verified');
            }])
            ->latest()
            ->paginate(20);

        return view('platform.tenants.index', compact('tenants'));
    }

    public function enter(Tenant $tenant): RedirectResponse
    {
        abort_unless($tenant->status === 'active', 404);

        $domain = $tenant->store?->domains()
            ->where('verification_status', 'verified')
            ->where('is_primary', true)
            ->first();

        if (! $domain) {
            $domain = $tenant->store?->domains()
                ->where('verification_status', 'verified')
                ->first();
        }

        abort_unless($domain, 409, 'Tenant belum memiliki domain terverifikasi.');

        return redirect()->away('https://' . $domain->domain . '/admin/dashboard');
    }
}