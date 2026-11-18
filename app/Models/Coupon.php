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
        'allow_partial_use',
        'value_limit',
        'consumed_value',
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

        'allow_partial_use' => 'boolean',

        'value_limit' => 'decimal:2',

        'consumed_value' => 'decimal:2',
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

    /**
     * A value-based coupon is spent like a prepaid balance rather than used up in
     * one go: Rs 10,000 can pay for 7,000 today and the remaining 3,000 later.
     */
    public function isValueBased(): bool
    {
        return (bool) $this->allow_partial_use && $this->type === self::TYPE_FIXED;
    }

    /**
     * Total value this code may ever give.
     */
    public function valueLimit(): float
    {
        return (float) ($this->value_limit ?? $this->discount_value ?? 0);
    }

    /**
     * Balance still available on this code.
     */
    public function remainingValue(): float
    {
        if (! $this->isValueBased()) {
            return 0.0;
        }

        return max(0.0, round($this->valueLimit() - (float) $this->consumed_value, 2));
    }

    /**
     * How much of the value has been spent, 0-100.
     */
    public function valueUsedPercent(): float
    {
        $limit = $this->valueLimit();

        if ($limit <= 0) {
            return 0.0;
        }

        return min(100.0, round(((float) $this->consumed_value / $limit) * 100, 1));
    }

    /**
     * Rebuild consumed_value from the redemption rows. Used after a sale is edited or
     * voided, so the balance can never drift away from the recorded redemptions.
     */
    public function syncConsumedValue(): void
    {
        $this->consumed_value = round((float) $this->redemptions()->sum('discount_amount'), 2);
        $this->saveQuietly();
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
        if ($this->isValueBased()) {
            return $this->remainingValue() > 0.004;
        }

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