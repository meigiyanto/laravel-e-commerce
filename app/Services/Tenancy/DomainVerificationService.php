<?php

namespace App\Services\Tenancy;

use App\Models\DomainVerificationChallenge;
use App\Models\Store;
use App\Models\StoreDomain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DomainVerificationService
{
    public function create(Store $store, string $domain): DomainVerificationChallenge
    {
        $domain = strtolower(rtrim(trim($domain), '.'));

        if (
            !filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) || str_contains($domain, '/')
        ) {
            throw ValidationException::withMessages([
                'domain' => 'Hostname domain tidak valid.',
            ]);
        }

        if (in_array($domain, ['localhost', '127.0.0.1'], true)) {
            throw ValidationException::withMessages([
                'domain' => 'Domain lokal tidak dapat digunakan sebagai custom domain.',
            ]);
        }

        return DB::transaction(function () use ($store, $domain) {
            $storeDomain = StoreDomain::query()->firstOrCreate(
                ['domain' => $domain],
                [
                    'store_id' => $store->id,
                    'type' => 'custom',
                    'is_primary' => false,
                    'verification_status' => 'pending',
                ]
            );

            if ((int) $storeDomain->store_id !== (int) $store->id) {
                throw ValidationException::withMessages([
                    'domain' => 'Domain sudah digunakan oleh store lain.',
                ]);
            }

            $token = Str::random(48);

            return DomainVerificationChallenge::updateOrCreate(
                ['store_domain_id' => $storeDomain->id],
                [
                    'method' => 'dns_txt',
                    'record_name' => '_saas-verification.'.$domain,
                    'record_value' => 'saas-verification='.$token,
                    'expires_at' => now()->addHours(24),
                    'verified_at' => null,
                ]
            );
        });
    }

    public function verify(DomainVerificationChallenge $challenge): bool
    {
        $challenge->refresh();

        if ($challenge->verified_at) {
            return true;
        }

        if (! $challenge->expires_at || $challenge->expires_at->isPast()) {
            return false;
        }

        $records = @dns_get_record($challenge->record_name, DNS_TXT);

        if (! is_array($records)) {
            return false;
        }

        $matched = collect($records)->contains(function ($record) use ($challenge) {
            return hash_equals(
                $challenge->record_value,
                (string) ($record['txt'] ?? '')
            );
        });

        if (! $matched) {
            return false;
        }

        return DB::transaction(function () use ($challenge) {
            $locked = DomainVerificationChallenge::query()
            ->lockForUpdate()
            ->findOrFail($challenge->id);

            if ($locked->verified_at) {
                return true;
            }

            if (! $locked->expires_at || $locked->expires_at->isPast()) {
                return false;
            }

            $domain = StoreDomain::query()
            ->lockForUpdate()
            ->findOrFail($locked->store_domain_id);

            $store = Store::query()
            ->lockForUpdate()
            ->findOrFail($domain->store_id);

            $hasVerifiedPrimary = $store->domains()
                ->where('verification_status', 'verified')
                ->whereNotNull('verified_at')
                ->where('is_primary', true)
                ->where('id', '!=', $domain->id)
                ->exists();
            
            $locked->update(['verified_at' => now()]);
            
            if (! $hasVerifiedPrimary) {
                $store->domains()
                    ->where('is_primary', true)
                    ->where('id', '!=', $domain->id)
                    ->update(['is_primary' => false]);
            }
            
            $domain->update([
                'verification_status' => 'verified',
                'verified_at' => now(),
                'is_primary' => ! $hasVerifiedPrimary,
            ]);
            
            return true;
        });
    }
}