<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleCoupon extends Model
{
    protected $fillable = [
        'sale_id',
        'coupon_id',
        'coupon_redemption_id',
        'discount_amount',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];

    // Relationships
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function redemption(): BelongsTo
    {
        return $this->belongsTo(CouponRedemption::class, 'coupon_redemption_id');
    }
}