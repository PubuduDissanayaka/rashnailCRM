<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkSchedule extends Model
{
    protected $fillable = [
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'grace_period_minutes',
        'is_working_day',
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'grace_period_minutes' => 'integer',
        'is_working_day' => 'boolean',
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
     * Scope: Filter by working day status
     */
    public function scopeWorkingDaysOnly($query)
    {
        return $query->where('is_working_day', true);
    }

    /**
     * Scope: Filter by day of week
     */
    public function scopeForDay($query, $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek);
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get formatted start time
     */
    public function getStartTimeFormattedAttribute()
    {
        return $this->start_time ? $this->start_time->format('h:i A') : '';
    }

    /**
     * Get formatted end time
     */
    public function getEndTimeFormattedAttribute()
    {
        return $this->end_time ? $this->end_time->format('h:i A') : '';
    }

    /**
     * Get working day label
     */
    public function getWorkingDayLabelAttribute()
    {
        return $this->is_working_day ? 'Working Day' : 'Off Day';
    }

    /**
     * Get day name in human readable format
     */
    public function getDayNameFormattedAttribute()
    {
        return ucfirst(str_replace('_', ' ', $this->day_of_week));
    }

    // ==========================================
    // BUSINESS LOGIC METHODS
    // ==========================================

    /**
     * Check if this is a scheduled working day
     */
    public function isWorkingDay()
    {
        return $this->is_working_day;
    }

    /**
     * Check if a given time is within working hours
     */
    public function isWithinWorkingHours($time)
    {
        if (!$this->is_working_day) {
            return false;
        }

        $checkTime = \Carbon\Carbon::parse($time);
        $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $this->start_time);
        $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $this->end_time);

        return $checkTime->between($startTime, $endTime);
    }

    /**
     * Check if the provided time is late compared to start time
     */
    public function isLateTime($time)
    {
        if (!$this->is_working_day) {
            return false;
        }

        $checkTime = \Carbon\Carbon::parse($time);
        $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $this->start_time);
        $lateTime = $startTime->copy()->addMinutes($this->grace_period_minutes);

        return $checkTime->gt($lateTime);
    }

    /**
     * Get the grace period end time
     */
    public function getGracePeriodEndTime()
    {
        if (!$this->start_time) {
            return null;
        }

        $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $this->start_time);
        return $startTime->addMinutes($this->grace_period_minutes);
    }

    /**
     * Check if the schedule is valid (start time is before end time)
     */
    public function isValidSchedule()
    {
        if (!$this->start_time || !$this->end_time) {
            return false;
        }

        return $this->start_time->lt($this->end_time);
    }

    // ==========================================
    // STATIC METHODS
    // ==========================================

    /**
     * Get user's schedule for a specific day
     */
    public static function getUserScheduleForDay($userId, $dayOfWeek)
    {
        return static::where('user_id', $userId)
                     ->where('day_of_week', $dayOfWeek)
                     ->first();
    }

    /**
     * Get user's weekly schedule
     */
    public static function getUserWeeklySchedule($userId)
    {
        return static::where('user_id', $userId)
                     ->orderByRaw("FIELD(day_of_week, 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday')")
                     ->get();
    }

    /**
     * Check if the schedule is valid (start time is before end time)
     */
    public function isValidSchedule()
    {
        if (!$this->start_time || !$this->end_time) {
            return false;
        }

        $start = \Carbon\Carbon::createFromTimeString($this->start_time);
        $end = \Carbon\Carbon::createFromTimeString($this->end_time);

        return $start->lt($end);
    }
}