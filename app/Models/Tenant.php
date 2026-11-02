<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = ['name',
        'slug',
        'status',
        'created_by_user_id'];

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
    public function memberships(): HasMany {
        return $this->hasMany(TenantMembership::class);
    }
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_memberships')->withPivot(['id', 'role', 'status', 'invited_at', 'accepted_at'])->withTimestamps();
    }
    public function store(): HasOne {
        return $this->hasOne(Store::class);
    }
}