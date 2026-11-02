<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use BelongsToTenant;
    
    protected $fillable = ['tenant_id',
        'product_variant_id',
        'quantity',
        'reserved_quantity',
        'reorder_level'];
    
    protected static function tenantParent(): ?array {
        return ['product_variants',
            'product_variant_id'];
    }
    protected function casts(): array {
        return ['quantity' => 'integer',
            'reserved_quantity' => 'integer',
            'reorder_level' => 'integer'];
    }
    public function variant(): BelongsTo {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
    public function tenant(): BelongsTo {
        return $this->belongsTo(Tenant::class);
    }
    public function getAvailableQuantityAttribute(): int {
        return max(0, $this->quantity - $this->reserved_quantity);
    }
}