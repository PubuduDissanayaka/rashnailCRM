<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Refund extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'refund_number', 'sale_id', 'user_id', 'refund_amount', 
        'refund_method', 'reason', 'notes', 'refund_date'
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'refund_date' => 'datetime',
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return '$' . number_format($this->refund_amount, 2);
    }

    // Business Methods
    public function processRefund()
    {
        // Additional processing logic if needed
        // For example, updating related records
        return $this;
    }

    // Scopes
    public function scopeByMethod($query, $method)
    {
        return $query->where('refund_method', $method);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('refund_date', [$startDate, $endDate]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($refund) {
            if (empty($refund->refund_number)) {
                $refund->refund_number = self::generateRefundNumber();
            }
            if (empty($refund->refund_date)) {
                $refund->refund_date = now();
            }
        });
    }

    private static function generateRefundNumber()
    {
        $lastRefund = self::latest('id')->first();
        $nextNumber = $lastRefund ? ($lastRefund->id + 1) : 1;
        return sprintf('REF-%s-%05d', date('Y'), $nextNumber);
    }
}