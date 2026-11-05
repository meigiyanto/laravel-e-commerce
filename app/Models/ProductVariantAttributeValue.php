<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\DB;
use LogicException;

class ProductVariantAttributeValue extends Pivot
{
    protected $table = 'product_variant_attribute_values';
    public $incrementing = false;
    protected $fillable = ['tenant_id', 'product_variant_id', 'attribute_value_id'];

    protected static function booted(): void
    {
        static::creating(function (self $pivot): void {
            $variantTenantId = DB::table('product_variants')->where('id', $pivot->product_variant_id)->value('tenant_id');
            $valueTenantId = DB::table('attribute_values')->where('id', $pivot->attribute_value_id)->value('tenant_id');

            if (! $variantTenantId || ! $valueTenantId || (int) $variantTenantId !== (int) $valueTenantId) {
                throw new LogicException('Variant and attribute value must belong to the same tenant.');
            }

            if ($pivot->tenant_id && (int) $pivot->tenant_id !== (int) $variantTenantId) {
                throw new LogicException('Pivot tenant_id does not match its related records.');
            }

            $pivot->tenant_id = $variantTenantId;
        });
    }
}
