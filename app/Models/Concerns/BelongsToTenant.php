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
            $tenantId = app(TenantContext::class)->current()?->getKey();

            if ($tenantId === null) {
                $builder->whereRaw('1 = 0');
                return;
            }

            $builder->where(
                $builder->getModel()->qualifyColumn('tenant_id'),
                $tenantId
            );
        });

        static::creating(function (Model $model): void {
            $contextTenantId = app(TenantContext::class)->current()?->getKey();
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

            if ($contextTenantId !== null && (int) $tenantId !== (int) $contextTenantId) {
                throw new LogicException(sprintf(
                    '%s cannot be created outside the active tenant.',
                    $model::class
                ));
            }

            /* if ($parentTenantId !== null && (int) $tenantId !== $parentTenantId) {
                throw new LogicException(sprintf(
                    '%s tenant_id does not match its parent record.',
                    $model::class
                ));
            } */

            $model->setAttribute('tenant_id', $tenantId);
        });
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
