<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashDrawerSession extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'opening_amount', 'closing_amount', 'expected_amount', 
        'difference', 'opened_at', 'closed_at', 'opening_notes', 'closing_notes', 'status'
    ];

    protected $casts = [
        'opening_amount' => 'decimal:2',
        'closing_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'difference' => 'decimal:2',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Accessors
    public function getIsOpenAttribute()
    {
        return $this->status === 'open';
    }

    public function getIsClosedAttribute()
    {
        return $this->status === 'closed';
    }

    // Business Methods
    public function openSession(float $openingAmount, ?string $notes = null)
    {
        $this->user_id = auth()->id();
        $this->opening_amount = $openingAmount;
        $this->opened_at = now();
        $this->opening_notes = $notes;
        $this->status = 'open';
        $this->save();

        return $this;
    }

    public function closeSession(float $closingAmount, ?string $notes = null)
    {
        $this->closing_amount = $closingAmount;
        $this->expected_amount = $this->calculateExpectedAmount();
        $this->difference = $closingAmount - $this->expected_amount;
        $this->closed_at = now();
        $this->closing_notes = $notes;
        $this->status = 'closed';
        $this->save();

        return $this;
    }

    private function calculateExpectedAmount()
    {
        // Calculate expected amount based on transactions during the session
        // This would typically include sales made during the session
        // For now, returning a simple calculation
        return $this->opening_amount;
    }

    public function getDurationAttribute()
    {
        if ($this->closed_at && $this->opened_at) {
            return $this->opened_at->diff($this->closed_at)->format('%h:%I');
        }
        return null;
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (empty($session->status)) {
                $session->status = 'open';
            }
            if (empty($session->opened_at)) {
                $session->opened_at = now();
            }
        });
    }
}