<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'discount_value',
        'max_discount_amount',
        'minimum_purchase_amount',
        'start_date',
        'end_date',
        'timezone',
        'total_usage_limit',
        'per_customer_limit',
        'stackable',
        'active',
        'location_restriction_type',
        'customer_eligibility_type',
        'product_restriction_type',
        'metadata',
        'batch_id',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'minimum_purchase_amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'total_usage_limit' => 'integer',
        'per_customer_limit' => 'integer',
        'stackable' => 'boolean',
        'active' => 'boolean',
        'metadata' => 'array',
    ];

    protected $attributes = [
        'minimum_purchase_amount' => 0,
        'per_customer_limit' => 1,
        'active' => true,
        'stackable' => false,
    ];

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';
    public const TYPE_BOGO = 'bogo';
    public const TYPE_FREE_SHIPPING = 'free_shipping';
    public const TYPE_TIERED = 'tiered';

    public const LOCATION_RESTRICTION_ALL = 'all';
    public const LOCATION_RESTRICTION_SPECIFIC = 'specific';

    public const CUSTOMER_ELIGIBILITY_ALL = 'all';
    public const CUSTOMER_ELIGIBILITY_NEW = 'new';
    public const CUSTOMER_ELIGIBILITY_EXISTING = 'existing';
    public const CUSTOMER_ELIGIBILITY_GROUPS = 'groups';

    public const PRODUCT_RESTRICTION_ALL = 'all';
    public const PRODUCT_RESTRICTION_SPECIFIC = 'specific';
    public const PRODUCT_RESTRICTION_CATEGORIES = 'categories';

    // Relationships
    public function batch(): BelongsTo
    {
        return $this->belongsTo(CouponBatch::class, 'batch_id');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function customerGroups(): BelongsToMany
    {
        return $this->belongsToMany(CustomerGroup::class, 'coupon_customer_groups');
    }

    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'coupon_locations');
    }

    public function products(): MorphToMany
    {
        return $this->morphToMany(Service::class, 'product', 'coupon_products', 'coupon_id', 'product_id')
            ->withPivot('restriction_type')
            ->withTimestamps();
    }

    public function servicePackages(): MorphToMany
    {
        return $this->morphToMany(ServicePackage::class, 'product', 'coupon_products', 'coupon_id', 'product_id')
            ->withPivot('restriction_type')
            ->withTimestamps();
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(ServicePackageCategory::class, 'categorizable', 'coupon_categories', 'coupon_id', 'category_id')
            ->withTimestamps();
    }

    public function saleCoupons(): HasMany
    {
        return $this->hasMany(SaleCoupon::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true)
            ->where('start_date', '<=', now())
            ->where(function ($q) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', now());
            });
    }

    public function scopeExpired($query)
    {
        return $query->where('active', true)
            ->whereNotNull('end_date')
            ->where('end_date', '<', now());
    }

    public function scopeValidForCustomer($query, Customer $customer)
    {
        // This is a simplistic scope; actual validation is done in service layer
        return $query->active();
    }

    // Helper methods
    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function isActive(): bool
    {
        return $this->active && !$this->isExpired() && $this->start_date->isPast();
    }

    public function remainingUses(): ?int
    {
        if ($this->total_usage_limit === null) {
            return null;
        }
        return max(0, $this->total_usage_limit - $this->redemptions()->count());
    }

    public function hasRemainingUses(): bool
    {
        return $this->remainingUses() === null || $this->remainingUses() > 0;
    }

    public function customerUsageCount(Customer $customer): int
    {
        return $this->redemptions()->where('customer_id', $customer->id)->count();
    }

    public function canBeUsedByCustomer(Customer $customer): bool
    {
        if ($this->per_customer_limit === null) {
            return true;
        }
        return $this->customerUsageCount($customer) < $this->per_customer_limit;
    }

    // Accessors for view compatibility (used in index and show views)
    public function getDiscountPercentageAttribute()
    {
        return $this->discount_value;
    }

    public function getValidFromAttribute()
    {
        return $this->start_date;
    }

    public function getValidUntilAttribute()
    {
        return $this->end_date;
    }

    public function getUsageCountAttribute()
    {
        return $this->redemptions()->count();
    }

    public function getUsageLimitAttribute()
    {
        return $this->total_usage_limit;
    }
}