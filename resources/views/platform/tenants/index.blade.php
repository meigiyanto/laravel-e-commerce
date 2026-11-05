@extends('layouts.platform')

@section('title', 'Manage Tenants')

@section('content')
<div class="nk-block-head">
    <div class="nk-block-head-content">
        <h3 class="nk-block-title page-title">Manage Tenants</h3>
    </div>
</div>
<div class="card card-bordered">
    <div class="card-inner">
        <div class="table-responsive">
            <table class="table table-bordered table-striped text-nowrap">
                <thead>
                    <tr>
                        <th>Tenant</th>
                        <th>Status</th>
                        <th>Verified Domain</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tenants as $tenant)
                        @php
                            $domain = $tenant->store?->domains->first();
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $tenant->name }}</strong>
                                <div class="text-soft">{{ $tenant->slug }}</div>
                            </td>
                            <td>{{ $tenant->status }}</td>
                            <td>{{ $domain?->domain ?? 'No verified domain' }}</td>
                            <td>
                                @if ($tenant->status === 'active' && $domain)
                                    <form method="POST" action="{{ route('platform.tenants.enter', $tenant) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-primary">Enter Tenant</button>
                                    </form>
                                @else
                                    <span class="text-soft">Unavailable</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center">No tenants found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $tenants->links() }}
    </div>
</div>
@endsection