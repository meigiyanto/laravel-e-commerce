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
    protected $signature = 'tenancy:bootstrap-default {--owner-id= : Existing users.id that owns the default tenant} {--tenant-name=Default Store : Default tenant and store name} {--tenant-slug=default : Unique tenant and store slug}';
    protected $description = 'Create the default tenant and backfill legacy catalog ownership.';

    public function handle(): int
    {
        $ownerId = filter_var($this->option('owner-id'), FILTER_VALIDATE_INT);
        if (! $ownerId || ! User::query()->whereKey($ownerId)->exists()) {
            $this->error('Provide --owner-id with an existing users.id.');
            return self::FAILURE;
        }
        $name = trim((string) $this->option('tenant-name'));
        $slug = trim((string) $this->option('tenant-slug'));
        if ($name === '' || $slug === '') {
            $this->error('Tenant name and slug must not be empty.');
            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($ownerId, $name, $slug): void {
                $tenant = Tenant::query()->firstOrCreate(['slug' => $slug], ['name' => $name, 'status' => 'active', 'created_by_user_id' => $ownerId]);
                if ((int) $tenant->created_by_user_id !== $ownerId) {
                    throw new RuntimeException('The existing tenant slug belongs to another owner.');
                }
                $tenant->store()->firstOrCreate([], ['name' => $name, 'slug' => $slug, 'status' => 'active', 'settings' => []]);
                TenantMembership::query()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'user_id' => $ownerId],
                    ['role' => TenantMembership::ROLE_OWNER, 'status' => 'active', 'accepted_at' => now()]
                );

                $this->backfillCatalog((int) $tenant->id);
                $this->backfillAttributeValues((int) $tenant->id);
                $this->backfillVariants((int) $tenant->id);
                $this->backfillInventory();
                $this->backfillVariantAttributePivot();
            });
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $this->info('Default tenant, store, membership, and ownership are ready.');
        return self::SUCCESS;
    }

    private function backfillCatalog(int $tenantId): void
    {
        foreach (['categories', 'sub_categories', 'products', 'attributes'] as $table) {
            DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $tenantId]);
        }
    }

    private function backfillAttributeValues(int $tenantId): void
    {
        if (! DB::getSchemaBuilder()->hasTable('attribute_values')) return;
        DB::table('attribute_values')->whereNull('tenant_id')->orderBy('id')->chunkById(200, function ($rows) use ($tenantId): void {
            foreach ($rows as $row) {
                $ownerId = DB::table('attributes')->where('id', $row->attribute_id)->value('tenant_id');
                if (! $ownerId) throw new RuntimeException("Attribute value {$row->id} has no tenant-owned attribute.");
                DB::table('attribute_values')->where('id', $row->id)->whereNull('tenant_id')->update(['tenant_id' => $ownerId ?: $tenantId]);
            }
        });
    }

    private function backfillVariants(int $tenantId): void
    {
        DB::table('product_variants')->whereNull('tenant_id')->orderBy('id')->chunkById(200, function ($rows) use ($tenantId): void {
            foreach ($rows as $variant) {
                $ownerId = DB::table('products')->where('id', $variant->product_id)->value('tenant_id');
                if (! $ownerId) throw new RuntimeException("Variant {$variant->id} has no tenant-owned product.");
                DB::table('product_variants')->where('id', $variant->id)->whereNull('tenant_id')->update(['tenant_id' => $ownerId ?: $tenantId]);
            }
        });
    }

    private function backfillInventory(): void
    {
        foreach (['inventories', 'inventory_movements', 'inventory_reservations'] as $table) {
            DB::table($table)->whereNull('tenant_id')->orderBy('id')->chunkById(200, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    $ownerId = DB::table('product_variants')->where('id', $row->product_variant_id)->value('tenant_id');
                    if (! $ownerId) throw new RuntimeException("{$table} row {$row->id} has no tenant-owned variant.");
                    DB::table($table)->where('id', $row->id)->whereNull('tenant_id')->update(['tenant_id' => $ownerId]);
                }
            });
        }
    }

    private function backfillVariantAttributePivot(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('product_variant_attribute_values')) return;
        $rows = DB::table('product_variant_attribute_values')->whereNull('tenant_id')->orderBy('product_variant_id')->orderBy('attribute_value_id')->get();
        foreach ($rows as $row) {
                $variantTenantId = DB::table('product_variants')->where('id', $row->product_variant_id)->value('tenant_id');
                $valueTenantId = DB::table('attribute_values')->where('id', $row->attribute_value_id)->value('tenant_id');
                if (! $variantTenantId || (int) $variantTenantId !== (int) $valueTenantId) {
                    throw new RuntimeException("Variant attribute pivot {$row->product_variant_id}/{$row->attribute_value_id} crosses tenant ownership.");
                }
                DB::table('product_variant_attribute_values')->where('product_variant_id', $row->product_variant_id)->where('attribute_value_id', $row->attribute_value_id)->whereNull('tenant_id')->update(['tenant_id' => $variantTenantId]);
        }
    }
}
