@extends('layouts.platform')

@section('title', 'Audit Log Details')

@section('content')
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">Audit Log Details</h3>
            <p class="text-soft">Detail aktivitas administratif platform.</p>
        </div>
        <div>
            <a href="{{ route('platform.audit-logs.index') }}" class="btn btn-outline-secondary">
                Back to Audit Logs
            </a>
        </div>
    </div>
</div>

<div class="card card-bordered mb-4">
    <div class="card-inner">
        <h5 class="mb-3">Event Information</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <tbody>
                    <tr><th style="width: 220px;">Log ID</th><td>{{ $log->id }}</td></tr>
                    <tr><th>Action</th><td>{{ $actions[$log->action] ?? $log->action }}</td></tr>
                    <tr><th>Timestamp</th><td>{{ $log->created_at?->format('d M Y H:i:s') ?? '-' }}</td></tr>
                    <tr><th>Actor</th><td>{{ $log->user?->name ?? 'Deleted user' }} (ID: {{ $log->user_id ?? '-' }})</td></tr>
                    <tr><th>Actor Email</th><td>{{ $log->user?->email ?? '-' }}</td></tr>
                    <tr><th>Tenant</th><td>{{ $log->tenant?->name ?? 'Deleted tenant' }} (ID: {{ $log->tenant_id ?? '-' }})</td></tr>
                    <tr><th>IP Address</th><td>{{ $log->ip_address ?? '-' }}</td></tr>
                    <tr><th>User Agent</th><td>{{ $log->user_agent ?? '-' }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-bordered">
    <div class="card-inner">
        <h5 class="mb-3">Metadata</h5>
        @if (count($metadata))
            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead><tr><th style="width: 220px;">Key</th><th>Value</th></tr></thead>
                    <tbody>
                        @foreach ($metadata as $key => $value)
                            <tr>
                                <th>{{ \Illuminate\Support\Str::headline((string) $key) }}</th>
                                <td>
                                    @if (is_array($value) || is_object($value))
                                        <pre class="mb-0 text-wrap">{{ json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                                    @elseif (is_bool($value))
                                        {{ $value ? 'true' : 'false' }}
                                    @else
                                        {{ $value ?? '-' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="text-soft mb-0">No metadata available.</p>
        @endif
    </div>
</div>
@endsection