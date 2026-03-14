<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CustomerGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'criteria',
        'is_active',
    ];

    protected $casts = [
        'criteria' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_group_members')->withTimestamps();
    }

    public function coupons(): BelongsToMany
    {
        return $this->belongsToMany(Coupon::class, 'coupon_customer_groups');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helper methods
    public function matchesCustomer(Customer $customer): bool
    {
        // Implement logic based on criteria
        if (empty($this->criteria)) {
            return true;
        }

        // Example criteria: {"min_orders": 5, "has_membership": true}
        // This is a placeholder; actual implementation depends on business rules
        return true;
    }
}