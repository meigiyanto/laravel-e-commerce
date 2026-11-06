<?php

namespace App\Services\Tenancy;

use App\Models\Store;
use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\Platform\PlatformAuditLogger;
use App\Support\Platform\PlatformAuditAction;

class TenantProvisioningService
{
    public function __construct(
        private readonly PlatformAuditLogger $auditLogger,
    ) {}

    public function provision(
        User $platformAdmin,
        string $tenantName,
        string $storeName,
        string $ownerEmail
    ): array {
        $ownerEmail = mb_strtolower(trim($ownerEmail));

        if (!filter_var($ownerEmail, FILTER_VALIDATE_EMAIL)) {
            throw ValidationException::withMessages([
                'owner_email' => 'Alamat email owner tidak valid.',
            ]);
        }

        $plainToken = Str::random(64);

        $result = DB::transaction(function () use (
            $platformAdmin,
            $tenantName,
            $storeName,
            $ownerEmail,
            $plainToken
        ) {
            $tenantSlug = $this->uniqueSlug(Tenant::class, $tenantName);
            $storeSlug = $this->uniqueSlug(Store::class, $storeName);

            $tenant = Tenant::create([
                'name' => $tenantName,
                'slug' => $tenantSlug,
                'status' => 'pending',
                'created_by_user_id' => $platformAdmin->id,
            ]);

            $store = Store::create([
                'tenant_id' => $tenant->id,
                'name' => $storeName,
                'slug' => $storeSlug,
                'status' => 'pending',
                'settings' => [],
            ]);

            $invitation = TenantInvitation::create([
                'tenant_id' => $tenant->id,
                'invited_by' => $platformAdmin->id,
                'email' => $ownerEmail,
                'role' => TenantMembership::ROLE_OWNER,
                'token_hash' => hash('sha256', $plainToken),
                'status' => 'pending',
                'expires_at' => now()->addDays(3),
            ]);

            $this->auditLogger->record(
                actor: $platformAdmin,
                action: PlatformAuditAction::TENANT_PROVISIONED,
                tenant: $tenant,
                metadata: [
                    'store_id' => (int) $store->id,
                    'tenant_name' => $tenant->name,
                    'store_name' => $store->name,
                ],
            );

            return compact('tenant', 'store', 'invitation');
        });

        return [
            ...$result,
            'plain_token' => $plainToken,
        ];
    }

    private function uniqueSlug(string $model, string $name): string
    {
        $base = Str::slug($name) ?: 'store';
        $slug = $base;
        $suffix = 1;

        while ($model::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}