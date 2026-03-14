<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Supply extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Boot the model and set up slug generation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($supply) {
            if (empty($supply->slug)) {
                $supply->slug = \Illuminate\Support\Str::slug($supply->name . '-' . \Illuminate\Support\Str::random(4));
            }
        });

        static::updating(function ($supply) {
            if ($supply->isDirty('name') && empty($supply->slug)) {
                $supply->slug = \Illuminate\Support\Str::slug($supply->name . '-' . $supply->id . '-' . \Illuminate\Support\Str::random(4));
            }
        });
    }

    protected $fillable = [
        'name', 'slug', 'description', 'sku', 'barcode',
        'category_id', 'brand', 'supplier_name',
        'unit_type', 'unit_size', 'min_stock_level', 'max_stock_level', 'current_stock',
        'unit_cost', 'retail_value',
        'is_active', 'track_expiry', 'track_batch', 'usage_per_service',
        'location', 'storage_location',
        'metadata', 'notes'
    ];

    protected $casts = [
        'unit_size' => 'decimal:2',
        'min_stock_level' => 'decimal:2',
        'max_stock_level' => 'decimal:2',
        'current_stock' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'retail_value' => 'decimal:2',
        'is_active' => 'boolean',
        'track_expiry' => 'boolean',
        'track_batch' => 'boolean',
        'metadata' => 'array',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(SupplyCategory::class, 'category_id');
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(SupplyStockMovement::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(SupplyUsageLog::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(SupplyAlert::class);
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_supplies')
            ->withPivot('quantity_required', 'is_optional')
            ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('current_stock', '<=', 'min_stock_level')
            ->where('current_stock', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('current_stock', '<=', 0);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // Methods
    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock_level && $this->current_stock > 0;
    }

    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }

    public function addStock($quantity, $reference, $notes = null)
    {
        // Implementation will be added later when SupplyStockMovement model is ready
        // This is a placeholder
        $this->current_stock += $quantity;
        $this->save();
    }

    public function removeStock($quantity, $reference, $notes = null)
    {
        // Implementation will be added later
        $this->current_stock -= $quantity;
        $this->save();
    }

    public function adjustStock($quantity, $reason, $notes = null)
    {
        // Implementation will be added later
        $this->current_stock = $quantity;
        $this->save();
    }

    public function getCurrentValue(): float
    {
        return $this->current_stock * $this->unit_cost;
    }

    public function getStockPercentage(): float
    {
        if (!$this->max_stock_level) {
            return 0;
        }
        return ($this->current_stock / $this->max_stock_level) * 100;
    }
}