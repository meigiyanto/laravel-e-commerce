<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubCategory extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'category_id',
        'name',
        'slug',
        'image',
        'description'
    ];

    protected static function tenantParent(): ?array {
        return ['categories', 'category_id'];
    }
    
    public function tenant(): BelongsTo {
        return $this->belongsTo(Tenant::class);
    }
    
    public function category(): BelongsTo {
        return $this->belongsTo(Category::class);
    }
    
    public function products(): HasMany {
        return $this->hasMany(Product::class);
    }
}