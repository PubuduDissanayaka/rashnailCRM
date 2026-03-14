<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponRedemption extends Model
{
    use HasFactory;

    protected $fillable = [
        'coupon_id',
        'sale_id',
        'customer_id',
        'redeemed_by_user_id',
        'discount_amount',
        'redeemed_at',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
        'redeemed_at' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function redeemedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'redeemed_by_user_id');
    }

    // Scopes
    public function scopeToday($query)
    {
        return $query->whereDate('redeemed_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('redeemed_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereBetween('redeemed_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }
}