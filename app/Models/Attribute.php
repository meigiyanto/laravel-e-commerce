<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use BelongsToTenant;
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'type',
        'is_variant',
        'sort_order'
    ];
    
    public function tenant(): BelongsTo {
        return $this->belongsTo(Tenant::class);
    }
    
    public function values(): HasMany {
        return $this->hasMany(AttributeValue::class);
    }
}