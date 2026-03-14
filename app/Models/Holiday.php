<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Holiday extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'holiday_date',
        'is_recurring',
        'type',
        'is_active',
        'description',
    ];

    protected $casts = [
        'holiday_date' => 'date',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope: Only active holidays
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Holidays of a specific type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Recurring holidays
     */
    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    /**
     * Scope: Non-recurring holidays
     */
    public function scopeNonRecurring($query)
    {
        return $query->where('is_recurring', false);
    }

    /**
     * Scope: Holidays in a specific year
     */
    public function scopeInYear($query, $year)
    {
        return $query->whereYear('holiday_date', $year);
    }

    /**
     * Scope: Holidays in a specific month
     */
    public function scopeInMonth($query, $year, $month)
    {
        return $query->whereYear('holiday_date', $year)
            ->whereMonth('holiday_date', $month);
    }

    /**
     * Scope: Holidays within a date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('holiday_date', [$startDate, $endDate]);
    }

    /**
     * Scope: Upcoming holidays
     */
    public function scopeUpcoming($query, $days = 30)
    {
        $today = Carbon::today();
        $futureDate = $today->copy()->addDays($days);
        
        return $query->where('holiday_date', '>=', $today)
            ->where('holiday_date', '<=', $futureDate)
            ->orderBy('holiday_date');
    }

    /**
     * Scope: Past holidays
     */
    public function scopePast($query, $days = 30)
    {
        $today = Carbon::today();
        $pastDate = $today->copy()->subDays($days);
        
        return $query->where('holiday_date', '>=', $pastDate)
            ->where('holiday_date', '<', $today)
            ->orderBy('holiday_date', 'desc');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get formatted holiday date
     */
    public function getHolidayDateFormattedAttribute(): string
    {
        return $this->holiday_date->format('M d, Y');
    }

    /**
     * Get day name
     */
    public function getDayNameAttribute(): string
    {
        return $this->holiday_date->format('l');
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'national' => 'National Holiday',
            'regional' => 'Regional Holiday',
            'company' => 'Company Holiday',
            'religious' => 'Religious Holiday',
            'other' => 'Other Holiday',
            default => ucfirst($this->type),
        };
    }

    /**
     * Get type badge class
     */
    public function getTypeBadgeAttribute(): string
    {
        return match($this->type) {
            'national' => 'bg-danger',
            'regional' => 'bg-warning',
            'company' => 'bg-info',
            'religious' => 'bg-success',
            'other' => 'bg-secondary',
            default => 'bg-light',
        };
    }

    /**
     * Get status badge class
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->is_active ? 'bg-success' : 'bg-secondary';
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    /**
     * Check if holiday is today
     */
    public function getIsTodayAttribute(): bool
    {
        return $this->holiday_date->isToday();
    }

    /**
     * Check if holiday is in the past
     */
    public function getIsPastAttribute(): bool
    {
        return $this->holiday_date->isPast();
    }

    /**
     * Check if holiday is in the future
     */
    public function getIsFutureAttribute(): bool
    {
        return $this->holiday_date->isFuture();
    }

    /**
     * Get days until holiday (negative if past)
     */
    public function getDaysUntilAttribute(): int
    {
        return Carbon::today()->diffInDays($this->holiday_date, false);
    }

    /**
     * Get days until formatted
     */
    public function getDaysUntilFormattedAttribute(): string
    {
        $days = $this->days_until;
        
        if ($days === 0) {
            return 'Today';
        }
        
        if ($days === 1) {
            return 'Tomorrow';
        }
        
        if ($days === -1) {
            return 'Yesterday';
        }
        
        if ($days > 0) {
            return "In {$days} days";
        }
        
        return abs($days) . ' days ago';
    }

    // ==========================================
    // BUSINESS LOGIC METHODS
    // ==========================================

    /**
     * Check if a date is a holiday
     */
    public static function isHoliday($date): bool
    {
        $date = Carbon::parse($date);
        
        // Check for exact date match
        $exactMatch = static::where('holiday_date', $date)
            ->where('is_active', true)
            ->exists();
        
        if ($exactMatch) {
            return true;
        }
        
        // Check for recurring holidays (same month and day)
        $recurringMatch = static::where('is_recurring', true)
            ->where('is_active', true)
            ->whereMonth('holiday_date', $date->month)
            ->whereDay('holiday_date', $date->day)
            ->exists();
        
        return $recurringMatch;
    }

    /**
     * Get holiday for a specific date
     */
    public static function getHolidayForDate($date): ?self
    {
        $date = Carbon::parse($date);
        
        // Try exact match first
        $holiday = static::where('holiday_date', $date)
            ->where('is_active', true)
            ->first();
        
        if ($holiday) {
            return $holiday;
        }
        
        // Try recurring match
        return static::where('is_recurring', true)
            ->where('is_active', true)
            ->whereMonth('holiday_date', $date->month)
            ->whereDay('holiday_date', $date->day)
            ->first();
    }

    /**
     * Get all holidays for a year
     */
    public static function getHolidaysForYear($year): \Illuminate\Database\Eloquent\Collection
    {
        return static::where(function ($query) use ($year) {
                $query->whereYear('holiday_date', $year)
                    ->orWhere('is_recurring', true);
            })
            ->where('is_active', true)
            ->get()
            ->filter(function ($holiday) use ($year) {
                if ($holiday->is_recurring) {
                    // For recurring holidays, create a date in the target year
                    $holidayDate = Carbon::create($year, $holiday->holiday_date->month, $holiday->holiday_date->day);
                    $holiday->holiday_date = $holidayDate;
                    return true;
                }
                return true;
            })
            ->sortBy('holiday_date');
    }

    /**
     * Get all holidays for a month
     */
    public static function getHolidaysForMonth($year, $month): \Illuminate\Database\Eloquent\Collection
    {
        return static::where(function ($query) use ($year, $month) {
                $query->whereYear('holiday_date', $year)
                    ->whereMonth('holiday_date', $month)
                    ->orWhere(function ($q) use ($month) {
                        $q->where('is_recurring', true)
                            ->whereMonth('holiday_date', $month);
                    });
            })
            ->where('is_active', true)
            ->get()
            ->filter(function ($holiday) use ($year, $month) {
                if ($holiday->is_recurring) {
                    // For recurring holidays, create a date in the target year/month
                    $holidayDate = Carbon::create($year, $month, $holiday->holiday_date->day);
                    $holiday->holiday_date = $holidayDate;
                    return true;
                }
                return true;
            })
            ->sortBy('holiday_date');
    }

    /**
     * Get next holiday
     */
    public static function getNextHoliday(): ?self
    {
        $today = Carbon::today();
        
        // Get exact date matches
        $nextExact = static::where('holiday_date', '>=', $today)
            ->where('is_active', true)
            ->orderBy('holiday_date')
            ->first();
        
        // Get recurring matches for current year
        $currentYear = $today->year;
        $recurringHolidays = static::where('is_recurring', true)
            ->where('is_active', true)
            ->get()
            ->map(function ($holiday) use ($currentYear) {
                $holidayDate = Carbon::create($currentYear, $holiday->holiday_date->month, $holiday->holiday_date->day);
                $holiday->holiday_date = $holidayDate;
                return $holiday;
            })
            ->filter(function ($holiday) use ($today) {
                return $holiday->holiday_date >= $today;
            })
            ->sortBy('holiday_date')
            ->first();
        
        // Return the earliest holiday
        if (!$nextExact) {
            return $recurringHolidays;
        }
        
        if (!$recurringHolidays) {
            return $nextExact;
        }
        
        return $nextExact->holiday_date <= $recurringHolidays->holiday_date ? $nextExact : $recurringHolidays;
    }

    /**
     * Activate holiday
     */
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Deactivate holiday
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Mark as recurring
     */
    public function markAsRecurring(): void
    {
        $this->update(['is_recurring' => true]);
    }

    /**
     * Mark as non-recurring
     */
    public function markAsNonRecurring(): void
    {
        $this->update(['is_recurring' => false]);
    }

    /**
     * Create recurring holiday from non-recurring
     */
    public function makeRecurring(): self
    {
        if ($this->is_recurring) {
            return $this;
        }
        
        // Create a new recurring holiday with same month/day
        $recurringHoliday = static::create([
            'name' => $this->name,
            'holiday_date' => $this->holiday_date,
            'is_recurring' => true,
            'type' => $this->type,
            'is_active' => $this->is_active,
            'description' => $this->description,
        ]);
        
        return $recurringHoliday;
    }

    /**
     * Check if holiday conflicts with another holiday
     */
    public function conflictsWith(self $otherHoliday): bool
    {
        if ($this->is_recurring && $otherHoliday->is_recurring) {
            // Both recurring - check if same month/day
            return $this->holiday_date->month == $otherHoliday->holiday_date->month &&
                   $this->holiday_date->day == $otherHoliday->holiday_date->day;
        }
        
        if (!$this->is_recurring && !$otherHoliday->is_recurring) {
            // Both non-recurring - check exact date
            return $this->holiday_date->equalTo($otherHoliday->holiday_date);
        }
        
        // One recurring, one non-recurring
        if ($this->is_recurring) {
            // This is recurring, other is non-recurring
            return $this->holiday_date->month == $otherHoliday->holiday_date->month &&
                   $this->holiday_date->day == $otherHoliday->holiday_date->day;
        } else {
            // This is non-recurring, other is recurring
            return $otherHoliday->holiday_date->month == $this->holiday_date->month &&
                   $otherHoliday->holiday_date->day == $this->holiday_date->day;
        }
    }
}