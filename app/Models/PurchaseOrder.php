<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'po_number', 'supplier_name', 'supplier_contact', 'supplier_email', 'supplier_phone',
        'status', 'order_date', 'expected_delivery_date', 'received_date',
        'subtotal', 'tax', 'shipping', 'total',
        'tracking_number', 'invoice_number',
        'created_by', 'approved_by', 'received_by', 'delivery_location',
        'notes'
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'received_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'shipping' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Relationships
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    // Scopes
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['draft', 'pending', 'ordered']);
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    // Methods
    public function calculateTotals()
    {
        $subtotal = $this->items->sum('total_cost');
        $this->subtotal = $subtotal;
        $this->total = $subtotal + $this->tax + $this->shipping;
        $this->save();
    }

    public function markAsReceived($userId)
    {
        $this->status = 'received';
        $this->received_date = now();
        $this->received_by = $userId;
        $this->save();
    }

    public function isPending(): bool
    {
        return in_array($this->status, ['draft', 'pending', 'ordered']);
    }

    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    public function canBeEdited(): bool
    {
        return in_array($this->status, ['draft', 'pending']);
    }
}