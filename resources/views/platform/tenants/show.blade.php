@extends('layouts.platform')

@section('title', 'Tenant Detail')

@section('content')
@php
    $store = $tenant->store;
    $domains = $store?->verifiedDomains ?? collect();
    $primaryDomain = $domains->first();
@endphp

<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">Tenant Detail</h3>
            <div class="nk-block-des text-soft">
                <p>Overview and management information for {{ $tenant->name }}.</p>
            </div>
        </div>
        <div class="nk-block-head-content">
            <a href="{{ route('platform.tenants.index') }}" class="btn btn-outline-light">
                <em class="icon ni ni-arrow-left"></em>
                <span>Go back</span>
            </a>
        </div>
    </div>
</div>

{{-- Overview --}}
<div class="row g-gs mb-4">
    @foreach ([
        ['label' => 'Active Members', 'value' => $tenant->active_memberships_count, 'icon' => 'users'],
        ['label' => 'Pending Invitations', 'value' => $tenant->pending_invitations_count, 'icon' => 'mail'],
        ['label' => 'Total Invoices', 'value' => $tenant->invoices_count, 'icon' => 'file-text'],
    ] as $stat)
        <div class="col-12 col-sm-6 col-xl-4">
            <div class="card card-bordered">
                <div class="card-inner">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-soft">{{ $stat['label'] }}</div>
                            <div class="amount">{{ number_format($stat['value']) }}</div>
                        </div>
                        <em class="icon ni ni-{{ $stat['icon'] }} fs-2 text-primary"></em>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>

