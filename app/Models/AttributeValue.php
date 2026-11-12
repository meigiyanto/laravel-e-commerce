<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AttributeValue extends Model
{
    use BelongsToTenant;
    
    protected $fillable = [
        'tenant_id',
        'attribute_id',
        'value',
        'slug',
        'hex_color',
        'sort_order'
    ];
    
    protected static function tenantParent(): ?array {
        return ['attributes', 'attribute_id'];
    }
    
    public function tenant(): BelongsTo {
        return $this->belongsTo(Tenant::class);
    }
    
    public function attribute(): BelongsTo {
        return $this->belongsTo(Attribute::class);
    }
    
    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'product_variant_attribute_values')->using(ProductVariantAttributeValue::class)->withPivot('tenant_id')->withTimestamps();
    }
}