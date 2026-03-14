<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use App\Services\BusinessHoursService;

class Attendance extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'hours_worked',
        'notes',
        'is_manual_entry',
        // New enterprise fields
        'location_id',
        'ip_address',
        'device_fingerprint',
        'user_agent',
        'latitude',
        'longitude',
        'verification_photo_url',
        'check_in_method',
        'check_out_method',
        'overtime_minutes',
        'early_departure_minutes',
        'late_arrival_minutes',
        'total_break_minutes',
        'is_approved',
        'approved_by',
        'approved_at',
        'attendance_type',
        'overtime_hours',
        // Business hours fields
        'business_hours_type',
        'expected_hours',
        'calculated_using_business_hours',
        'check_in_latitude',
        'check_in_longitude',
        'check_out_latitude',
        'check_out_longitude',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'hours_worked' => 'decimal:2',
        'is_manual_entry' => 'boolean',
        // New enterprise casts
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'overtime_minutes' => 'integer',
        'early_departure_minutes' => 'integer',
        'late_arrival_minutes' => 'integer',
        'total_break_minutes' => 'integer',
        'is_approved' => 'boolean',
        'approved_at' => 'datetime',
        'overtime_hours' => 'decimal:2',
        // Business hours casts
        'expected_hours' => 'decimal:2',
        'calculated_using_business_hours' => 'boolean',
    ];

    // ==========================================
    // RELATIONSHIPS
    // ==========================================

    /**
     * Attendance belongs to a user (staff member)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Attendance belongs to a location
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * Attendance approved by user
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Attendance has many breaks
     */
    public function breaks(): HasMany
    {
        return $this->hasMany(AttendanceBreak::class);
    }

    /**
     * Attendance has many meta entries
     */
    public function meta(): HasMany
    {
        return $this->hasMany(AttendanceMeta::class);
    }

    /**
     * Attendance has many audit logs
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AttendanceAuditLog::class);
    }

    // ==========================================
    // QUERY SCOPES
    // ==========================================

    /**
     * Scope: Filter by specific staff member
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Today's attendance records
     */
    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    /**
     * Scope: This week's attendance
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * Scope: This month's attendance
     */
    public function scopeThisMonth($query)
    {
        return $query->whereYear('date', now()->year)
                     ->whereMonth('date', now()->month);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope: Filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Only checked in (has check_in but no check_out)
     */
    public function scopeCheckedIn($query)
    {
        return $query->whereNotNull('check_in')
                     ->whereNull('check_out');
    }

    /**
     * Scope: Only staff members (exclude administrators)
     */
    public function scopeStaffOnly($query)
    {
        return $query->whereHas('user', function ($q) {
            $q->where('role', 'staff');
        });
    }

    /**
     * Scope: Filter by location
     */
    public function scopeAtLocation($query, $locationId)
    {
        return $query->where('location_id', $locationId);
    }

    /**
     * Scope: Filter by approval status
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope: Filter by pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('is_approved', false);
    }

    /**
     * Scope: Filter by attendance type
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('attendance_type', $type);
    }

    /**
     * Scope: Filter by check-in method
     */
    public function scopeWithCheckInMethod($query, $method)
    {
        return $query->where('check_in_method', $method);
    }

    /**
     * Scope: Filter by geolocation within radius
     */
    public function scopeNearLocation($query, $latitude, $longitude, $radius = 100)
    {
        // Using Haversine formula for distance calculation
        $haversine = "(6371 * acos(cos(radians($latitude)) 
                     * cos(radians(latitude)) 
                     * cos(radians(longitude) - radians($longitude)) 
                     + sin(radians($latitude)) 
                     * sin(radians(latitude))))";

        return $query->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('*')
            ->selectRaw("$haversine AS distance")
            ->whereRaw("$haversine < ?", [$radius / 1000]) // Convert meters to kilometers
            ->orderBy('distance');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Get formatted check-in time (12-hour format)
     */
    public function getCheckInFormattedAttribute()
    {
        return $this->check_in ? $this->check_in->format('h:i A') : '-';
    }

    /**
     * Get formatted check-out time (12-hour format)
     */
    public function getCheckOutFormattedAttribute()
    {
        return $this->check_out ? $this->check_out->format('h:i A') : '-';
    }

    /**
     * Get formatted date (human-readable)
     */
    public function getDateFormattedAttribute()
    {
        return $this->date->format('M d, Y');
    }

    /**
     * Get day name (Monday, Tuesday, etc.)
     */
    public function getDayNameAttribute()
    {
        return $this->date->format('l');
    }

    /**
     * Get status badge class for UI
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'present' => 'bg-success',
            'late' => 'bg-warning',
            'absent' => 'bg-danger',
            'leave' => 'bg-info',
            'half_day' => 'bg-secondary',
            default => 'bg-secondary',
        };
    }

    /**
     * Get human-readable status label
     */
    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'present' => 'Present',
            'late' => 'Late',
            'absent' => 'Absent',
            'leave' => 'On Leave',
            'half_day' => 'Half Day',
            default => 'Unknown',
        };
    }

    /**
     * Check if attendance is complete (has both check-in and check-out)
     */
    public function getIsCompleteAttribute()
    {
        return $this->check_in && $this->check_out;
    }

    /**
     * Check if currently checked in
     */
    public function getIsCheckedInAttribute()
    {
        return $this->check_in && !$this->check_out;
    }

    /**
     * Get check-in method label
     */
    public function getCheckInMethodLabelAttribute(): string
    {
        return match($this->check_in_method) {
            'web' => 'Web',
            'mobile' => 'Mobile',
            'biometric' => 'Biometric',
            'card' => 'Card',
            'manual' => 'Manual',
            default => ucfirst($this->check_in_method),
        };
    }

    /**
     * Get check-out method label
     */
    public function getCheckOutMethodLabelAttribute(): string
    {
        return match($this->check_out_method) {
            'web' => 'Web',
            'mobile' => 'Mobile',
            'biometric' => 'Biometric',
            'card' => 'Card',
            'manual' => 'Manual',
            default => ucfirst($this->check_out_method),
        };
    }

    /**
     * Get attendance type label
     */
    public function getAttendanceTypeLabelAttribute(): string
    {
        return match($this->attendance_type) {
            'regular' => 'Regular',
            'overtime' => 'Overtime',
            'holiday' => 'Holiday',
            'weekend' => 'Weekend',
            default => ucfirst($this->attendance_type),
        };
    }

    /**
     * Get approval status label
     */
    public function getApprovalStatusLabelAttribute(): string
    {
        return $this->is_approved ? 'Approved' : 'Pending Approval';
    }

    /**
     * Get approval status badge
     */
    public function getApprovalStatusBadgeAttribute(): string
    {
        return $this->is_approved ? 'bg-success' : 'bg-warning';
    }

    /**
     * Get formatted overtime hours
     */
    public function getOvertimeHoursFormattedAttribute(): string
    {
        if (!$this->overtime_hours) {
            return '0h';
        }
        
        $hours = floor($this->overtime_hours);
        $minutes = round(($this->overtime_hours - $hours) * 60);
        
        if ($hours > 0 && $minutes > 0) {
            return "{$hours}h {$minutes}m";
        }
        
        if ($hours > 0) {
            return "{$hours}h";
        }
        
        return "{$minutes}m";
    }

    /**
     * Get net working hours (excluding breaks)
     */
    public function getNetWorkingHoursAttribute(): float
    {
        $totalHours = $this->hours_worked ?? 0;
        $breakHours = $this->total_break_minutes / 60;
        
        return round($totalHours - $breakHours, 2);
    }

    /**
     * Get coordinates as array
     */
    public function getCoordinatesAttribute(): ?array
    {
        if (!$this->latitude || !$this->longitude) {
            return null;
        }
        
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }

    // ==========================================
    // BUSINESS LOGIC METHODS
    // ==========================================

    /**
     * Calculate hours worked from check-in and check-out times
     */
    public function calculateHoursWorked()
    {
        if (!$this->check_in || !$this->check_out) {
            return null;
        }

        $minutes = $this->check_in->diffInMinutes($this->check_out);
        return round($minutes / 60, 2);
    }

    /**
     * Perform check-in with enterprise features
     */
    public function checkIn($time = null, array $options = [])
    {
        $checkInTime = $time ? Carbon::parse($time) : now();

        $this->check_in = $checkInTime;
        $this->check_in_method = $options['method'] ?? 'web';
        
        // Set location if provided
        if (isset($options['location_id'])) {
            $this->location_id = $options['location_id'];
        }
        
        // Set coordinates if provided
        if (isset($options['latitude']) && isset($options['longitude'])) {
            $this->latitude = $options['latitude'];
            $this->longitude = $options['longitude'];
        }
        
        // Set device information
        if (isset($options['ip_address'])) {
            $this->ip_address = $options['ip_address'];
        }
        
        if (isset($options['device_fingerprint'])) {
            $this->device_fingerprint = $options['device_fingerprint'];
        }
        
        if (isset($options['user_agent'])) {
            $this->user_agent = $options['user_agent'];
        }
        
        if (isset($options['verification_photo_url'])) {
            $this->verification_photo_url = $options['verification_photo_url'];
        }

        // Determine if late based on work schedule
        if ($this->isLateCheckIn($checkInTime)) {
            $this->status = 'late';
            $this->late_arrival_minutes = $this->calculateLateArrivalMinutes($checkInTime);
        } else {
            $this->status = 'present';
        }

        $this->save();

        // Log the check-in
        AttendanceAuditLog::logCheckIn($this);

        return $this;
    }

    /**
     * Perform check-out and calculate hours with enterprise features
     */
    public function checkOut($time = null, array $options = [])
    {
        $checkOutTime = $time ? Carbon::parse($time) : now();

        $this->check_out = $checkOutTime;
        $this->check_out_method = $options['method'] ?? 'web';
        $this->hours_worked = $this->calculateHoursWorked();
        
        // Calculate overtime
        $this->overtime_minutes = $this->calculateOvertimeMinutes($checkOutTime);
        $this->overtime_hours = round($this->overtime_minutes / 60, 2);
        
        // Calculate early departure
        $this->early_departure_minutes = $this->calculateEarlyDepartureMinutes($checkOutTime);
        
        // Calculate total break minutes
        $this->total_break_minutes = $this->calculateTotalBreakMinutes();
        
        // Update attendance type if it's overtime
        if ($this->overtime_minutes > 0) {
            $this->attendance_type = 'overtime';
        }
        
        // Check if it's a holiday or weekend
        if (Holiday::isHoliday($this->date)) {
            $this->attendance_type = 'holiday';
        } elseif ($this->date->isWeekend()) {
            $this->attendance_type = 'weekend';
        }

        $this->save();

        // Log the check-out
        AttendanceAuditLog::logCheckOut($this);

        return $this;
    }

    /**
     * Check if check-in time is late based on business hours
     */
    protected function isLateCheckIn($checkInTime)
    {
        // Use BusinessHoursService to determine if check-in is late
        $businessHoursService = app(BusinessHoursService::class);
        $businessHours = $businessHoursService->getHoursForDate($this->date);
        
        if (!$businessHours) {
            // Fallback to default schedule
            $scheduledStart = Carbon::parse($this->date->format('Y-m-d') . ' 09:00:00');
            $gracePeriod = 15; // minutes
            return $checkInTime->greaterThan($scheduledStart->addMinutes($gracePeriod));
        }
        
        return $businessHoursService->isLateCheckIn($checkInTime);
    }

    /**
     * Calculate late arrival minutes based on business hours
     */
    protected function calculateLateArrivalMinutes($checkInTime): int
    {
        $businessHoursService = app(BusinessHoursService::class);
        $businessHours = $businessHoursService->getHoursForDate($this->date);
        
        if (!$businessHours) {
            // Fallback calculation
            $scheduledStart = Carbon::parse($this->date->format('Y-m-d') . ' 09:00:00');
            $graceEnd = $scheduledStart->addMinutes(15);
            
            if ($checkInTime->lessThanOrEqualTo($graceEnd)) {
                return 0;
            }
            
            return $graceEnd->diffInMinutes($checkInTime);
        }
        
        return $businessHoursService->calculateLateArrivalMinutes($checkInTime);
    }

    /**
     * Calculate overtime minutes based on business hours
     */
    protected function calculateOvertimeMinutes($checkOutTime): int
    {
        $businessHoursService = app(BusinessHoursService::class);
        $businessHours = $businessHoursService->getHoursForDate($this->date);
        
        if (!$businessHours) {
            // Fallback calculation
            $scheduledEnd = Carbon::parse($this->date->format('Y-m-d') . ' 17:00:00');
            $overtimeStart = $scheduledEnd->addMinutes(60); // 1 hour after scheduled end
            
            if ($checkOutTime->lessThanOrEqualTo($overtimeStart)) {
                return 0;
            }
            
            return $overtimeStart->diffInMinutes($checkOutTime);
        }
        
        return $businessHoursService->calculateOvertimeMinutes($checkOutTime);
    }

    /**
     * Calculate early departure minutes based on business hours
     */
    protected function calculateEarlyDepartureMinutes($checkOutTime): int
    {
        $businessHoursService = app(BusinessHoursService::class);
        $businessHours = $businessHoursService->getHoursForDate($this->date);
        
        if (!$businessHours) {
            // Fallback calculation
            $scheduledEnd = Carbon::parse($this->date->format('Y-m-d') . ' 17:00:00');
            
            if ($checkOutTime->greaterThanOrEqualTo($scheduledEnd)) {
                return 0;
            }
            
            return $scheduledEnd->diffInMinutes($checkOutTime);
        }
        
        return $businessHoursService->calculateEarlyDepartureMinutes($checkOutTime);
    }

    /**
     * Calculate total break minutes
     */
    protected function calculateTotalBreakMinutes(): int
    {
        return $this->breaks()->sum('duration_minutes') ?? 0;
    }

    /**
     * Mark as leave
     */
    public function markAsLeave($notes = null)
    {
        $this->status = 'leave';
        $this->notes = $notes;
        $this->save();

        return $this;
    }

    /**
     * Mark as absent
     */
    public function markAsAbsent($notes = null)
    {
        $this->status = 'absent';
        $this->notes = $notes;
        $this->save();

        return $this;
    }

    /**
     * Approve attendance
     */
    public function approve($approvedBy = null)
    {
        $this->is_approved = true;
        $this->approved_by = $approvedBy ?? auth()->id();
        $this->approved_at = now();
        $this->save();

        // Log the approval
        AttendanceAuditLog::logApproval($this, $this->approved_by);

        return $this;
    }

    /**
     * Reject attendance
     */
    public function reject($rejectedBy = null, $reason = null)
    {
        $this->is_approved = false;
        $this->approved_by = $rejectedBy ?? auth()->id();
        $this->approved_at = now();
        $this->save();

        // Log the rejection
        AttendanceAuditLog::logRejection($this, $this->approved_by, $reason);

        return $this;
    }

    /**
     * Start a break
     */
    public function startBreak($breakType = 'lunch', $time = null)
    {
        // Check if there's already an active break
        $activeBreak = AttendanceBreak::getActiveBreak($this->id);
        
        if ($activeBreak) {
            throw new \Exception('There is already an active break');
        }

        $break = new AttendanceBreak([
            'attendance_id' => $this->id,
            'break_type' => $breakType,
        ]);
        
        $break->startBreak($time);
        $break->save();

        // Log the break start
        AttendanceAuditLog::logAction(
            $this->id,
            $this->user_id,
            'break_start',
            null,
            ['break_type' => $breakType, 'break_start' => $break->break_start]
        );

        return $break;
    }

    /**
     * End the current break
     */
    public function endBreak($time = null)
    {
        $activeBreak = AttendanceBreak::getActiveBreak($this->id);
        
        if (!$activeBreak) {
            throw new \Exception('No active break found');
        }

        $activeBreak->endBreak($time);
        $activeBreak->save();

        // Update total break minutes
        $this->total_break_minutes = $this->calculateTotalBreakMinutes();
        $this->save();

        // Log the break end
        AttendanceAuditLog::logAction(
            $this->id,
            $this->user_id,
            'break_end',
            null,
            ['break_type' => $activeBreak->break_type, 'break_end' => $activeBreak->break_end, 'duration_minutes' => $activeBreak->duration_minutes]
        );

        return $activeBreak;
    }

    /**
     * Get active break
     */
    public function getActiveBreak()
    {
        return AttendanceBreak::getActiveBreak($this->id);
    }

    /**
     * Check if currently on break
     */
    public function isOnBreak(): bool
    {
        return $this->getActiveBreak() !== null;
    }

    /**
     * Set meta value
     */
    public function setMeta($key, $value)
    {
        return AttendanceMeta::setMeta($this->id, $key, $value);
    }

    /**
     * Get meta value
     */
    public function getMeta($key, $default = null)
    {
        return AttendanceMeta::getMeta($this->id, $key, $default);
    }

    /**
     * Get all meta
     */
    public function getAllMeta(): array
    {
        return AttendanceMeta::getAllMeta($this->id);
    }

    /**
     * Check if within geofence of assigned location
     */
    public function isWithinGeofence(): bool
    {
        if (!$this->location || !$this->latitude || !$this->longitude) {
            return false;
        }

        return $this->location->isWithinGeofence($this->latitude, $this->longitude);
    }

    /**
     * Get distance from assigned location
     */
    public function getDistanceFromLocation(): ?float
    {
        if (!$this->location || !$this->latitude || !$this->longitude) {
            return null;
        }

        return $this->location->distanceTo($this->latitude, $this->longitude);
    }

    // ==========================================
    // STATIC HELPER METHODS
    // ==========================================

    /**
     * Get or create today's attendance for a user
     */
    public static function getOrCreateToday($userId)
    {
        return static::firstOrCreate(
            [
                'user_id' => $userId,
                'date' => today(),
            ],
            [
                'status' => 'absent',
            ]
        );
    }

    /**
     * Check if user has checked in today
     */
    public static function hasCheckedInToday($userId)
    {
        return static::where('user_id', $userId)
                     ->whereDate('date', today())
                     ->whereNotNull('check_in')
                     ->exists();
    }

    /**
     * Check if user has checked out today
     */
    public static function hasCheckedOutToday($userId)
    {
        return static::where('user_id', $userId)
                     ->whereDate('date', today())
                     ->whereNotNull('check_out')
                     ->exists();
    }

    /**
     * Get today's attendance for a user
     */
    public static function getTodayAttendance($userId)
    {
        return static::where('user_id', $userId)
                     ->whereDate('date', today())
                     ->first();
    }

    /**
     * Get attendance summary for a user in a date range
     */
    public static function getUserSummary($userId, $startDate, $endDate): array
    {
        $attendances = static::where('user_id', $userId)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalDays = $attendances->count();
        $presentDays = $attendances->whereIn('status', ['present', 'late'])->count();
        $lateDays = $attendances->where('status', 'late')->count();
        $absentDays = $attendances->where('status', 'absent')->count();
        $leaveDays = $attendances->where('status', 'leave')->count();
        $totalHours = $attendances->sum('hours_worked');
        $totalOvertime = $attendances->sum('overtime_hours');

        return [
            'total_days' => $totalDays,
            'present_days' => $presentDays,
            'late_days' => $lateDays,
            'absent_days' => $absentDays,
            'leave_days' => $leaveDays,
            'total_hours' => round($totalHours, 2),
            'total_overtime' => round($totalOvertime, 2),
            'attendance_rate' => $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0,
        ];
    }

    /**
     * Get team attendance summary for a date range
     */
    public static function getTeamSummary($userIds, $startDate, $endDate): array
    {
        $attendances = static::whereIn('user_id', $userIds)
            ->whereBetween('date', [$startDate, $endDate])
            ->get();

        $totalRecords = $attendances->count();
        $presentRecords = $attendances->whereIn('status', ['present', 'late'])->count();
        $lateRecords = $attendances->where('status', 'late')->count();
        $absentRecords = $attendances->where('status', 'absent')->count();
        $totalHours = $attendances->sum('hours_worked');
        $totalOvertime = $attendances->sum('overtime_hours');

        return [
            'total_records' => $totalRecords,
            'present_records' => $presentRecords,
            'late_records' => $lateRecords,
            'absent_records' => $absentRecords,
            'total_hours' => round($totalHours, 2),
            'total_overtime' => round($totalOvertime, 2),
            'attendance_rate' => $totalRecords > 0 ? round(($presentRecords / $totalRecords) * 100, 2) : 0,
            'late_rate' => $totalRecords > 0 ? round(($lateRecords / $totalRecords) * 100, 2) : 0,
        ];
    }
}