<?php

namespace App\Services\Platform;

use App\Models\PlatformAuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Platform\PlatformAuditAction;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Illuminate\Support\Facades\Log;
use Throwable;

class PlatformAuditLogger
{
    private const CONTRACTS = [
        PlatformAuditAction::TENANT_PROVISIONED => [
            'required' => ['store_id',
                'tenant_name',
                'store_name'],
            'optional' => [],
        ],
        PlatformAuditAction::TENANT_STATUS_CHANGED => [
            'required' => ['from',
                'to'],
            'optional' => [],
        ],
        PlatformAuditAction::TENANT_ACCESS_REQUESTED => [
            'required' => ['domain'],
            'optional' => [],
        ],
        PlatformAuditAction::TENANT_ACCESS_GRANTED => [
            'required' => ['access_token_id',
                'domain'],
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
    ): PlatformAuditLog {
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

        $missing = array_diff(array_keys($contract['required']), array_keys($metadata));

        $unexpected = array_diff(array_keys($metadata), $allowed);

        if ($missing || $unexpected) {
            throw new InvalidArgumentException('Invalid metadata contract for '.$action.'. Missing: '.implode(', ', $missing).'. Unexpected: '.implode(', ', $unexpected));
        }

        $this->validateValues($action, $metadata);
        $metadata = $this->sanitizeMetadata($metadata);
        $this->validateValues($action, $metadata);

        try {
            return PlatformAuditLog::create([
                'user_id' => $actor->id,
                'tenant_id' => $tenant?->id,
                'action' => $action,
                'ip_address' => $this->request->ip(),
                'user_agent' => $this->safeUserAgent(),
                'metadata' => $this->sanitizeMetadata($metadata),
            ]);
        } catch (Throwable $exception) {
            try {
                Log::channel('audit_ops')->error(
                    'Platform audit log persistence failed',
                    [
                        'action' => $action,
                        'actor_id' => $actor->id,
                        'tenant_id' => $tenant?->id,
                        'exception_class' => $exception::class,
                    ]
                );
            } catch (Throwable) {
                // Jangan sampai kegagalan operational logger menutupi
                // exception asli dari penyimpanan audit.
            }

            throw $exception;
        }
    }

    private function safeUserAgent(): ?string
    {
        $userAgent = $this->request->userAgent();

        if (!$userAgent) {
            return null;
        }

        // User-Agent adalah input dari client, bukan data tepercaya.
        $userAgent = preg_replace('/[\x00-\x1F\x7F]/u', '', $userAgent);

        return mb_substr($userAgent ?? '', 0, 500) ?: null;
    }

    private function sanitizeMetadata(array $metadata): array
    {
        $sensitiveKeys = [
            'password',
            'passwordconfirmation',
            'secret',
            'accesstoken',
            'plaintoken',
            'token',
            'tokenhash',
            'authorization',
            'cookie',
            'setcookie',
            'email',
            'owneremail',
        ];

        $sanitize = function (array $items) use (&$sanitize, $sensitiveKeys): array {
            $result = [];

            foreach ($items as $key => $value) {
                $normalizedKey = strtolower(
                    preg_replace('/[^a-z0-9]/i', '', (string) $key)
                );

                if (in_array($normalizedKey, $sensitiveKeys, true)) {
                    continue;
                }

                if (is_array($value)) {
                    $result[$key] = $sanitize($value);
                    continue;
                }

                if (is_string($value)) {
                    $value = preg_replace(
                        '/Bearer\s+[A-Za-z0-9._~+\/=-]+/i',
                        '[REDACTED]',
                        $value
                    );

                    $value = preg_replace(
                        '/[\w.+-]+@[\w.-]+\.[A-Za-z]{2,}/u',
                        '[REDACTED_EMAIL]',
                        $value
                    );

                    $result[$key] = mb_substr($value ?? '', 0, 500);
                    continue;
                }

                $result[$key] = $value;
            }

            return $result;
        };

        return $sanitize($metadata);
    }


    private function validateValues(string $action, array $metadata): void
    {
        $valid = match ($action) {
            PlatformAuditAction::TENANT_PROVISIONED =>
            is_int($metadata['store_id']) &&
            is_string($metadata['tenant_name']) &&
            mb_strlen($metadata['tenant_name']) <= 150 &&
            is_string($metadata['store_name']) &&
            mb_strlen($metadata['store_name']) <= 150,

            PlatformAuditAction::TENANT_STATUS_CHANGED =>
            in_array($metadata['from'], [
                'pending', 'active', 'suspended',
            ], true) &&
            in_array($metadata['to'], [
                'pending', 'active', 'suspended',
            ], true),

            PlatformAuditAction::TENANT_ACCESS_REQUESTED =>
            is_string($metadata['domain']) &&
            mb_strlen($metadata['domain']) <= 253,

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