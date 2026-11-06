<?php

namespace App\Services\Tenancy;

use App\Models\Tenant;
use App\Models\TenantAuditLog;
use App\Models\User;
use App\Support\Tenancy\TenantAuditAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;

class TenantAuditLogger
{
    private const CONTRACTS = [
        TenantAuditAction::DOMAIN_CREATED => [
            'required' => ['store_domain_id', 'domain', 'type'],
            'optional' => [],
        ],
        TenantAuditAction::DOMAIN_VERIFIED => [
            'required' => ['store_domain_id', 'domain'],
            'optional' => [],
        ],
        TenantAuditAction::MEMBERSHIP_ROLE_CHANGED => [
            'required' => ['membership_id', 'user_id', 'from', 'to'],
            'optional' => [],
        ],
        TenantAuditAction::MEMBERSHIP_DEACTIVATED => [
            'required' => ['membership_id', 'user_id', 'role'],
            'optional' => [],
        ],
        TenantAuditAction::INVENTORY_STOCK_UPDATED => [
            'required' => [
                'product_id',
                'variant_id',
                'from',
                'to',
                'reserved_quantity',
            ],
            'optional' => [],
        ],
        TenantAuditAction::INVENTORY_STOCK_ADJUSTED => [
            'required' => [
                'product_id',
                'variant_id',
                'from',
                'to',
                'reserved_quantity',
            ],
            'optional' => [],
        ],
    ];

    public function __construct(
        private readonly Request $request,
    ) {}

    public function record(
        User $actor,
        Tenant $tenant,
        string $action,
        Model $subject,
        array $metadata = [],
    ): TenantAuditLog {
        $contract = self::CONTRACTS[$action] ?? null;

        if (!$contract) {
            throw new InvalidArgumentException(
                "Unknown tenant audit action: {$action}"
            );
        }

        $allowed = [
            ...$contract['required'],
            ...$contract['optional'],
        ];

        $missing = array_diff(
            $contract['required'],
            array_keys($metadata)
        );

        $unexpected = array_diff(
            array_keys($metadata),
            $allowed
        );

        if ($missing || $unexpected) {
            throw new InvalidArgumentException(
                'Invalid metadata contract for '.$action.
                '. Missing: '.implode(', ', $missing).
                '. Unexpected: '.implode(', ', $unexpected)
            );
        }

        $this->validateValues($action, $metadata);

        if (!$subject->exists) {
            throw new InvalidArgumentException(
                'Audit subject must be a persisted model.'
            );
        }

        return TenantAuditLog::create([
            'tenant_id' => $tenant->id,
            'user_id' => $actor->id,
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    private function validateValues(string $action, array $metadata): void
    {
        $valid = match ($action) {
            TenantAuditAction::DOMAIN_CREATED =>
                is_int($metadata['store_domain_id']) &&
                $this->validDomain($metadata['domain']) &&
                in_array($metadata['type'], ['custom', 'subdomain'], true),

            TenantAuditAction::DOMAIN_VERIFIED =>
                is_int($metadata['store_domain_id']) &&
                $this->validDomain($metadata['domain']),

            TenantAuditAction::MEMBERSHIP_ROLE_CHANGED =>
                is_int($metadata['membership_id']) &&
                is_int($metadata['user_id']) &&
                in_array($metadata['from'], [
                    'owner', 'admin', 'product_manager',
                    'inventory_manager', 'order_manager', 'staff',
                ], true) &&
                in_array($metadata['to'], [
                    'owner', 'admin', 'product_manager',
                    'inventory_manager', 'order_manager', 'staff',
                ], true),

            TenantAuditAction::MEMBERSHIP_DEACTIVATED =>
                is_int($metadata['membership_id']) &&
                is_int($metadata['user_id']) &&
                in_array($metadata['role'], [
                    'owner', 'admin', 'product_manager',
                    'inventory_manager', 'order_manager', 'staff',
                ], true),

            TenantAuditAction::INVENTORY_STOCK_UPDATED,
            TenantAuditAction::INVENTORY_STOCK_ADJUSTED =>
                is_int($metadata['product_id']) &&
                is_int($metadata['variant_id']) &&
                is_int($metadata['from']) &&
                is_int($metadata['to']) &&
                is_int($metadata['reserved_quantity']) &&
                $metadata['from'] >= 0 &&
                $metadata['to'] >= 0 &&
                $metadata['reserved_quantity'] >= 0,

            default => false,
        };

        if (!$valid) {
            throw new InvalidArgumentException(
                "Invalid metadata values for {$action}"
            );
        }
    }

    private function validDomain(mixed $domain): bool
    {
        return is_string($domain)
            && strlen($domain) <= 253
            && filter_var(
                $domain,
                FILTER_VALIDATE_DOMAIN,
                FILTER_FLAG_HOSTNAME
            ) !== false;
    }
}