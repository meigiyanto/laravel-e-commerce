<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class DefaultTenantSeeder extends Seeder
{
    public const TENANT_SLUG = 'default';

    public function run(): void
    {
        DB::transaction(function (): void {
            $tenant = Tenant::query()
                ->where('slug', self::TENANT_SLUG)
                ->first();

            if ($tenant) {
                $owner = $tenant->creator;

                if (! $owner) {
                    throw new RuntimeException(
                        'Default tenant does not have a valid owner.'
                    );
                }
            } else {
                $ownerEmail = env('ADMIN_EMAIL', 'admin@example.com');

                $owner = User::query()
                    ->where('email', $ownerEmail)
                    ->first();

                if (! $owner) {
                    throw new RuntimeException(
                        'Default tenant cannot be created. '
                        . "User [{$ownerEmail}] does not exist. "
                        . 'Configure ADMIN_EMAIL and ADMIN_PASSWORD, '
                        . 'then run the seeder again.'
                    );
                }

                $tenant = Tenant::query()->create([
                    'created_by_user_id' => $owner->id,
                    'name' => 'Default Store',
                    'slug' => self::TENANT_SLUG,
                    'status' => 'active',
                ]);
            }

            $tenant->store()->firstOrCreate(
                [],
                [
                    'name' => $tenant->name,
                    'slug' => self::TENANT_SLUG,
                    'status' => 'active',
                    'settings' => [],
                ]
            );

            TenantMembership::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'user_id' => $owner->id,
                ],
                [
                    'role' => TenantMembership::ROLE_OWNER,
                    'status' => 'active',
                    'accepted_at' => now(),
                ]
            );
        });
    }
}
