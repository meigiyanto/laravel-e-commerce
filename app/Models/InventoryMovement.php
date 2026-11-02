<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use BelongsToTenant;
    public const UPDATED_AT = null;
    protected $fillable = ['tenant_id',
        'product_variant_id',
        'type',
        'quantity',
        'reference_type',
        'reference_id',
        'idempotency_key',
        'note',
        'created_by'];
    protected static function tenantParent(): ?array {
        return ['product_variants',
            'product_variant_id'];
    }
    protected function casts(): array {
        return ['quantity' => 'integer',
            'reference_id' => 'integer',
            'created_by' => 'integer',
            'created_at' => 'datetime'];
    }
    public function variant(): BelongsTo {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function tenant(): BelongsTo {
        return $this->belongsTo(Tenant::class);
    }
}