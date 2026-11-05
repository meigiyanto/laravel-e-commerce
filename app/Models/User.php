<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Support\Tenancy\TenantContext;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    public const ROLE_PLATFORM_ADMIN = 'platform_admin';

    public function activeTenantMembership(?int $tenantId = null): ?TenantMembership
    {
        $tenantId ??= app(TenantContext::class)->current()?->getKey();

        if (! $tenantId) {
            return null;
        }

        return $this->tenantMemberships()->where('tenant_id', $tenantId)->where('status', 'active')->first();
    }

    public function hasTenantRole(string ...$roles): bool
    {
        $tenantId = app(TenantContext::class)->current()?->getKey();

        if (! $tenantId) {
            return false;
        }

        if ($this->hasPlatformTenantAccess($tenantId)) {
            return true;
        }

        $membership = $this->activeTenantMembership($tenantId);

        return $membership !== null && in_array($membership->role, $roles, true);
    }

    public function hasPlatformTenantAccess(int $tenantId): bool
    {
        return $this->isPlatformAdmin() && request()->hasSession() && (int) request()->session()->get('platform_tenant_access') === $tenantId;
    }

    public function hasTenantPermission(string $permission): bool
    {
        $tenantId = app(TenantContext::class)->current()?->getKey();

        if (! $tenantId) {
            return false;
        }

        if ($this->hasPlatformTenantAccess($tenantId)) {
            return true;
        }

        $membership = $this->activeTenantMembership($tenantId);

        if (! $membership) {
            return false;
        }

        $permissions = [
            'dashboard.view' => [
                TenantMembership::ROLE_OWNER,
                TenantMembership::ROLE_ADMIN,
                TenantMembership::ROLE_PRODUCT_MANAGER,
                TenantMembership::ROLE_INVENTORY_MANAGER,
                TenantMembership::ROLE_ORDER_MANAGER,
                TenantMembership::ROLE_STAFF,
            ],
            'products.view' => [
                TenantMembership::ROLE_OWNER,
                TenantMembership::ROLE_ADMIN,
                TenantMembership::ROLE_PRODUCT_MANAGER,
                TenantMembership::ROLE_STAFF,
            ],
            'products.manage' => [
                TenantMembership::ROLE_OWNER,
                TenantMembership::ROLE_ADMIN,
                TenantMembership::ROLE_PRODUCT_MANAGER,
            ],
            'inventory.view' => [
                TenantMembership::ROLE_OWNER,
                TenantMembership::ROLE_ADMIN,
                TenantMembership::ROLE_INVENTORY_MANAGER,
                TenantMembership::ROLE_STAFF,
            ],
            'inventory.manage' => [
                TenantMembership::ROLE_OWNER,
                TenantMembership::ROLE_ADMIN,
                TenantMembership::ROLE_INVENTORY_MANAGER,
            ],
            'orders.view' => [
                TenantMembership::ROLE_OWNER,
                TenantMembership::ROLE_ADMIN,
                TenantMembership::ROLE_ORDER_MANAGER,
                TenantMembership::ROLE_STAFF,
            ],
            'orders.manage' => [
                TenantMembership::ROLE_OWNER,
                TenantMembership::ROLE_ADMIN,
                TenantMembership::ROLE_ORDER_MANAGER,
            ],
            'refunds.manage' => [
                TenantMembership::ROLE_OWNER,
                TenantMembership::ROLE_ADMIN,
                TenantMembership::ROLE_ORDER_MANAGER,
            ],
            'users.manage' => [
                TenantMembership::ROLE_OWNER,
                TenantMembership::ROLE_ADMIN,
            ],
        ];

        return in_array(
            $membership->role,
            $permissions[$permission] ?? [],
            true
        );
    }
    
    public function isPlatformAdmin(): bool 
    {
        return $this->role === self::ROLE_PLATFORM_ADMIN;
    }

    protected $fillable = [
        'name',
        'email',
        'phone',
        'birth_date',
        'avatar',
        'address',
        'city',
        'province',
        'postal_code',
        'password',
        'role',
    ];

    /**
    * Get the attributes that should be cast.
    *
    * @return array<string, string>
    */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date',
            'password' => 'hashed',
        ];
    }

    public const ROLE_ADMIN = 'admin';

    public const ROLE_STAFF = 'staff';

    public const ROLE_CUSTOMER = 'customer';

    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isCustomer(): bool
    {
        return $this->role === self::ROLE_CUSTOMER;
    }


    public function hasAnyRole(string ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function wishlistProducts(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'wishlists')->withTimestamps();
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    public function defaultAddress(): HasOne
    {
        return $this->hasOne(UserAddress::class)
        ->where('is_default', true);
    }

    public function tenantMemberships(): HasMany
    {
        return $this->hasMany(TenantMembership::class);
    }

    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(
            Tenant::class,
            'tenant_memberships'
        )->withPivot([
                'id',
                'role',
                'status',
                'invited_at',
                'accepted_at',
            ])->withTimestamps();
    }

    public function createdTenants(): HasMany
    {
        return $this->hasMany(
            Tenant::class,
            'created_by_user_id'
        );
    }
}