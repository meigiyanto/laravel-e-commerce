@extends('layouts.platform')

@section('title', 'Platform Audit Logs')

@section('content')
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-head-content">
        <h3 class="nk-block-title page-title">Platform Audit Logs</h3>
        <p class="text-soft">Riwayat aktivitas administratif platform.</p>
    </div>
</div>

<div class="card card-bordered mb-4">
    <div class="card-inner">
        <form method="GET" action="{{ route('platform.audit-logs.index') }}">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label" for="action">Action</label>
                    <select name="action" id="action" class="form-select">
                        <option value="">All Actions</option>
                        @foreach ($actions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['action'] ?? '') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="tenant_id">Tenant ID</label>
                    <input type="number" min="1" name="tenant_id" id="tenant_id"
                        class="form-control" value="{{ $filters['tenant_id'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="user_id">Actor ID</label>
                    <input type="number" min="1" name="user_id" id="user_id"
                        class="form-control" value="{{ $filters['user_id'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="from">From</label>
                    <input type="date" name="from" id="from" class="form-control"
                        value="{{ $filters['from'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="to">To</label>
                    <input type="date" name="to" id="to" class="form-control"
                        value="{{ $filters['to'] ?? '' }}">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('platform.audit-logs.index') }}"
                        class="btn btn-outline-secondary">Reset</a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card card-bordered">
    <div class="card-inner">
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Action</th>
                        <th>Actor</th>
                        <th>Tenant</th>
                        <th>Details</th>
                        <th>Request Context</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="text-nowrap">
                                {{ $log->created_at?->format('d M Y H:i:s') }}
                            </td>
                            <td>
                                <span class="badge bg-primary">
                                    {{ $actions[$log->action] ?? $log->action }}
                                </span>
                            </td>
                            <td>
                                @if ($log->user)
                                    <strong>{{ $log->user->name }}</strong>
                                    <div class="text-soft small">{{ $log->user->email }}</div>
                                @else
                                    <span class="text-soft">Deleted user</span>
                                @endif
                                <div class="text-soft small">ID: {{ $log->user_id ?? '-' }}</div>
                            </td>
                            <td>
                                @if ($log->tenant)
                                    <a href="{{ route('platform.tenants.show', $log->tenant) }}">
                                        {{ $log->tenant->name }}
                                    </a>
                                @else
                                    <span class="text-soft">Deleted tenant</span>
                                @endif
                            </td>
                            <td>
                                @php($metadata = $log->metadata ?? [])
                                @switch($log->action)
                                    @case('tenant.provisioned')
                                        <div>Store ID: {{ $metadata['store_id'] ?? '-' }}</div>
                                        <div>Tenant: {{ $metadata['tenant_name'] ?? '-' }}</div>
                                        <div>Store: {{ $metadata['store_name'] ?? '-' }}</div>
                                        @break
                                    @case('tenant.status_changed')
                                        <div>
                                            {{ ucfirst($metadata['from'] ?? '-') }}
                                            &rarr;
                                            {{ ucfirst($metadata['to'] ?? '-') }}
                                        </div>
                                        @break
                                    @case('tenant.access_requested')
                                    @case('tenant.access_granted')
                                        <div>Domain: {{ $metadata['domain'] ?? '-' }}</div>
                                        @if ($log->action === 'tenant.access_granted')
                                            <div>Access Token ID: {{ $metadata['access_token_id'] ?? '-' }}</div>
                                        @endif
                                        @break
                                    @default
                                        <span class="text-soft">No details available</span>
                                @endswitch
                            </td>
                            <td>
                                <div>IP: {{ $log->ip_address ?? '-' }}</div>
                                <div class="text-soft small" style="max-width: 240px; overflow-wrap: anywhere;">
                                    {{ $log->user_agent ?? '-' }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-soft">
                                No audit logs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection