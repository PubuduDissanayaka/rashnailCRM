<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Carbon\Carbon;

class Shift extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'start_time',
        'end_time',
        'grace_period_minutes',
        'break_duration_minutes',
        'break_start_time',
        'overtime_threshold_minutes',
        'overtime_rate_multiplier',
        'is_active',
        'description',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'break_start_time' => 'datetime:H:i:s',
        'grace_period_minutes' => 'integer',
        'break_duration_minutes' => 'integer',
        'overtime_threshold_minutes' => 'integer',
        'overtime_rate_multiplier' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Get all shift assignments
     */
    public function shiftAssignments(): HasMany
    {
        return $this->hasMany(ShiftAssignment::class);
    }

    /**
     * Get users assigned to this shift
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'shift_assignments')
            ->using(ShiftAssignment::class)
            ->withPivot(['effective_date', 'end_date', 'is_active', 'notes'])
            ->withTimestamps();
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope: Only active shifts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Filter by code
     */
    public function scopeByCode($query, $code)
    {
        return $query->where('code', $code);
    }

    /**
     * Scope: Shifts starting after a specific time
     */
    public function scopeStartingAfter($query, $time)
    {
        return $query->whereTime('start_time', '>', $time);
    }

    /**
     * Scope: Shifts ending before a specific time
     */
    public function scopeEndingBefore($query, $time)
    {
        return $query->whereTime('end_time', '<', $time);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get formatted start time
     */
    public function getStartTimeFormattedAttribute(): string
    {
        return $this->start_time ? $this->start_time->format('h:i A') : '';
    }

    /**
     * Get formatted end time
     */
    public function getEndTimeFormattedAttribute(): string
    {
        return $this->end_time ? $this->end_time->format('h:i A') : '';
    }

    /**
     * Get formatted break start time
     */
    public function getBreakStartTimeFormattedAttribute(): ?string
    {
        return $this->break_start_time ? $this->break_start_time->format('h:i A') : null;
    }

    /**
     * Get shift duration in hours
     */
    public function getDurationHoursAttribute(): float
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }

        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        
        return $start->diffInHours($end);
    }

    /**
     * Get shift duration in minutes
     */
    public function getDurationMinutesAttribute(): int
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }

        $start = Carbon::parse($this->start_time);
        $end = Carbon::parse($this->end_time);
        
        return $start->diffInMinutes($end);
    }

    /**
     * Get net working hours (excluding break)
     */
    public function getNetWorkingHoursAttribute(): float
    {
        $totalHours = $this->duration_hours;
        $breakHours = $this->break_duration_minutes / 60;
        
        return $totalHours - $breakHours;
    }

    /**
     * Get grace period end time
     */
    public function getGracePeriodEndTimeAttribute(): Carbon
    {
        $start = Carbon::parse($this->start_time);
        return $start->addMinutes($this->grace_period_minutes);
    }

    /**
     * Get overtime start time
     */
    public function getOvertimeStartTimeAttribute(): Carbon
    {
        $end = Carbon::parse($this->end_time);
        return $end->addMinutes($this->overtime_threshold_minutes);
    }

    // ==========================================
    // BUSINESS LOGIC METHODS
    // ==========================================

    /**
     * Check if a time is within shift hours
     */
    public function isWithinShiftHours($time): bool
    {
        $checkTime = Carbon::parse($time);
        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);

        return $checkTime->between($startTime, $endTime);
    }

    /**
     * Check if check-in time is late
     */
    public function isLateCheckIn($checkInTime): bool
    {
        $checkTime = Carbon::parse($checkInTime);
        $graceEnd = $this->grace_period_end_time;

        return $checkTime->greaterThan($graceEnd);
    }

    /**
     * Check if check-out time is early departure
     */
    public function isEarlyDeparture($checkOutTime): bool
    {
        $checkTime = Carbon::parse($checkOutTime);
        $endTime = Carbon::parse($this->end_time);

        return $checkTime->lessThan($endTime);
    }

    /**
     * Calculate overtime minutes
     */
    public function calculateOvertimeMinutes($checkOutTime): int
    {
        $checkTime = Carbon::parse($checkOutTime);
        $overtimeStart = $this->overtime_start_time;

        if ($checkTime->lessThanOrEqualTo($overtimeStart)) {
            return 0;
        }

        return $overtimeStart->diffInMinutes($checkTime);
    }

    /**
     * Calculate late arrival minutes
     */
    public function calculateLateArrivalMinutes($checkInTime): int
    {
        $checkTime = Carbon::parse($checkInTime);
        $graceEnd = $this->grace_period_end_time;

        if ($checkTime->lessThanOrEqualTo($graceEnd)) {
            return 0;
        }

        return $graceEnd->diffInMinutes($checkTime);
    }

    /**
     * Calculate early departure minutes
     */
    public function calculateEarlyDepartureMinutes($checkOutTime): int
    {
        $checkTime = Carbon::parse($checkOutTime);
        $endTime = Carbon::parse($this->end_time);

        if ($checkTime->greaterThanOrEqualTo($endTime)) {
            return 0;
        }

        return $endTime->diffInMinutes($checkTime);
    }

    /**
     * Activate shift
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate shift
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Check if shift overlaps with another shift
     */
    public function overlapsWith(Shift $otherShift): bool
    {
        $thisStart = Carbon::parse($this->start_time);
        $thisEnd = Carbon::parse($this->end_time);
        $otherStart = Carbon::parse($otherShift->start_time);
        $otherEnd = Carbon::parse($otherShift->end_time);

        return $thisStart->lessThan($otherEnd) && $thisEnd->greaterThan($otherStart);
    }
}