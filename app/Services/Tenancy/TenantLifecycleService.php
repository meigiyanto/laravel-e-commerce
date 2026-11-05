<?php

namespace App\Services\Tenancy;

use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Platform\PlatformAuditLogger;
use App\Support\Platform\PlatformAuditAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TenantLifecycleService
{
    public function __construct(
        private readonly PlatformAuditLogger $auditLogger,
    ) {}

    public function activate(Tenant $tenant, User $actor): Tenant
    {
        return $this->changeStatus($tenant, $actor, 'active');
    }

    public function suspend(
        Tenant $tenant,
        User $actor,
        string $reason
    ): Tenant {
        if (trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'Alasan penangguhan wajib diisi.',
            ]);
        }

        return $this->changeStatus(
            $tenant,
            $actor,
            'suspended',
            ['reason' => $reason]
        );
    }

    public function changeStatus(
        Tenant $tenant,
        User $actor,
        string $status,
        array $metadata = []
    ): Tenant {
        if (!in_array($status, ['pending', 'active', 'suspended'], true)) {
            throw ValidationException::withMessages([
                'status' => 'Status tenant tidak valid.',
            ]);
        }

        return DB::transaction(function () use (
            $tenant,
            $actor,
            $status,
            $metadata
        ) {
            $locked = Tenant::query()->lockForUpdate()->findOrFail($tenant->id);
            $previous = $locked->status;

            $locked->update(['status' => $status]);

            // Store tidak boleh aktif ketika tenant tidak aktif.
            if ($locked->store) {
                $locked->store->update([
                    'status' => $status === 'active' ? 'active' : 'suspended',
                ]);
            }

            $this->auditLogger->record(
                actor: $actor,
                action: PlatformAuditAction::TENANT_STATUS_CHANGED,
                tenant: $locked,
                metadata: [
                    'from' => $previous,
                    'to' => $status,
                    ...array_intersect_key($metadata, ['reason' => true]),
                ],
            );

            return $locked->fresh('store');
        });
    }
}