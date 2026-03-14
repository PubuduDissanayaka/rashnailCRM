<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveRequest extends Model
{
    protected $fillable = [
        'user_id',
        'leave_type',
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'days_count' => 'integer',
        'approved_at' => 'datetime',
        'status' => 'string',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function($q2) use ($startDate, $endDate) {
                  $q2->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get formatted status for UI
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'pending' => 'bg-warning',
            default => 'bg-secondary',
        };
    }

    /**
     * Get human-readable status label
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            'pending' => 'Pending Approval',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get leave type label
     */
    public function getTypeLabelAttribute()
    {
        return match($this->leave_type) {
            'sick' => 'Sick Leave',
            'vacation' => 'Vacation Leave',
            'personal' => 'Personal Leave',
            'unpaid' => 'Unpaid Leave',
            'emergency' => 'Emergency Leave',
            default => ucfirst($this->leave_type),
        };
    }

    /**
     * Get formatted date range
     */
    public function getDateRangeAttribute()
    {
        return $this->start_date->format('M d, Y') . ' - ' . $this->end_date->format('M d, Y');
    }

    /**
     * Check if request overlaps with today
     */
    public function getIsActiveTodayAttribute()
    {
        $today = today();
        return $this->start_date->lte($today) && $this->end_date->gte($today);
    }

    // ==========================================
    // BUSINESS LOGIC METHODS
    // ==========================================

    /**
     * Approve the leave request
     */
    public function approve($approverId, $notes = null)
    {
        $this->status = 'approved';
        $this->approved_by = $approverId;
        $this->approved_at = now();
        
        if ($notes) {
            $this->rejection_reason = $notes;
        }
        
        $this->save();

        // Update leave balance
        $this->updateLeaveBalance();

        return $this;
    }

    /**
     * Reject the leave request
     */
    public function reject($approverId, $reason = null)
    {
        $this->status = 'rejected';
        $this->approved_by = $approverId;
        $this->approved_at = now();
        $this->rejection_reason = $reason;
        $this->save();

        return $this;
    }

    /**
     * Update leave balance after approval
     */
    protected function updateLeaveBalance()
    {
        // Find or create leave balance
        $leaveBalance = LeaveBalance::firstOrCreate([
            'user_id' => $this->user_id,
            'year' => $this->start_date->year,
            'leave_type' => $this->leave_type,
        ], [
            'total_days' => 0,
            'used_days' => 0,
            'remaining_days' => 0,
        ]);

        // Update balance
        $leaveBalance->used_days += $this->days_count;
        $leaveBalance->remaining_days = $leaveBalance->total_days - $leaveBalance->used_days;
        $leaveBalance->save();
    }

    /**
     * Cancel the leave request (only before the start date)
     */
    public function cancel()
    {
        if ($this->start_date->isPast()) {
            throw new \Exception('Cannot cancel leave request that has already started');
        }

        $this->status = 'cancelled';
        $this->save();

        // Decrease leave balance if this was approved
        if ($this->status === 'approved') {
            $this->reduceLeaveBalance();
        }

        return $this;
    }

    /**
     * Reduce leave balance when cancelling approved request
     */
    protected function reduceLeaveBalance()
    {
        $leaveBalance = LeaveBalance::where([
            'user_id' => $this->user_id,
            'year' => $this->start_date->year,
            'leave_type' => $this->leave_type,
        ])->first();

        if ($leaveBalance) {
            $leaveBalance->used_days = max(0, $leaveBalance->used_days - $this->days_count);
            $leaveBalance->remaining_days = $leaveBalance->total_days - $leaveBalance->used_days;
            $leaveBalance->save();
        }
    }

    /**
     * Calculate total days for the leave period
     */
    public static function calculateDaysCount($startDate, $endDate)
    {
        $start = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        // Include both start and end dates
        return $end->diffInDays($start) + 1;
    }

    /**
     * Approve the leave request
     */
    public function approve($approverId, $notes = null)
    {
        $this->status = 'approved';
        $this->approved_by = $approverId;
        $this->approved_at = now();
        $this->rejection_reason = $notes; // Store notes in rejection_reason field for consistency
        $this->save();

        // Update leave balance
        $this->updateLeaveBalance();

        return $this;
    }

    /**
     * Reject the leave request
     */
    public function reject($approverId, $reason = null)
    {
        $this->status = 'rejected';
        $this->approved_by = $approverId;
        $this->approved_at = now();
        $this->rejection_reason = $reason;
        $this->save();

        return $this;
    }

    /**
     * Update leave balance after approval
     */
    protected function updateLeaveBalance()
    {
        if (in_array($this->leave_type, ['unpaid', 'emergency'])) {
            // Skip balance update for unpaid/emergency leaves
            return;
        }

        // Find or create leave balance
        $leaveBalance = LeaveBalance::firstOrCreate([
            'user_id' => $this->user_id,
            'year' => $this->start_date->year,
            'leave_type' => $this->leave_type,
        ], [
            'total_days' => 0,
            'used_days' => 0,
            'remaining_days' => 0,
        ]);

        // Update balance
        $leaveBalance->used_days += $this->days_count;
        $leaveBalance->remaining_days = max(0, $leaveBalance->total_days - $leaveBalance->used_days);
        $leaveBalance->save();
    }
}