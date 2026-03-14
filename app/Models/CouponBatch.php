<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CouponBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'pattern',
        'count',
        'generated_count',
        'status',
        'settings',
    ];

    protected $casts = [
        'count' => 'integer',
        'generated_count' => 'integer',
        'settings' => 'array',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_GENERATING = 'generating';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    // Relationships
    public function coupons(): HasMany
    {
        return $this->hasMany(Coupon::class, 'batch_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // Helper methods
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function remainingToGenerate(): int
    {
        return max(0, $this->count - $this->generated_count);
    }

    public function progressPercentage(): float
    {
        if ($this->count === 0) {
            return 0;
        }
        return ($this->generated_count / $this->count) * 100;
    }
}