{{-- Tenant and Store Information --}}
<div class="row g-gs mb-4">
    <div class="col-12 col-xl-6">
        <div class="card card-bordered h-100">
            <div class="card-inner">
                <div class="card-title-group mb-3">
                    <div class="card-title">
                        <h6 class="title">Tenant Information</h6>
                    </div>
                    <div class="card-tools">
                        @if ($tenant->status === 'active')
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">{{ ucfirst($tenant->status) }}</span>
                        @endif
                    </div>
                </div>

                <div class="row gy-3">
                    <div class="col-12">
                        <div class="text-soft small">Tenant Name</div>
                        <div class="fw-medium">{{ $tenant->name }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-soft small">Slug</div>
                        <div>{{ $tenant->slug }}</div>
                    </div>
                    <div class="col-12">
                        <div class="text-soft small">Created By</div>
                        @if ($tenant->creator)
                            <div>{{ $tenant->creator->name }}</div>
                            <div class="text-soft small">{{ $tenant->creator->email }}</div>
                        @else
                            <div class="text-soft">Unknown creator</div>
                        @endif
                    </div>
                    <div class="col-12">
                        <div class="text-soft small">Created At</div>
                        <div>{{ $tenant->created_at?->format('d M Y H:i') ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-xl-6">
        <div class="card card-bordered h-100">
            <div class="card-inner">
                <div class="card-title-group mb-3">
                    <div class="card-title">
                        <h6 class="title">Store Information</h6>
                    </div>
                    @if ($store)
                        <span class="badge {{ $store->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                            {{ ucfirst($store->status) }}
                        </span>
                    @endif
                </div>

                @if ($store)
                    <div class="row gy-3">
                        <div class="col-12">
                            <div class="text-soft small">Store Name</div>
                            <div class="fw-medium">{{ $store->name }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-soft small">Store Slug</div>
                            <div>{{ $store->slug }}</div>
                        </div>
                        <div class="col-12">
                            <div class="text-soft small">Description</div>
                            <div>{{ $store->description ?: 'No description' }}</div>
                        </div>
                    </div>
                @else
                    <div class="text-soft">This tenant has no store yet.</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- Verified Domains --}}
<div class="card card-bordered mb-4">
    <div class="card-inner">
        <div class="card-title-group mb-3">
            <div class="card-title">
                <h6 class="title">Verified Domains</h6>
            </div>
            <span class="badge bg-light text-dark">{{ $domains->count() }} domains</span>
        </div>

        @forelse ($domains as $domain)
            <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                <div>
                    <div class="fw-medium">{{ $domain->domain }}</div>
                    <div class="text-soft small">
                        {{ ucfirst($domain->type) }}
                        @if ($domain->verified_at)
                            · Verified {{ $domain->verified_at->format('d M Y') }}
                        @endif
                    </div>
                </div>
                <div class="d-flex gap-1">
                    @if ($domain->is_primary)
                        <span class="badge bg-primary">Primary</span>
                    @endif
                    <span class="badge bg-success">Verified</span>
                </div>
            </div>
        @empty
            <div class="text-soft">No verified domain available.</div>
        @endforelse

        @if ($tenant->status === 'active' && $primaryDomain)
            <div class="mt-3">
                <form method="POST" action="{{ route('platform.tenants.enter', $tenant) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        <em class="icon ni ni-signin"></em>
                        <span>Enter Tenant</span>
                    </button>
                </form>
            </div>
        @else
            <div class="text-soft small mt-3">
                Tenant access is unavailable. An active tenant and verified domain are required.
            </div>
        @endif
    </div>
</div>

{{-- Active Memberships --}}
<div class="card card-bordered mb-4">
    <div class="card-inner">
        <div class="card-title-group mb-3">
            <div class="card-title">
                <h6 class="title">Active Memberships</h6>
            </div>
            <span class="badge bg-light text-dark">{{ $tenant->active_memberships_count }} members</span>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped text-nowrap">
                <thead>
                    <tr>
                        <th>Member</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Joined At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tenant->activeMemberships as $membership)
                        <tr>
                            <td>{{ $membership->user?->name ?? 'Unknown user' }}</td>
                            <td>{{ $membership->user?->email ?? '-' }}</td>
                            <td>
                                <span class="badge bg-light text-dark">
                                    {{ ucfirst(str_replace('_', ' ', $membership->role)) }}
                                </span>
                            </td>
                            <td>{{ $membership->accepted_at?->format('d M Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-soft">No active members found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Invitations --}}
<div class="card card-bordered mb-4">
    <div class="card-inner">
        <div class="card-title-group mb-3">
            <div class="card-title">
                <h6 class="title">Invitations</h6>
            </div>
            <span class="badge bg-light text-dark">{{ $tenant->invitations->count() }} invitations</span>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped text-nowrap">
                <thead>
                    <tr>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Invited By</th>
                        <th>Expires At</th>
                        <th>Accepted At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tenant->invitations as $invitation)
                        <tr>
                            <td>{{ $invitation->email }}</td>
                            <td>{{ ucfirst(str_replace('_', ' ', $invitation->role)) }}</td>
                            <td>
                                @php
                                    $statusClass = match ($invitation->status) {
                                        'pending' => 'bg-warning',
                                        'accepted' => 'bg-success',
                                        'expired', 'cancelled', 'revoked' => 'bg-secondary',
                                        default => 'bg-light text-dark',
                                    };
                                @endphp
                                <span class="badge {{ $statusClass }}">
                                    {{ ucfirst($invitation->status) }}
                                </span>
                            </td>
                            <td>{{ $invitation->inviter?->name ?? 'Unknown user' }}</td>
                            <td>{{ $invitation->expires_at?->format('d M Y H:i') ?? '-' }}</td>
                            <td>{{ $invitation->accepted_at?->format('d M Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-soft">No invitations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Billing & Invoices --}}
<div class="card card-bordered mb-4">
    <div class="card-inner">
        <div class="card-title-group mb-3">
            <div class="card-title">
                <h6 class="title">Billing &amp; Invoices</h6>
            </div>
            <span class="badge bg-light text-dark">{{ number_format($tenant->invoices_count) }} invoices</span>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-striped text-nowrap">
                <thead>
                    <tr>
                        <th>Invoice Number</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Issued At</th>
                        <th>Due At</th>
                        <th>Paid At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($invoices as $invoice)
                        <tr>
                            <td>{{ $invoice->invoice_number }}</td>
                            <td>
                                <span class="badge {{ $invoice->status === 'paid' ? 'bg-success' : ($invoice->status === 'open' ? 'bg-warning' : 'bg-secondary') }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td>{{ $invoice->currency }} {{ number_format($invoice->total, 0, ',', '.') }}</td>
                            <td>{{ $invoice->issued_at?->format('d M Y') ?? '-' }}</td>
                            <td>{{ $invoice->due_at?->format('d M Y') ?? '-' }}</td>
                            <td>{{ $invoice->paid_at?->format('d M Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-soft">No invoices found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($invoices->hasPages())
            <div class="mt-3">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
