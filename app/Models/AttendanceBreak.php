<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AttendanceBreak extends Model
{
    protected $fillable = [
        'attendance_id',
        'break_type',
        'break_start',
        'break_end',
        'duration_minutes',
        'is_paid',
        'notes',
    ];

    protected $casts = [
        'break_start' => 'datetime:H:i:s',
        'break_end' => 'datetime:H:i:s',
        'duration_minutes' => 'integer',
        'is_paid' => 'boolean',
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

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope: Breaks of a specific type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('break_type', $type);
    }

    /**
     * Scope: Paid breaks only
     */
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }

    /**
     * Scope: Unpaid breaks only
     */
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }

    /**
     * Scope: Completed breaks (has break_end)
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('break_end');
    }

    /**
     * Scope: Active breaks (has break_start but no break_end)
     */
    public function scopeActive($query)
    {
        return $query->whereNotNull('break_start')->whereNull('break_end');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get formatted break start time
     */
    public function getBreakStartFormattedAttribute(): string
    {
        return $this->break_start ? $this->break_start->format('h:i A') : '';
    }

    /**
     * Get formatted break end time
     */
    public function getBreakEndFormattedAttribute(): ?string
    {
        return $this->break_end ? $this->break_end->format('h:i A') : null;
    }

    /**
     * Get break type label
     */
    public function getBreakTypeLabelAttribute(): string
    {
        return match($this->break_type) {
            'lunch' => 'Lunch Break',
            'coffee' => 'Coffee Break',
            'personal' => 'Personal Break',
            'meeting' => 'Meeting',
            'other' => 'Other Break',
            default => ucfirst($this->break_type),
        };
    }

    /**
     * Get break status
     */
    public function getStatusAttribute(): string
    {
        if ($this->break_start && !$this->break_end) {
            return 'active';
        }
        
        if ($this->break_start && $this->break_end) {
            return 'completed';
        }
        
        return 'pending';
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Active',
            'completed' => 'Completed',
            'pending' => 'Pending',
            default => 'Unknown',
        };
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'active' => 'bg-warning',
            'completed' => 'bg-success',
            'pending' => 'bg-secondary',
            default => 'bg-light',
        };
    }

    /**
     * Get break duration in hours
     */
    public function getDurationHoursAttribute(): float
    {
        if (!$this->duration_minutes) {
            return 0;
        }
        
        return round($this->duration_minutes / 60, 2);
    }

    /**
     * Get formatted duration
     */
    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_minutes) {
            return '0 min';
        }
        
        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;
        
        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        return "{$minutes}m";
    }

    // ==========================================
    // BUSINESS LOGIC METHODS
    // ==========================================

    /**
     * Start break
     */
    public function startBreak($time = null): void
    {
        $breakStart = $time ? Carbon::parse($time) : now();
        
        $this->update([
            'break_start' => $breakStart,
            'break_end' => null,
            'duration_minutes' => null,
        ]);
    }

    /**
     * End break
     */
    public function endBreak($time = null): void
    {
        if (!$this->break_start) {
            return;
        }
        
        $breakEnd = $time ? Carbon::parse($time) : now();
        $duration = $this->break_start->diffInMinutes($breakEnd);
        
        $this->update([
            'break_end' => $breakEnd,
            'duration_minutes' => $duration,
        ]);
    }

    /**
     * Calculate break duration
     */
    public function calculateDuration(): ?int
    {
        if (!$this->break_start || !$this->break_end) {
            return null;
        }
        
        return $this->break_start->diffInMinutes($this->break_end);
    }

    /**
     * Check if break is currently active
     */
    public function isActive(): bool
    {
        return $this->break_start && !$this->break_end;
    }

    /**
     * Check if break is completed
     */
    public function isCompleted(): bool
    {
        return $this->break_start && $this->break_end;
    }

    /**
     * Get break duration up to now (for active breaks)
     */
    public function getCurrentDuration(): int
    {
        if (!$this->break_start) {
            return 0;
        }
        
        if ($this->break_end) {
            return $this->break_start->diffInMinutes($this->break_end);
        }
        
        return $this->break_start->diffInMinutes(now());
    }

    /**
     * Mark break as paid
     */
    public function markAsPaid(): void
    {
        $this->update(['is_paid' => true]);
    }

    /**
     * Mark break as unpaid
     */
    public function markAsUnpaid(): void
    {
        $this->update(['is_paid' => false]);
    }

    /**
     * Check if break exceeds maximum allowed duration
     */
    public function exceedsMaximumDuration($maxMinutes = 60): bool
    {
        $duration = $this->calculateDuration();
        
        if (!$duration) {
            return false;
        }
        
        return $duration > $maxMinutes;
    }

    /**
     * Get all breaks for an attendance record
     */
    public static function getBreaksForAttendance($attendanceId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('attendance_id', $attendanceId)
            ->orderBy('break_start')
            ->get();
    }

    /**
     * Get total break duration for an attendance record
     */
    public static function getTotalBreakDuration($attendanceId): int
    {
        return static::where('attendance_id', $attendanceId)
            ->whereNotNull('duration_minutes')
            ->sum('duration_minutes');
    }

    /**
     * Get active break for an attendance record
     */
    public static function getActiveBreak($attendanceId): ?self
    {
        return static::where('attendance_id', $attendanceId)
            ->whereNotNull('break_start')
            ->whereNull('break_end')
            ->first();
    }
}