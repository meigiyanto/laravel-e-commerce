<?php

namespace App\Models\Concerns;

use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use LogicException;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder): void {
            $column = $builder->getModel()->qualifyColumn('tenant_id');

            if (! static::tenantIsolationEnabled()) {
                $builder->whereNull($column);
                return;
            }

            $tenantId = app(TenantContext::class)->current()?->getKey();

            if ($tenantId === null) {
                $builder->whereRaw('1 = 0');
                return;
            }

            $builder->where($column, $tenantId);
        });

        static::creating(function (Model $model): void {
            if (! static::tenantIsolationEnabled()) {
                $model->setAttribute('tenant_id', null);
                return;
            }

            $contextTenantId = app(TenantContext::class)
                ->current()?->getKey();

            $parentTenantId = $model->resolveParentTenantId();

            $tenantId = $model->getAttribute('tenant_id')
                ?: $parentTenantId
                ?: $contextTenantId;

            if (! $tenantId) {
                throw new LogicException(sprintf(
                    '%s requires tenant_id or an initialized TenantContext.',
                    $model::class
                ));
            }

            if (
                $contextTenantId !== null
                && (int) $tenantId !== (int) $contextTenantId
            ) {
                throw new LogicException(sprintf(
                    '%s cannot be created outside the active tenant.',
                    $model::class
                ));
            }

            $model->setAttribute('tenant_id', $tenantId);
        });
    }

    protected static function tenantIsolationEnabled(): bool
    {
        return config('operational.mode', 'single_store')
            === 'multi_tenant';
    }

    protected function resolveParentTenantId(): ?int
    {
        $parent = static::tenantParent();

        if (! $parent) {
            return null;
        }

        [$table, $foreignKey] = $parent;
        $parentId = $this->getAttribute($foreignKey);

        if (! $parentId) {
            return null;
        }

        $tenantId = DB::table($table)
            ->where('id', $parentId)
            ->value('tenant_id');

        if (! $tenantId) {
            throw new LogicException(sprintf(
                'Cannot resolve tenant ownership from %s.%s.',
                $table,
                $foreignKey
            ));
        }

        return (int) $tenantId;
    }

    protected static function tenantParent(): ?array
    {
        return null;
    }
}
