<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupplyStockMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'supply_id', 'movement_type', 'quantity', 'quantity_before', 'quantity_after',
        'reference_type', 'reference_id', 'reference_number',
        'unit_cost', 'total_cost',
        'batch_number', 'expiry_date',
        'from_location', 'to_location',
        'created_by', 'notes', 'movement_date'
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'quantity_before' => 'decimal:2',
        'quantity_after' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
        'movement_date' => 'datetime',
    ];

    // Relationships
    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeBySupply($query, $supplyId)
    {
        return $query->where('supply_id', $supplyId);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('movement_date', [$from, $to]);
    }
}