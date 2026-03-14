<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $fillable = [
        'user_id',
        'year',
        'leave_type',
        'total_days',
        'used_days',
        'remaining_days',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_days' => 'integer',
        'used_days' => 'integer',
        'remaining_days' => 'integer',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope: Filter by user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by year
     */
    public function scopeForYear($query, $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope: Filter by leave type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('leave_type', $type);
    }

    /**
     * Scope: Available leave balances (remaining > 0)
     */
    public function scopeWithRemaining($query)
    {
        return $query->where('remaining_days', '>', 0);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get human-readable leave type
     */
    public function getLeaveTypeLabelAttribute()
    {
        return match($this->leave_type) {
            'sick' => 'Sick Leave',
            'vacation' => 'Vacation Leave',
            'personal' => 'Personal Leave',
            default => ucfirst($this->leave_type) . ' Leave',
        };
    }

    /**
     * Get usage percentage
     */
    public function getUsagePercentageAttribute()
    {
        if ($this->total_days == 0) {
            return 0;
        }

        return round(($this->used_days / $this->total_days) * 100, 2);
    }

    /**
     * Get remaining percentage
     */
    public function getRemainingPercentageAttribute()
    {
        if ($this->total_days == 0) {
            return 0;
        }

        return round(($this->remaining_days / $this->total_days) * 100, 2);
    }

    /**
     * Check if leave balance is exhausted
     */
    public function getIsExhaustedAttribute()
    {
        return $this->remaining_days <= 0;
    }

    /**
     * Check if leave balance has available days
     */
    public function getIsAvailableAttribute()
    {
        return $this->remaining_days > 0;
    }

    // ==========================================
    // BUSINESS LOGIC METHODS
    // ==========================================

    /**
     * Use specified number of leave days
     */
    public function useDays($days)
    {
        if ($days > $this->remaining_days) {
            throw new \Exception('Not enough leave days available');
        }

        $this->used_days += $days;
        $this->remaining_days -= $days;
        $this->save();

        return $this;
    }

    /**
     * Add days to the balance (e.g., for carry-over or adjustments)
     */
    public function addDays($days)
    {
        $this->total_days += $days;
        $this->remaining_days += $days;
        $this->save();

        return $this;
    }

    /**
     * Remove days from used balance (e.g., when leave request is cancelled)
     */
    public function returnDays($days)
    {
        $this->used_days = max(0, $this->used_days - $days);
        $this->remaining_days += $days;
        $this->remaining_days = min($this->total_days, $this->remaining_days); // Ensure it doesn't exceed total
        $this->save();

        return $this;
    }

    /**
     * Reset the year's leave balance (admin function)
     * Note: This should be called carefully, typically at year start
     */
    public function resetYearlyBalance($newAllocation = null)
    {
        $allocation = $newAllocation ?? $this->total_days; // Preserve existing allocation unless specified otherwise
        
        $this->total_days = $allocation;
        $this->used_days = 0;
        $this->remaining_days = $allocation;
        $this->save();

        return $this;
    }

    /**
     * Update balance after leave approval
     */
    public function deductLeaveRequest($daysRequested)
    {
        if ($daysRequested > $this->remaining_days) {
            throw new \Exception('Insufficient leave balance');
        }

        $this->used_days += $daysRequested;
        $this->remaining_days -= $daysRequested;

        // Ensure remaining days doesn't go negative
        $this->remaining_days = max(0, $this->remaining_days);

        $this->save();

        return $this;
    }

    /**
     * Restore balance after leave cancellation
     */
    public function restoreLeaveDays($daysCancelled)
    {
        $this->used_days = max(0, $this->used_days - $daysCancelled);
        $this->remaining_days += $daysCancelled;
        
        // Ensure remaining days doesn't exceed total
        $this->remaining_days = min($this->total_days, $this->remaining_days);

        $this->save();

        return $this;
    }

    // ==========================================
    // STATIC METHODS
    // ==========================================

    /**
     * Get or create user's leave balance for a specific type and year
     */
    public static function getOrCreateBalance($userId, $year, $leaveType, $initialAllocation = 0)
    {
        $balance = static::firstOrCreate([
            'user_id' => $userId,
            'year' => $year,
            'leave_type' => $leaveType,
        ], [
            'total_days' => $initialAllocation,
            'used_days' => 0,
            'remaining_days' => $initialAllocation,
        ]);

        // Ensure remaining days matches if it was created with different total
        if ($balance->total_days != $balance->remaining_days + $balance->used_days) {
            $balance->remaining_days = $balance->total_days - $balance->used_days;
            $balance->save();
        }

        return $balance;
    }

    /**
     * Get user's total available leave by type for current year
     */
    public static function getUserBalanceForType($userId, $leaveType)
    {
        $currentYear = now()->year;
        
        return static::where('user_id', $userId)
                       ->where('year', $currentYear)
                       ->where('leave_type', $leaveType)
                       ->first();
    }

    /**
     * Get all balances for a user for the current year
     */
    public static function getUserBalances($userId)
    {
        $currentYear = now()->year;
        
        return static::where('user_id', $userId)
                       ->where('year', $currentYear)
                       ->get();
    }

    /**
     * Get total available leave (all types) for user
     */
    public static function getTotalAvailableForUser($userId)
    {
        $currentYear = now()->year;
        
        return static::where('user_id', $userId)
                       ->where('year', $currentYear)
                       ->sum('remaining_days');
    }
}