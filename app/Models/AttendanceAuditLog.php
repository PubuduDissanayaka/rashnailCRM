<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceAuditLog extends Model
{
    protected $fillable = [
        'attendance_id',
        'user_id',
        'action',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'notes',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get the attendance record
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * Get the user who performed the action
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope: Logs for a specific attendance record
     */
    public function scopeForAttendance($query, $attendanceId)
    {
        return $query->where('attendance_id', $attendanceId);
    }

    /**
     * Scope: Logs by a specific user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Logs of a specific action
     */
    public function scopeOfAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Logs within a date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Recent logs (last N days)
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Logs with specific IP address
     */
    public function scopeWithIp($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get action label
     */
    public function getActionLabelAttribute(): string
    {
        return match($this->action) {
            'create' => 'Created',
            'update' => 'Updated',
            'delete' => 'Deleted',
            'approve' => 'Approved',
            'reject' => 'Rejected',
            'check_in' => 'Checked In',
            'check_out' => 'Checked Out',
            'break_start' => 'Break Started',
            'break_end' => 'Break Ended',
            default => ucfirst(str_replace('_', ' ', $this->action)),
        };
    }

    /**
     * Get action badge class
     */
    public function getActionBadgeAttribute(): string
    {
        return match($this->action) {
            'create' => 'bg-success',
            'update' => 'bg-info',
            'delete' => 'bg-danger',
            'approve' => 'bg-success',
            'reject' => 'bg-danger',
            'check_in' => 'bg-primary',
            'check_out' => 'bg-secondary',
            'break_start' => 'bg-warning',
            'break_end' => 'bg-warning',
            default => 'bg-light',
        };
    }

    /**
     * Get formatted created date
     */
    public function getCreatedAtFormattedAttribute(): string
    {
        return $this->created_at->format('M d, Y h:i A');
    }

    /**
     * Get time ago
     */
    public function getTimeAgoAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get changed fields
     */
    public function getChangedFieldsAttribute(): array
    {
        if (!$this->old_values || !$this->new_values) {
            return [];
        }

        $changed = [];
        
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            
            if ($oldValue != $newValue) {
                $changed[] = [
                    'field' => $key,
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        return $changed;
    }

    /**
     * Get changed fields count
     */
    public function getChangedFieldsCountAttribute(): int
    {
        return count($this->changed_fields);
    }

    /**
     * Get changed fields summary
     */
    public function getChangedFieldsSummaryAttribute(): string
    {
        $count = $this->changed_fields_count;
        
        if ($count === 0) {
            return 'No changes';
        }
        
        if ($count === 1) {
            $field = $this->changed_fields[0]['field'];
            return "Changed {$field}";
        }
        
        return "Changed {$count} fields";
    }

    // ==========================================
    // BUSINESS LOGIC METHODS
    // ==========================================

    /**
     * Log an attendance action
     */
    public static function logAction(
        $attendanceId,
        $userId,
        $action,
        $oldValues = null,
        $newValues = null,
        $ipAddress = null,
        $userAgent = null,
        $notes = null
    ): self {
        return static::create([
            'attendance_id' => $attendanceId,
            'user_id' => $userId,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'notes' => $notes,
        ]);
    }

    /**
     * Log attendance creation
     */
    public static function logCreation($attendance, $userId = null): self
    {
        return static::logAction(
            $attendance->id,
            $userId ?? auth()->id(),
            'create',
            null,
            $attendance->toArray()
        );
    }

    /**
     * Log attendance update
     */
    public static function logUpdate($attendance, $original, $userId = null): self
    {
        $changes = $attendance->getChanges();
        
        if (empty($changes)) {
            // No actual changes, don't log
            return new static();
        }

        $oldValues = array_intersect_key($original, $changes);
        $newValues = $changes;

        return static::logAction(
            $attendance->id,
            $userId ?? auth()->id(),
            'update',
            $oldValues,
            $newValues
        );
    }

    /**
     * Log attendance deletion
     */
    public static function logDeletion($attendance, $userId = null): self
    {
        return static::logAction(
            $attendance->id,
            $userId ?? auth()->id(),
            'delete',
            $attendance->toArray(),
            null
        );
    }

    /**
     * Log check-in
     */
    public static function logCheckIn($attendance, $userId = null): self
    {
        return static::logAction(
            $attendance->id,
            $userId ?? $attendance->user_id,
            'check_in',
            null,
            ['check_in' => $attendance->check_in]
        );
    }

    /**
     * Log check-out
     */
    public static function logCheckOut($attendance, $userId = null): self
    {
        return static::logAction(
            $attendance->id,
            $userId ?? $attendance->user_id,
            'check_out',
            null,
            ['check_out' => $attendance->check_out, 'hours_worked' => $attendance->hours_worked]
        );
    }

    /**
     * Log approval
     */
    public static function logApproval($attendance, $userId = null): self
    {
        return static::logAction(
            $attendance->id,
            $userId ?? auth()->id(),
            'approve',
            ['is_approved' => false],
            ['is_approved' => true, 'approved_by' => $userId ?? auth()->id(), 'approved_at' => now()]
        );
    }

    /**
     * Log rejection
     */
    public static function logRejection($attendance, $userId = null, $reason = null): self
    {
        return static::logAction(
            $attendance->id,
            $userId ?? auth()->id(),
            'reject',
            ['is_approved' => true],
            ['is_approved' => false],
            null,
            null,
            $reason
        );
    }

    /**
     * Get audit trail for an attendance record
     */
    public static function getAuditTrail($attendanceId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('attendance_id', $attendanceId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get recent activity for a user
     */
    public static function getUserActivity($userId, $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('user_id', $userId)
            ->with('attendance')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get system-wide audit logs
     */
    public static function getSystemAuditLogs($filters = [], $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        $query = static::with(['attendance', 'user']);
        
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }
        
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }
        
        if (!empty($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }
        
        return $query->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Clean up old audit logs
     */
    public static function cleanupOldLogs($days = 90): int
    {
        $cutoffDate = now()->subDays($days);
        
        return static::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Export audit logs to array
     */
    public static function exportLogs($logs): array
    {
        return $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'attendance_id' => $log->attendance_id,
                'user_name' => $log->user ? $log->user->name : 'System',
                'user_email' => $log->user ? $log->user->email : null,
                'action' => $log->action,
                'action_label' => $log->action_label,
                'changed_fields' => $log->changed_fields,
                'changed_fields_summary' => $log->changed_fields_summary,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'notes' => $log->notes,
                'created_at' => $log->created_at->toISOString(),
                'created_at_formatted' => $log->created_at_formatted,
                'time_ago' => $log->time_ago,
            ];
        })->toArray();
    }
}