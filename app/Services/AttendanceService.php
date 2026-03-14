<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceMeta;
use App\Models\AttendanceAuditLog;
use App\Models\Location;
use App\Models\Shift;
use App\Models\ShiftAssignment;
use App\Models\User;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\AttendanceCheckInNotification;
use App\Notifications\AttendanceCheckOutNotification;
use App\Notifications\LateCheckInNotification;
use App\Events\AttendanceCheckedIn;
use App\Events\AttendanceCheckedOut;
use App\Events\LateCheckInDetected;
use App\Events\EarlyDepartureDetected;

class AttendanceService
{
    protected $businessHoursService;

    public function __construct(BusinessHoursService $businessHoursService = null)
    {
        $this->businessHoursService = $businessHoursService ?? new BusinessHoursService();
    }
    /**
     * Check in a user using business hours
     *
     * @param User $user
     * @param array $data
     * @return Attendance
     * @throws \Exception
     */
    public function checkIn(User $user, array $data): Attendance
    {
        return DB::transaction(function () use ($user, $data) {
            // Check if already checked in today
            $existingAttendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', today())
                ->first();

            if ($existingAttendance && $existingAttendance->check_in) {
                throw new \Exception('You have already clocked in today.');
            }

            $checkInTime = now();
            
            // Determine business hours type
            $businessHoursType = $this->businessHoursService->getBusinessDayType($checkInTime);
            
            // Determine if late based on business hours
            $isLate = $this->businessHoursService->isLateCheckIn($checkInTime);
            $lateMinutes = $this->businessHoursService->calculateLateArrivalMinutes($checkInTime);
            
            // Determine status
            $status = $isLate ? 'late' : 'present';
            
            // Get expected hours for this date
            $expectedHours = $this->businessHoursService->getExpectedHoursForDate($checkInTime);

            // Create or update attendance
            $attendance = Attendance::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => today(),
                ],
                [
                    'check_in' => $checkInTime,
                    'status' => $status,
                    'late_arrival_minutes' => $lateMinutes,
                    'business_hours_type' => $businessHoursType,
                    'expected_hours' => $expectedHours,
                    'calculated_using_business_hours' => true,
                    'notes' => $data['notes'] ?? null,
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                    'is_manual_entry' => false,
                    'is_approved' => false, // Require approval
                ]
            );

            // Log audit trail
            AttendanceAuditLog::create([
                'attendance_id' => $attendance->id,
                'user_id' => $user->id,
                'action' => 'check_in',
                'new_values' => [
                    'check_in' => $checkInTime->toISOString(),
                    'business_hours_type' => $businessHoursType,
                    'status' => $status,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Dispatch attendance checked in event
            Event::dispatch(new AttendanceCheckedIn($attendance, $user));

            // Dispatch late check-in event if applicable
            if ($attendance->status === 'late') {
                Event::dispatch(new LateCheckInDetected($attendance));
            }

            // Send notifications (legacy - will be handled by event listeners)
            $this->sendCheckInNotifications($attendance, $user);

            return $attendance;
        });
    }
    
    /**
     * Check out a user using business hours
     *
     * @param User $user
     * @param array $data
     * @return Attendance
     * @throws \Exception
     */
    public function checkOut(User $user, array $data): Attendance
    {
        return DB::transaction(function () use ($user, $data) {
            // Get today's attendance that hasn't been checked out
            $attendance = Attendance::where('user_id', $user->id)
                ->whereDate('date', today())
                ->whereNotNull('check_in')
                ->whereNull('check_out')
                ->firstOrFail();

            $checkOutTime = now();

            // Calculate hours worked (excluding breaks)
            $totalMinutes = $attendance->check_in->diffInMinutes($checkOutTime);
            $breakMinutes = $attendance->total_break_minutes ?? 0;
            $netMinutes = $totalMinutes - $breakMinutes;
            $hoursWorked = round($netMinutes / 60, 2);

            // Calculate overtime based on business hours
            $overtimeMinutes = $this->businessHoursService->calculateOvertimeMinutes($checkOutTime);
            
            // Calculate early departure minutes
            $earlyDepartureMinutes = $this->businessHoursService->calculateEarlyDepartureMinutes($checkOutTime);
            
            // Determine if it's a half day
            $halfDayThreshold = $this->businessHoursService->getHalfDayThresholdHours();
            if ($hoursWorked < $halfDayThreshold && $hoursWorked > 0) {
                $attendance->status = 'half_day';
            }

            // Update attendance with business hours calculations
            $attendance->update([
                'check_out' => $checkOutTime,
                'hours_worked' => $hoursWorked,
                'overtime_minutes' => $overtimeMinutes,
                'early_departure_minutes' => $earlyDepartureMinutes,
                'calculated_using_business_hours' => true,
                'overtime_hours' => round($overtimeMinutes / 60, 2),
                'latitude_out' => $data['latitude'] ?? null,
                'longitude_out' => $data['longitude'] ?? null,
            ]);

            // Log audit trail
            AttendanceAuditLog::create([
                'attendance_id' => $attendance->id,
                'user_id' => $user->id,
                'action' => 'check_out',
                'new_values' => [
                    'check_out' => $checkOutTime->toISOString(),
                    'hours_worked' => $hoursWorked,
                    'overtime_minutes' => $overtimeMinutes,
                    'early_departure_minutes' => $earlyDepartureMinutes,
                ],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Dispatch attendance checked out event
            Event::dispatch(new AttendanceCheckedOut($attendance, $user));

            // Dispatch early departure event if applicable
            if ($attendance->early_departure_minutes > 0) {
                Event::dispatch(new EarlyDepartureDetected($attendance));
            }

            // Send notifications (legacy - will be handled by event listeners)
            $this->sendCheckOutNotifications($attendance, $user);

            return $attendance->fresh();
        });
    }
    
    /**
     * Start a break for a user
     *
     * @param User $user
     * @param array $data
     * @return AttendanceBreak
     * @throws \Exception
     */
    public function startBreak(User $user, array $data): AttendanceBreak
    {
        $now = Carbon::now();
        
        // Get active attendance for today
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('check_in', $now->toDateString())
            ->whereNull('check_out')
            ->first();
        
        if (!$attendance) {
            throw new \Exception('No active attendance record found for today.');
        }
        
        // Check if user already has an active break
        $activeBreak = $attendance->breaks()
            ->whereNull('end_time')
            ->first();
        
        if ($activeBreak) {
            throw new \Exception('User already has an active break.');
        }
        
        // Create break record
        $break = AttendanceBreak::create([
            'attendance_id' => $attendance->id,
            'start_time' => $now,
            'end_time' => null,
            'duration_minutes' => 0,
            'break_type' => $data['break_type'] ?? 'lunch',
            'notes' => $data['notes'] ?? null,
        ]);
        
        // Log audit trail
        $this->logAudit($attendance, 'break_start', [
            'break_id' => $break->id,
            'break_type' => $break->break_type,
            'start_time' => $now->toDateTimeString(),
        ]);
        
        return $break;
    }
    
    /**
     * End a break for a user
     *
     * @param User $user
     * @param array $data
     * @return AttendanceBreak
     * @throws \Exception
     */
    public function endBreak(User $user, array $data): AttendanceBreak
    {
        $now = Carbon::now();
        
        // Get active attendance for today
        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('check_in', $now->toDateString())
            ->whereNull('check_out')
            ->first();
        
        if (!$attendance) {
            throw new \Exception('No active attendance record found for today.');
        }
        
        // Get active break
        $break = $attendance->breaks()
            ->whereNull('end_time')
            ->first();
        
        if (!$break) {
            throw new \Exception('No active break found.');
        }
        
        // Calculate break duration
        $startTime = Carbon::parse($break->start_time);
        $durationMinutes = $startTime->diffInMinutes($now);
        
        // Update break record
        $break->update([
            'end_time' => $now,
            'duration_minutes' => $durationMinutes,
            'notes' => $data['notes'] ?? $break->notes,
        ]);
        
        // Log audit trail
        $this->logAudit($attendance, 'break_end', [
            'break_id' => $break->id,
            'break_type' => $break->break_type,
            'end_time' => $now->toDateTimeString(),
            'duration_minutes' => $durationMinutes,
        ]);
        
        return $break->fresh();
    }
    
    /**
     * Get business hours for a specific date
     *
     * @param Carbon $date
     * @return array|null
     */
    public function getBusinessHoursForDate(Carbon $date): ?array
    {
        return $this->businessHoursService->getHoursForDate($date);
    }
    
    /**
     * Validate geolocation data
     *
     * @param array $data
     * @param User $user
     * @throws \Exception
     */
    private function validateGeolocation(array $data, User $user): void
    {
        $enableGeolocation = Setting::get('attendance.general.enable_geolocation', true);
        
        if (!$enableGeolocation) {
            return;
        }
        
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            throw new \Exception('Geolocation is required for attendance.');
        }
        
        // Validate coordinates
        $latitude = $data['latitude'];
        $longitude = $data['longitude'];
        
        if (!is_numeric($latitude) || !is_numeric($longitude)) {
            throw new \Exception('Invalid geolocation coordinates.');
        }
        
        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            throw new \Exception('Geolocation coordinates are out of valid range.');
        }
    }
    
    /**
     * Validate location proximity
     *
     * @param array $data
     * @param User $user
     * @throws \Exception
     */
    private function validateLocation(array $data, User $user): void
    {
        $enableGeofencing = Setting::get('attendance.locations.enable_geofencing', true);
        $requireLocation = Setting::get('attendance.locations.require_location', false);
        
        if (!$enableGeofencing && !$requireLocation) {
            return;
        }
        
        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            if ($requireLocation) {
                throw new \Exception('Location is required for attendance.');
            }
            return;
        }
        
        // Get user's assigned location or default location
        $locationId = $data['location_id'] ?? Setting::get('attendance.locations.default_location_id');
        
        if (!$locationId) {
            // No location configured, skip validation
            return;
        }
        
        $location = Location::find($locationId);
        
        if (!$location) {
            throw new \Exception('Specified location not found.');
        }
        
        // Calculate distance
        $distance = $this->calculateDistance(
            $data['latitude'],
            $data['longitude'],
            $location->latitude,
            $location->longitude
        );
        
        $maxDistance = Setting::get('attendance.locations.max_distance', 500);
        
        if ($distance > $maxDistance) {
            throw new \Exception("You are too far from the required location. Distance: {$distance}m, Maximum: {$maxDistance}m");
        }
    }
    
