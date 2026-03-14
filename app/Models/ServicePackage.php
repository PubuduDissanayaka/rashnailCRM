<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ServicePackage extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'description', 'base_price', 'discounted_price', 'discount_percentage', 'total_duration', 'is_active', 'image', 'slug', 'category_id'
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'total_duration' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(ServicePackageCategory::class, 'category_id');
    }

    public function saleItems()
    {
        return $this->morphMany(SaleItem::class, 'sellable');
    }

    public function servicePackageSales()
    {
        return $this->hasMany(ServicePackageSale::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'package_service', 'package_id', 'service_id')
                    ->withPivot(['quantity', 'sort_order'])
                    ->withTimestamps();
    }

    // Accessors
    public function getPriceAttribute()
    {
        return $this->discounted_price;
    }

    public function getFormattedPriceAttribute()
    {
        return number_format($this->discounted_price, 2);
    }

    public function canBeSold(): bool
    {
        return $this->is_active;
    }

    /**
     * Scope a query to only include active service packages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Auto-generate slug
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($package) {
            if (empty($package->slug)) {
                $package->slug = Str::slug($package->name) . '-' . Str::random(6);
            }
        });
    }

    // Route key binding
    public function getRouteKeyName()
    {
        return 'slug';
    }
}