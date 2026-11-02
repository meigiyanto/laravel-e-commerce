<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BootstrapDefaultTenant extends Command
{
    protected $signature = 'tenancy:bootstrap-default
        {--owner-id= : Existing users.id that owns the default tenant}
        {--tenant-name=Default Store : Default tenant and store name}
        {--tenant-slug=default : Unique tenant and store slug}';

    protected $description = 'Create the default tenant and backfill legacy catalog ownership.';

    public function handle(): int
    {
        $ownerId = filter_var(
            $this->option('owner-id'),
            FILTER_VALIDATE_INT
        );

        if (! $ownerId || ! User::query()->whereKey($ownerId)->exists()) {
            $this->error(
                'Provide --owner-id with an existing users.id.'
            );

            return self::FAILURE;
        }

        $name = trim((string) $this->option('tenant-name'));
        $slug = trim((string) $this->option('tenant-slug'));

        if ($name === '' || $slug === '') {
            $this->error('Tenant name and slug must not be empty.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($ownerId, $name, $slug) {
                $tenant = Tenant::query()->firstOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $name,
                        'status' => 'active',
                        'created_by_user_id' => $ownerId,
                    ]
                );

                if ((int) $tenant->created_by_user_id !== $ownerId) {
                    throw new RuntimeException(
                        'The existing tenant slug belongs to another owner.'
                    );
                }

                $tenant->store()->firstOrCreate([], [
                    'name' => $name,
                    'slug' => $slug,
                    'status' => 'active',
                    'settings' => [],
                ]);

                TenantMembership::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'user_id' => $ownerId,
                    ],
                    [
                        'role' => TenantMembership::ROLE_OWNER,
                        'status' => 'active',
                        'accepted_at' => now(),
                    ]
                );

                $this->backfillCatalog($tenant->id);
                $this->backfillInventory();
            });
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info(
            'Default tenant, store, membership, and ownership are ready.'
        );

        return self::SUCCESS;
    }

    private function backfillCatalog(int $tenantId): void
    {
        foreach ([
            'categories',
            'sub_categories',
            'products',
            'attributes',
        ] as $table) {
            DB::table($table)
                ->whereNull('tenant_id')
                ->update(['tenant_id' => $tenantId]);
        }

        DB::table('product_variants')
            ->whereNull('tenant_id')
            ->orderBy('id')
            ->chunkById(200, function ($variants) {
                foreach ($variants as $variant) {
                    $productTenantId = DB::table('products')
                        ->where('id', $variant->product_id)
                        ->value('tenant_id');

                    if (! $productTenantId) {
                        throw new RuntimeException(
                            "Variant {$variant->id} has no tenant-owned product."
                        );
                    }

                    DB::table('product_variants')
                        ->where('id', $variant->id)
                        ->whereNull('tenant_id')
                        ->update(['tenant_id' => $productTenantId]);
                }
            });
    }

    private function backfillInventory(): void
    {
        foreach ([
            'inventories',
            'inventory_movements',
            'inventory_reservations',
        ] as $table) {
            DB::table($table)
                ->whereNull('tenant_id')
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        $tenantId = DB::table('product_variants')
                            ->where('id', $row->product_variant_id)
                            ->value('tenant_id');

                        if (! $tenantId) {
                            throw new RuntimeException(
                                "{$table} row {$row->id} has no tenant-owned variant."
                            );
                        }

                        DB::table($table)
                            ->where('id', $row->id)
                            ->whereNull('tenant_id')
                            ->update(['tenant_id' => $tenantId]);
                    }
                });
        }
    }
}