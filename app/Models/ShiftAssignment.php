<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ShiftAssignment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'shift_id',
        'effective_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the user assigned to this shift
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the shift details
     */
    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope: Only active assignments
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Assignments for a specific user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Assignments for a specific shift
     */
    public function scopeForShift($query, $shiftId)
    {
        return $query->where('shift_id', $shiftId);
    }

    /**
     * Scope: Current assignments (effective date <= today and (end_date is null or end_date >= today))
     */
    public function scopeCurrent($query)
    {
        $today = Carbon::today();
        
        return $query->where('effective_date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $today);
            })
            ->where('is_active', true);
    }

    /**
     * Scope: Future assignments (effective_date > today)
     */
    public function scopeFuture($query)
    {
        return $query->where('effective_date', '>', Carbon::today());
    }

    /**
     * Scope: Past assignments (end_date < today or (end_date is null and effective_date < today and is_active = false))
     */
    public function scopePast($query)
    {
        $today = Carbon::today();
        
        return $query->where(function ($q) use ($today) {
            $q->where('end_date', '<', $today)
              ->orWhere(function ($q2) use ($today) {
                  $q2->whereNull('end_date')
                     ->where('effective_date', '<', $today)
                     ->where('is_active', false);
              });
        });
    }

    /**
     * Scope: Assignments effective on a specific date
     */
    public function scopeEffectiveOn($query, $date)
    {
        $date = Carbon::parse($date);
        
        return $query->where('effective_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            });
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get assignment status
     */
    public function getStatusAttribute(): string
    {
        $today = Carbon::today();
        
        if ($this->effective_date > $today) {
            return 'future';
        }
        
        if ($this->end_date && $this->end_date < $today) {
            return 'past';
        }
        
        if (!$this->is_active) {
            return 'inactive';
        }
        
        return 'active';
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'future' => 'Future',
            'past' => 'Past',
            'inactive' => 'Inactive',
            'active' => 'Active',
            default => 'Unknown',
        };
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'future' => 'bg-info',
            'past' => 'bg-secondary',
            'inactive' => 'bg-warning',
            'active' => 'bg-success',
            default => 'bg-light',
        };
    }

    /**
     * Get formatted effective date
     */
    public function getEffectiveDateFormattedAttribute(): string
    {
        return $this->effective_date->format('M d, Y');
    }

    /**
     * Get formatted end date
     */
    public function getEndDateFormattedAttribute(): ?string
    {
        return $this->end_date ? $this->end_date->format('M d, Y') : null;
    }

    /**
     * Get assignment duration in days
     */
    public function getDurationDaysAttribute(): ?int
    {
        if (!$this->end_date) {
            return null;
        }
        
        return $this->effective_date->diffInDays($this->end_date);
    }

    // ==========================================
    // BUSINESS LOGIC METHODS
    // ==========================================

    /**
     * Check if assignment is active on a specific date
     */
    public function isActiveOn($date): bool
    {
        $date = Carbon::parse($date);
        
        if (!$this->is_active) {
            return false;
        }
        
        if ($this->effective_date > $date) {
            return false;
        }
        
        if ($this->end_date && $this->end_date < $date) {
            return false;
        }
        
        return true;
    }

    /**
     * Activate assignment
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate assignment
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * End assignment on a specific date
     */
    public function endOn($date): void
    {
        $this->update(['end_date' => Carbon::parse($date), 'is_active' => false]);
    }

    /**
     * Extend assignment to a new end date
     */
    public function extendTo($date): void
    {
        $this->update(['end_date' => Carbon::parse($date)]);
    }

    /**
     * Get user's shift for a specific date
     */
    public static function getUserShiftForDate($userId, $date): ?self
    {
        $date = Carbon::parse($date);
        
        return static::where('user_id', $userId)
            ->where('effective_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            })
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get all active assignments for a user
     */
    public static function getUserActiveAssignments($userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('user_id', $userId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', Carbon::today());
            })
            ->get();
    }
}