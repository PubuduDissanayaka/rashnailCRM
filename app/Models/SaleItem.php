<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id', 'sellable_type', 'sellable_id', 'item_name', 'item_code',
        'quantity', 'unit_price', 'discount_amount', 'tax_amount', 'line_total', 'notes'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function sellable()
    {
        return $this->morphTo();
    }

    public function servicePackageSales()
    {
        return $this->hasMany(\App\Models\ServicePackageSale::class, 'sale_item_id');
    }

    // Business Methods
    public function calculateLineTotal()
    {
        $this->line_total = ($this->unit_price * $this->quantity) - $this->discount_amount + $this->tax_amount;
        $this->save();
        return $this;
    }

    // Snapshot service/service package details when creating
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if ($item->sellable) {
                $item->item_name = $item->sellable->name;
                if ($item->sellable instanceof ServicePackage) {
                    $item->item_code = $item->sellable->slug;
                }
            }
        });
    }
}