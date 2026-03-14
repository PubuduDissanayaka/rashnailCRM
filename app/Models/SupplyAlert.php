<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplyAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'supply_id', 'alert_type', 'severity', 'message',
        'is_resolved', 'resolved_at', 'resolved_by',
        'current_stock', 'min_stock_level', 'expiry_date'
    ];

    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
        'current_stock' => 'decimal:2',
        'min_stock_level' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    // Relationships
    public function supply(): BelongsTo
    {
        return $this->belongsTo(Supply::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeBySeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('alert_type', $type);
    }

    // Methods
    public function resolve($userId)
    {
        $this->is_resolved = true;
        $this->resolved_at = now();
        $this->resolved_by = $userId;
        $this->save();
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }
}