    /**
     * Calculate distance between two points using Haversine formula
     *
     * @param float $lat1
     * @param float $lon1
     * @param float $lat2
     * @param float $lon2
     * @return float Distance in meters
     */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters
        
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);
        
        $latDelta = $lat2 - $lat1;
        $lonDelta = $lon2 - $lon1;
        
        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($lat1) * cos($lat2) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        
        return $earthRadius * $c;
    }
    
    
    /**
     * Determine if attendance should be auto-approved
     *
     * @param Attendance $attendance
     * @return bool
     */
    private function shouldAutoApprove(Attendance $attendance): bool
    {
        $autoApprove = Setting::get('attendance.approval.auto_approve', false);
        
        if (!$autoApprove) {
            return false;
        }
        
        // Check if attendance has any issues that would prevent auto-approval
        $maxLateMinutes = Setting::get('attendance.approval.max_late_minutes_for_auto_approve', 30);
        $maxEarlyMinutes = Setting::get('attendance.approval.max_early_minutes_for_auto_approve', 15);
        
        if ($attendance->late_minutes > $maxLateMinutes) {
            return false;
        }
        
        if ($attendance->early_minutes > $maxEarlyMinutes) {
            return false;
        }
        
        // Check if check-in/check-out times are within reasonable bounds
        $checkInTime = Carbon::parse($attendance->check_in);
        $checkOutTime = $attendance->check_out ? Carbon::parse($attendance->check_out) : null;
        
        if (!$checkOutTime) {
            return false; // Cannot auto-approve without check-out
        }
        
        $minWorkHours = Setting::get('attendance.approval.min_work_hours_for_auto_approve', 4);
        $maxWorkHours = Setting::get('attendance.approval.max_work_hours_for_auto_approve', 12);
        
        $workHours = $attendance->hours_worked;
        
        if ($workHours < $minWorkHours || $workHours > $maxWorkHours) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Log audit trail for attendance actions
     *
     * @param Attendance $attendance
     * @param string $action
     * @param array $details
     * @return void
     */
    private function logAudit(Attendance $attendance, string $action, array $details = []): void
    {
        AttendanceAuditLog::create([
            'attendance_id' => $attendance->id,
            'user_id' => auth()->id() ?? $attendance->user_id,
            'action' => $action,
            'new_values' => $details,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
    
    /**
     * Send check-in notifications
     *
     * @param Attendance $attendance
     * @param User $user
     * @return void
     */
    private function sendCheckInNotifications(Attendance $attendance, User $user): void
    {
        $notifyManager = Setting::get('attendance.notifications.notify_manager_on_check_in', true);
        $notifyUser = Setting::get('attendance.notifications.notify_user_on_check_in', false);
        
        if ($notifyManager) {
            // Get user's manager
            $manager = $user->manager; // Assuming there's a manager relationship
            
            if ($manager) {
                $manager->notify(new AttendanceCheckInNotification($attendance, $user));
            }
        }
        
        if ($notifyUser) {
            $user->notify(new AttendanceCheckInNotification($attendance, $user));
        }
        
        // Send late check-in notification if applicable
        if ($attendance->status === 'late') {
            $notifyLate = Setting::get('attendance.notifications.notify_on_late_check_in', true);
            
            if ($notifyLate) {
                $user->notify(new LateCheckInNotification($attendance));
                
                // Also notify manager
                if ($manager = $user->manager) {
                    $manager->notify(new LateCheckInNotification($attendance));
                }
            }
        }
    }
    
    /**
     * Send check-out notifications
     *
     * @param Attendance $attendance
     * @param User $user
     * @return void
     */
    private function sendCheckOutNotifications(Attendance $attendance, User $user): void
    {
        $notifyManager = Setting::get('attendance.notifications.notify_manager_on_check_out', true);
        $notifyUser = Setting::get('attendance.notifications.notify_user_on_check_out', false);
        
        if ($notifyManager) {
            // Get user's manager
            $manager = $user->manager; // Assuming there's a manager relationship
            
            if ($manager) {
                $manager->notify(new AttendanceCheckOutNotification($attendance, $user));
            }
        }
        
        if ($notifyUser) {
            $user->notify(new AttendanceCheckOutNotification($attendance, $user));
        }
    }
}