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
            ! filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            || filter_var($domain, FILTER_VALIDATE_IP)
            || str_contains($domain, '/')
            || str_contains($domain, ':')
            || in_array($domain, ['localhost', '127.0.0.1'], true)
        ) {
            throw ValidationException::withMessages([
                'domain' => 'Hostname domain tidak valid.',
            ]);
        }

        return DB::transaction(function () use ($store, $domain) {
            $storeDomain = StoreDomain::query()
                ->where('domain', $domain)
                ->lockForUpdate()
                ->first();

            if ($storeDomain && (int) $storeDomain->store_id !== (int) $store->id) {
                throw ValidationException::withMessages([
                    'domain' => 'Domain sudah digunakan oleh store lain.',
                ]);
            }

            if ($storeDomain?->verification_status === 'verified') {
                throw ValidationException::withMessages([
                    'domain' => 'Domain ini sudah berhasil diverifikasi.',
                ]);
            }

            if (! $storeDomain) {
                $storeDomain = StoreDomain::create([
                    'store_id' => $store->id,
                    'domain' => $domain,
                    'type' => 'custom',
                    'is_primary' => false,
                    'verification_status' => 'pending',
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

        if (
            $challenge->method !== 'dns_txt'
            || ! $challenge->expires_at
            || $challenge->expires_at->isPast()
        ) {
            return false;
        }

        $expectedValue = $challenge->record_value;
        $records = $this->lookupTxtRecords($challenge->record_name);

        $matched = collect($records)->contains(
            fn (string $value): bool => hash_equals($expectedValue, $value)
        );

        if (! $matched) {
            return false;
        }

        return DB::transaction(function () use ($challenge, $expectedValue) {
            $locked = DomainVerificationChallenge::query()
                ->lockForUpdate()
                ->findOrFail($challenge->id);

            if ($locked->verified_at) {
                return true;
            }

            // Pastikan token belum diganti setelah DNS diperiksa.
            if (! hash_equals($locked->record_value, $expectedValue)) {
                return false;
            }

            if (
                $locked->method !== 'dns_txt'
                || ! $locked->expires_at
                || $locked->expires_at->isPast()
            ) {
                return false;
            }

            $domain = StoreDomain::query()
                ->lockForUpdate()
                ->findOrFail($locked->store_domain_id);

            $store = Store::query()
                ->lockForUpdate()
                ->findOrFail($domain->store_id);

            if ($domain->verification_status === 'verified') {
                return true;
            }

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

    /**
     * Menggabungkan TXT record yang mungkin dikembalikan DNS
     * sebagai beberapa character-string.
     */
    protected function lookupTxtRecords(string $hostname): array
    {
        $records = @dns_get_record($hostname, DNS_TXT);

        if (! is_array($records)) {
            return [];
        }

        $values = [];

        foreach ($records as $record) {
            if (isset($record['entries']) && is_array($record['entries'])) {
                $values[] = implode('', $record['entries']);

                continue;
            }

            if (isset($record['txt'])) {
                $values[] = (string) $record['txt'];
            }
        }

        return $values;
    }
}
