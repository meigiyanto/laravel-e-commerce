<?php

namespace App\Services\Platform;

use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Platform\PlatformAuditAction;
use Illuminate\Http\Request;
use InvalidArgumentException;

class PlatformAuditLogger
{
    private const CONTRACTS = [
        PlatformAuditAction::TENANT_PROVISIONED => [
            'required' => [
                'store_id',
                'tenant_name',
                'store_name',
                'owner_email',
            ],
            'optional' => [],
        ],
        PlatformAuditAction::TENANT_STATUS_CHANGED => [
            'required' => ['from', 'to'],
            'optional' => ['reason'],
        ],
        PlatformAuditAction::TENANT_ACCESS_REQUESTED => [
            'required' => ['domain'],
            'optional' => [],
        ],
        PlatformAuditAction::TENANT_ACCESS_GRANTED => [
            'required' => ['access_token_id', 'domain'],
            'optional' => [],
        ],
    ];

    public function __construct(
        private readonly Request $request,
    ) {}

    public function record(
        User $actor,
        string $action,
        ?Tenant $tenant,
        array $metadata = [],
    ): PlatformAuditLog 
    {
        $contract = self::CONTRACTS[$action] ?? null;

        if (!$contract) {
            throw new InvalidArgumentException(
                "Unknown platform audit action: {$action}"
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

        return PlatformAuditLog::create([
            'user_id' => $actor->id,
            'tenant_id' => $tenant?->id,
            'action' => $action,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
    
    private function validateValues(string $action, array $metadata): void
    {
        $valid = match ($action) {
            PlatformAuditAction::TENANT_PROVISIONED =>
            is_int($metadata['store_id']) &&
            is_string($metadata['tenant_name']) &&
            is_string($metadata['store_name']) &&
            filter_var($metadata['owner_email'], FILTER_VALIDATE_EMAIL),

            PlatformAuditAction::TENANT_STATUS_CHANGED =>
            in_array($metadata['from'], ['pending', 'active', 'suspended'], true) &&
            in_array($metadata['to'], ['pending', 'active', 'suspended'], true) &&
            (!isset($metadata['reason']) ||
                (is_string($metadata['reason']) && mb_strlen($metadata['reason']) <= 1000)),

            PlatformAuditAction::TENANT_ACCESS_REQUESTED =>
            is_string($metadata['domain']) && mb_strlen($metadata['domain']) <= 253,

            PlatformAuditAction::TENANT_ACCESS_GRANTED =>
            is_int($metadata['access_token_id']) &&
            is_string($metadata['domain']) &&
            mb_strlen($metadata['domain']) <= 253,

            default => false,
        };

        if (!$valid) {
            throw new InvalidArgumentException(
                "Invalid metadata values for {$action}"
            );
        }
    }
}
