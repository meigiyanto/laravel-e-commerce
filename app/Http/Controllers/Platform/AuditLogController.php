<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformAuditLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'action' => ['nullable', 'string', 'in:tenant.provisioned,tenant.status_changed,tenant.access_requested,tenant.access_granted'],
            'tenant_id' => ['nullable', 'integer', 'exists:tenants,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $logs = PlatformAuditLog::query()
        ->with([
            'user:id,name,email',
            'tenant:id,name,slug',
        ])
        ->when($filters['action'] ?? null, function ($query, $action) {
            $query->where('action', $action);
        })
        ->when($filters['tenant_id'] ?? null, function ($query, $tenantId) {
            $query->where('tenant_id', $tenantId);
        })
        ->when($filters['user_id'] ?? null, function ($query, $userId) {
            $query->where('user_id', $userId);
        })
        ->when($filters['from'] ?? null, function ($query, $date) {
            $query->whereDate('created_at', '>=', $date);
        })
        ->when($filters['to'] ?? null, function ($query, $date) {
            $query->whereDate('created_at', '<=', $date);
        })
        ->latest('id')
        ->paginate(25)
        ->withQueryString();

        return view('platform.audit-logs.index', [
            'logs' => $logs,
            'filters' => $filters,
            'actions' => $this->actions(),
        ]);
    }

    public function show(PlatformAuditLog $auditLog): View
    {
        $auditLog->load([
            'user:id,name,email',
            'tenant:id,name,slug',
        ]);

        return view('platform.audit-logs.show', [
            'log' => $auditLog,
            'metadata' => $auditLog->metadata ?? [],
            'actions' => $this->actions(),
        ]);
    }

    private function actions(): array
    {
        return [
            'tenant.provisioned' => 'Tenant Provisioned',
            'tenant.status_changed' => 'Tenant Status Changed',
            'tenant.access_requested' => 'Tenant Access Requested',
            'tenant.access_granted' => 'Tenant Access Granted',
        ];
    }
}