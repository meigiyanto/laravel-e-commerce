<?php

namespace App\Services\Tenancy;

use App\Models\TenantInvitation;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OwnerInvitationService
{
    public function accept(string $plainToken, User $user): TenantMembership
    {
        return DB::transaction(function () use ($plainToken, $user) {
            $invitation = TenantInvitation::query()
                ->where('token_hash', hash('sha256', $plainToken))
                ->lockForUpdate()
                ->first();

            if (!$invitation || !$invitation->isUsable()) {
                throw ValidationException::withMessages([
                    'invitation' => 'Undangan tidak valid atau sudah kedaluwarsa.',
                ]);
            }

            if (mb_strtolower($user->email) !== mb_strtolower($invitation->email)) {
                throw ValidationException::withMessages([
                    'email' => 'Email akun tidak cocok dengan email undangan.',
                ]);
            }

            $tenant = $invitation->tenant;

            if ($tenant->status === 'suspended') {
                throw ValidationException::withMessages([
                    'tenant' => 'Tenant sedang ditangguhkan.',
                ]);
            }

            $membership = TenantMembership::query()->firstOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                ],
                [
                    'role' => TenantMembership::ROLE_OWNER,
                    'status' => 'active',
                    'invited_at' => $invitation->created_at,
                    'accepted_at' => now(),
                ]
            );

            if ($membership->status !== 'active') {
                $membership->update([
                    'role' => TenantMembership::ROLE_OWNER,
                    'status' => 'active',
                    'accepted_at' => now(),
                ]);
            }

            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            return $membership;
        });
    }
}
