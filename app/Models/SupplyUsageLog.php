<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'supply_id', 'appointment_id', 'service_id',
        'quantity_used', 'unit_cost', 'total_cost',
        'used_by', 'customer_id', 'batch_number',
        'notes', 'used_at'
    ];

    protected $casts = [
        'quantity_used' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'used_at' => 'datetime',
    ];

    // Relationships
    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }
}