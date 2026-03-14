# Staff Attendance System - Comprehensive Implementation Plan

## Executive Summary

This document outlines a complete staff attendance management system for the Laravel-based business management application. The system will enable:
- Daily staff check-in/check-out tracking
- Attendance history and reporting
- Leave management
- Automated late detection
- Monthly attendance reports
- Integration with existing user/staff management

---

## Table of Contents

1. [System Analysis & Context](#system-analysis--context)
2. [Feature Requirements](#feature-requirements)
3. [Database Design](#database-design)
4. [Models & Relationships](#models--relationships)
5. [Controllers & Business Logic](#controllers--business-logic)
6. [Routes & Middleware](#routes--middleware)
7. [Views & UI Components](#views--ui-components)
8. [Permissions & Authorization](#permissions--authorization)
9. [Check-In/Check-Out Flow](#check-incheck-out-flow)
10. [Reports & Analytics](#reports--analytics)
11. [Settings & Configuration](#settings--configuration)
12. [Implementation Phases](#implementation-phases)
13. [Testing Plan](#testing-plan)
14. [Future Enhancements](#future-enhancements)

---

## System Analysis & Context

### Current System Architecture

**User Management:**
- Single User model with role field (`administrator`, `staff`)
- Spatie Laravel-Permission for RBAC
- User fields: name, email, phone, avatar, status, slug
- Status types: active, inactive, suspended

**Technology Stack:**
- Laravel 12.x + PHP 8.2+
- Bootstrap 5.3+ with custom DataTables
- Vite for asset bundling
- SweetAlert2 for notifications
- Choices.js for select dropdowns

**Existing Patterns:**
- CRUD operations with authorization
- Query scopes for filtering
- Status management with enum fields
- Calendar integration (FullCalendar) in Appointments
- Custom DataTable implementation

---

## Feature Requirements

### Core Features (Phase 1)

#### 1. Daily Check-In/Check-Out
- Staff can check in at start of work day
- Staff can check out at end of work day
- Single check-in per day per staff member
- Automatic date stamping
- Optional notes field

#### 2. Attendance Records Management
- View all attendance records (Admin)
- View own attendance (Staff)
- Filter by date range, staff member, status
- Search functionality
- Export to Excel/PDF

#### 3. Attendance Status Types
- **Present** - Checked in on time
- **Late** - Checked in after scheduled time
- **Absent** - No check-in record for the day
- **Leave** - Approved leave
- **Half-day** - Present for half shift

#### 4. Manual Attendance Entry
- Admin can manually add/edit attendance records
- Useful for corrections, retroactive entries
- Requires reason/notes field

#### 5. Daily Dashboard Widget
- Today's check-in status
- Quick check-in button
- Staff currently checked in count
- Late arrivals count

### Enhanced Features (Phase 2)

#### 6. Leave Management
- Leave request submission by staff
- Leave approval workflow
- Leave types: sick, vacation, personal, unpaid
- Leave balance tracking
- Leave calendar view

#### 7. Work Schedule Management
- Define work hours per staff member
- Shift management (morning, afternoon, evening, night)
- Flexible schedules vs fixed schedules
- Weekend/holiday configuration

#### 8. Automated Late Detection
- Compare check-in time with scheduled start time
- Configurable grace period (e.g., 15 minutes)
- Automatic status update to "Late"
- Late arrival notifications

#### 9. Overtime Tracking
- Track hours beyond scheduled work time
- Overtime calculation
- Overtime approval workflow
- Overtime reports

#### 10. Reports & Analytics
- Monthly attendance summary
- Individual staff attendance report
- Department/team attendance (if applicable)
- Punctuality analysis
- Absence patterns
- Leave utilization reports

### Optional Features (Phase 3)

#### 11. Biometric Integration
- Face recognition check-in
- Fingerprint scanner support
- QR code check-in
- GPS location tracking

#### 12. Mobile App Support
- API for mobile check-in
- Push notifications
- Mobile dashboard

#### 13. Payroll Integration
- Link attendance to payroll calculations
- Deductions for absences
- Overtime pay calculation

---

## Database Design

### Attendances Table

```php
Schema::create('attendances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->date('date');
    $table->time('check_in')->nullable();
    $table->time('check_out')->nullable();
    $table->enum('status', ['present', 'late', 'absent', 'leave', 'half_day'])->default('absent');
    $table->decimal('hours_worked', 5, 2)->nullable(); // Calculated field
    $table->text('notes')->nullable();
    $table->boolean('is_manual_entry')->default(false);
    $table->timestamps();

    // Indexes
    $table->unique(['user_id', 'date']); // One record per staff per day
    $table->index(['date', 'status']);
    $table->index('user_id');
});
```

**Field Descriptions:**
- `user_id` - Foreign key to users table (staff member)
- `date` - Attendance date (YYYY-MM-DD)
- `check_in` - Check-in time (HH:MM:SS)
- `check_out` - Check-out time (HH:MM:SS)
- `status` - Attendance status (enum)
- `hours_worked` - Total hours (calculated from check-in/out)
- `notes` - Optional notes/reason
- `is_manual_entry` - Flag for admin-created records
- Unique constraint ensures one attendance record per staff per day

---

### Leave Requests Table (Phase 2)

```php
Schema::create('leave_requests', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->enum('leave_type', ['sick', 'vacation', 'personal', 'unpaid', 'emergency']);
    $table->date('start_date');
    $table->date('end_date');
    $table->integer('days_count'); // Calculated: end_date - start_date + 1
    $table->text('reason');
    $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
    $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('approved_at')->nullable();
    $table->text('rejection_reason')->nullable();
    $table->timestamps();

    // Indexes
    $table->index(['user_id', 'status']);
    $table->index('start_date');
});
```

---

### Work Schedules Table (Phase 2)

```php
Schema::create('work_schedules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']);
    $table->time('start_time'); // e.g., 09:00:00
    $table->time('end_time');   // e.g., 17:00:00
    $table->integer('grace_period_minutes')->default(15); // Late threshold
    $table->boolean('is_working_day')->default(true);
    $table->timestamps();

    // Unique constraint: one schedule per staff per day
    $table->unique(['user_id', 'day_of_week']);
});
```

---

### Leave Balances Table (Phase 2)

```php
Schema::create('leave_balances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->integer('year'); // e.g., 2025
    $table->enum('leave_type', ['sick', 'vacation', 'personal']);
    $table->integer('total_days')->default(0);   // Annual allocation
    $table->integer('used_days')->default(0);    // Days taken
    $table->integer('remaining_days')->default(0); // total - used
    $table->timestamps();

    // Unique constraint
    $table->unique(['user_id', 'year', 'leave_type']);
});
```

---

## Models & Relationships

### Attendance Model

**File:** `app/Models/Attendance.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

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
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'hours_worked' => 'decimal:2',
        'is_manual_entry' => 'boolean',
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
     * Perform check-in
     */
    public function checkIn($time = null)
    {
        $checkInTime = $time ? Carbon::parse($time) : now();

        $this->check_in = $checkInTime;

        // Determine if late based on work schedule
        if ($this->isLateCheckIn($checkInTime)) {
            $this->status = 'late';
        } else {
            $this->status = 'present';
        }

        $this->save();

        return $this;
    }

    /**
     * Perform check-out and calculate hours
     */
    public function checkOut($time = null)
    {
        $checkOutTime = $time ? Carbon::parse($time) : now();

        $this->check_out = $checkOutTime;
        $this->hours_worked = $this->calculateHoursWorked();
        $this->save();

        return $this;
    }

    /**
     * Check if check-in time is late (Phase 2 - requires WorkSchedule)
     */
    protected function isLateCheckIn($checkInTime)
    {
        // TODO: Compare with user's work schedule
        // For now, simple logic: after 9:15 AM is late
        $scheduledStart = Carbon::parse($this->date->format('Y-m-d') . ' 09:00:00');
        $gracePeriod = 15; // minutes

        return $checkInTime->greaterThan($scheduledStart->addMinutes($gracePeriod));
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
}
```

---

### Update User Model

**File:** `app/Models/User.php`

Add relationship to Attendance:

```php
/**
 * User has many attendance records
 */
public function attendances()
{
    return $this->hasMany(Attendance::class);
}

/**
 * Get today's attendance record
 */
public function todaysAttendance()
{
    return $this->attendances()->whereDate('date', today())->first();
}

/**
 * Check if user has checked in today
 */
public function hasCheckedInToday()
{
    return Attendance::hasCheckedInToday($this->id);
}

/**
 * Get attendance for specific month
 */
public function attendanceForMonth($year, $month)
{
    return $this->attendances()
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->orderBy('date')
                ->get();
}

/**
 * Get attendance summary statistics
 */
public function getAttendanceSummary($startDate, $endDate)
{
    $attendances = $this->attendances()
                        ->betweenDates($startDate, $endDate)
                        ->get();

    return [
        'total_days' => $attendances->count(),
        'present' => $attendances->where('status', 'present')->count(),
        'late' => $attendances->where('status', 'late')->count(),
        'absent' => $attendances->where('status', 'absent')->count(),
        'leave' => $attendances->where('status', 'leave')->count(),
        'half_day' => $attendances->where('status', 'half_day')->count(),
        'total_hours' => $attendances->sum('hours_worked'),
    ];
}
```

---

## Controllers & Business Logic

### AttendanceController

**File:** `app/Http/Controllers/AttendanceController.php`

```php
<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display a listing of attendance records
     */
    public function index(Request $request)
    {
        $this->authorize('view attendances');

        // Get filter parameters
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $userId = $request->input('user_id');
        $status = $request->input('status');

        // Build query
        $query = Attendance::with('user')
                           ->betweenDates($startDate, $endDate)
                           ->orderBy('date', 'desc')
                           ->orderBy('check_in', 'desc');

        // Apply filters
        if ($userId) {
            $query->forUser($userId);
        }

        if ($status) {
            $query->byStatus($status);
        }

        // Get staff members for filter dropdown
        $staffMembers = User::where('role', 'staff')
                           ->where('status', 'active')
                           ->orderBy('name')
                           ->get();

        // Paginate results
        $attendances = $query->paginate(20)->appends($request->query());

        return view('attendance.index', compact(
            'attendances',
            'staffMembers',
            'startDate',
            'endDate',
            'userId',
            'status'
        ));
    }

    /**
     * Show the form for creating a new attendance record (manual entry)
     */
    public function create()
    {
        $this->authorize('create attendances');

        $staffMembers = User::where('role', 'staff')
                           ->where('status', 'active')
                           ->orderBy('name')
                           ->get();

        return view('attendance.create', compact('staffMembers'));
    }

    /**
     * Store a newly created attendance record (manual entry)
     */
    public function store(Request $request)
    {
        $this->authorize('create attendances');

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i|after:check_in',
            'status' => 'required|in:present,late,absent,leave,half_day',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check for duplicate
        $exists = Attendance::where('user_id', $validated['user_id'])
                           ->where('date', $validated['date'])
                           ->exists();

        if ($exists) {
            return redirect()->back()
                           ->withInput()
                           ->withErrors(['date' => 'Attendance record already exists for this date']);
        }

        // Create attendance record
        $attendance = new Attendance($validated);
        $attendance->is_manual_entry = true;

        // Calculate hours if both check-in and check-out provided
        if ($validated['check_in'] && $validated['check_out']) {
            $checkIn = Carbon::parse($validated['date'] . ' ' . $validated['check_in']);
            $checkOut = Carbon::parse($validated['date'] . ' ' . $validated['check_out']);
            $attendance->hours_worked = round($checkIn->diffInMinutes($checkOut) / 60, 2);
        }

        $attendance->save();

        return redirect()->route('attendance.index')
                        ->with('success', 'Attendance record created successfully');
    }

    /**
     * Display the specified attendance record
     */
    public function show(Attendance $attendance)
    {
        $this->authorize('view attendances');

        $attendance->load('user');

        return view('attendance.show', compact('attendance'));
    }

    /**
     * Show the form for editing the specified attendance record
     */
    public function edit(Attendance $attendance)
    {
        $this->authorize('edit attendances');

        $staffMembers = User::where('role', 'staff')
                           ->where('status', 'active')
                           ->orderBy('name')
                           ->get();

        return view('attendance.edit', compact('attendance', 'staffMembers'));
    }

    /**
     * Update the specified attendance record
     */
    public function update(Request $request, Attendance $attendance)
    {
        $this->authorize('edit attendances');

        $validated = $request->validate([
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i|after:check_in',
            'status' => 'required|in:present,late,absent,leave,half_day',
            'notes' => 'nullable|string|max:500',
        ]);

        // Update attendance
        $attendance->fill($validated);

        // Recalculate hours if both times provided
        if ($validated['check_in'] && $validated['check_out']) {
            $checkIn = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $validated['check_in']);
            $checkOut = Carbon::parse($attendance->date->format('Y-m-d') . ' ' . $validated['check_out']);
            $attendance->hours_worked = round($checkIn->diffInMinutes($checkOut) / 60, 2);
        }

        $attendance->save();

        return redirect()->route('attendance.index')
                        ->with('success', 'Attendance record updated successfully');
    }

    /**
     * Remove the specified attendance record
     */
    public function destroy(Attendance $attendance)
    {
        $this->authorize('delete attendances');

        $attendance->delete();

        return redirect()->route('attendance.index')
                        ->with('success', 'Attendance record deleted successfully');
    }

    /**
     * Staff check-in endpoint
     */
    public function checkIn(Request $request)
    {
        $user = auth()->user();

        // Check if already checked in today
        if (Attendance::hasCheckedInToday($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked in today'
            ], 400);
        }

        // Get or create today's attendance
        $attendance = Attendance::getOrCreateToday($user->id);

        // Perform check-in
        $attendance->checkIn();

        return response()->json([
            'success' => true,
            'message' => 'Checked in successfully at ' . $attendance->check_in_formatted,
            'attendance' => $attendance
        ]);
    }

    /**
     * Staff check-out endpoint
     */
    public function checkOut(Request $request)
    {
        $user = auth()->user();

        // Check if checked out already
        if (Attendance::hasCheckedOutToday($user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'You have already checked out today'
            ], 400);
        }

        // Get today's attendance
        $attendance = Attendance::where('user_id', $user->id)
                                ->whereDate('date', today())
                                ->first();

        if (!$attendance || !$attendance->check_in) {
            return response()->json([
                'success' => false,
                'message' => 'You must check in first'
            ], 400);
        }

        // Perform check-out
        $attendance->checkOut();

        return response()->json([
            'success' => true,
            'message' => 'Checked out successfully at ' . $attendance->check_out_formatted,
            'hours_worked' => $attendance->hours_worked,
            'attendance' => $attendance
        ]);
    }

    /**
     * Dashboard widget - today's attendance overview
     */
    public function todayOverview()
    {
        $this->authorize('view attendances');

        $today = today();

        $stats = [
            'total_staff' => User::where('role', 'staff')->where('status', 'active')->count(),
            'present' => Attendance::today()->byStatus('present')->count(),
            'late' => Attendance::today()->byStatus('late')->count(),
            'absent' => Attendance::today()->byStatus('absent')->count(),
            'leave' => Attendance::today()->byStatus('leave')->count(),
            'checked_in' => Attendance::today()->checkedIn()->count(),
        ];

        // Recent check-ins (last 10)
        $recentCheckIns = Attendance::with('user')
                                   ->today()
                                   ->whereNotNull('check_in')
                                   ->orderBy('check_in', 'desc')
                                   ->limit(10)
                                   ->get();

        return view('attendance.today-overview', compact('stats', 'recentCheckIns'));
    }

    /**
     * Staff member's personal attendance page
     */
    public function myAttendance()
    {
        $user = auth()->user();

        // Current month's attendance
        $attendances = $user->attendanceForMonth(now()->year, now()->month);

        // Get summary statistics
        $summary = $user->getAttendanceSummary(
            now()->startOfMonth(),
            now()->endOfMonth()
        );

        // Today's attendance
        $todayAttendance = $user->todaysAttendance();

        return view('attendance.my-attendance', compact(
            'attendances',
            'summary',
            'todayAttendance'
        ));
    }
}
```

---

## Routes & Middleware

**File:** `routes/web.php`

```php
// Attendance routes - Admin only (MUST be before catch-all routes)
Route::middleware(['auth', 'can:view attendances'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])
        ->name('attendance.index');

    Route::get('/attendance/create', [AttendanceController::class, 'create'])
        ->middleware('can:create attendances')
        ->name('attendance.create');

    Route::post('/attendance', [AttendanceController::class, 'store'])
        ->middleware('can:create attendances')
        ->name('attendance.store');

    Route::get('/attendance/{attendance}', [AttendanceController::class, 'show'])
        ->name('attendance.show');

    Route::get('/attendance/{attendance}/edit', [AttendanceController::class, 'edit'])
        ->middleware('can:edit attendances')
        ->name('attendance.edit');

    Route::put('/attendance/{attendance}', [AttendanceController::class, 'update'])
        ->middleware('can:edit attendances')
        ->name('attendance.update');

    Route::delete('/attendance/{attendance}', [AttendanceController::class, 'destroy'])
        ->middleware('can:delete attendances')
        ->name('attendance.destroy');

    // Dashboard widget
    Route::get('/attendance/today/overview', [AttendanceController::class, 'todayOverview'])
        ->name('attendance.today-overview');
});

// Staff check-in/check-out - All authenticated users
Route::middleware(['auth'])->group(function () {
    Route::post('/attendance/check-in', [AttendanceController::class, 'checkIn'])
        ->name('attendance.check-in');

    Route::post('/attendance/check-out', [AttendanceController::class, 'checkOut'])
        ->name('attendance.check-out');

    Route::get('/my-attendance', [AttendanceController::class, 'myAttendance'])
        ->name('attendance.my-attendance');
});
```

---

## Views & UI Components

### Index View (List)

**File:** `resources/views/attendance/index.blade.php`

```blade
@extends('layouts.vertical', ['title' => 'Attendance Management'])

@section('css')
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Attendance Records'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Attendance Records</h4>
                    <p class="text-muted mb-0">View and manage staff attendance</p>
                </div>
                <div class="card-header border-light justify-content-between">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('attendance.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control"
                                   value="{{ $startDate }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control"
                                   value="{{ $endDate }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Staff Member</label>
                            <select name="user_id" class="form-select">
                                <option value="">All Staff</option>
                                @foreach($staffMembers as $staff)
                                <option value="{{ $staff->id }}" {{ $userId == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="present" {{ $status == 'present' ? 'selected' : '' }}>Present</option>
                                <option value="late" {{ $status == 'late' ? 'selected' : '' }}>Late</option>
                                <option value="absent" {{ $status == 'absent' ? 'selected' : '' }}>Absent</option>
                                <option value="leave" {{ $status == 'leave' ? 'selected' : '' }}>On Leave</option>
                                <option value="half_day" {{ $status == 'half_day' ? 'selected' : '' }}>Half Day</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti ti-filter"></i>
                            </button>
                        </div>
                    </form>

                    <!-- Actions -->
                    <div class="d-flex gap-2 mt-3">
                        @can('create attendances')
                        <a href="{{ route('attendance.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Add Attendance
                        </a>
                        @endcan
                        <button class="btn btn-success" onclick="exportToExcel()">
                            <i class="ti ti-file-excel me-1"></i> Export Excel
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover table-centered mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Staff Member</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Hours</th>
                                <th>Status</th>
                                <th>Notes</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($attendances as $attendance)
                            <tr>
                                <td>{{ $attendance->date_formatted }}</td>
                                <td>{{ $attendance->day_name }}</td>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-sm">
                                            @if($attendance->user->avatar)
                                            <img src="{{ asset('storage/avatars/' . $attendance->user->avatar) }}"
                                                 class="rounded-circle" alt="avatar">
                                            @else
                                            <span class="avatar-title rounded-circle bg-soft-primary text-primary">
                                                {{ substr($attendance->user->name, 0, 1) }}
                                            </span>
                                            @endif
                                        </div>
                                        <span class="fw-semibold">{{ $attendance->user->name }}</span>
                                    </div>
                                </td>
                                <td>{{ $attendance->check_in_formatted }}</td>
                                <td>{{ $attendance->check_out_formatted }}</td>
                                <td>{{ $attendance->hours_worked ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $attendance->status_badge }}-subtle text-{{ str_replace('bg-', '', $attendance->status_badge) }}">
                                        {{ $attendance->status_label }}
                                    </span>
                                    @if($attendance->is_manual_entry)
                                    <small class="text-muted">(Manual)</small>
                                    @endif
                                </td>
                                <td>
                                    <small class="text-muted">{{ Str::limit($attendance->notes, 30) }}</small>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('attendance.show', $attendance) }}"
                                           class="btn btn-light btn-icon btn-sm"
                                           title="View">
                                            <i class="ti ti-eye fs-lg"></i>
                                        </a>

                                        @can('edit attendances')
                                        <a href="{{ route('attendance.edit', $attendance) }}"
                                           class="btn btn-light btn-icon btn-sm"
                                           title="Edit">
                                            <i class="ti ti-edit fs-lg"></i>
                                        </a>
                                        @endcan

                                        @can('delete attendances')
                                        <form method="POST"
                                              action="{{ route('attendance.destroy', $attendance) }}"
                                              style="display: inline;">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="btn btn-danger btn-icon btn-sm"
                                                    onclick="return confirm('Are you sure?')"
                                                    title="Delete">
                                                <i class="ti ti-trash fs-lg"></i>
                                            </button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <div class="d-flex flex-column align-items-center">
                                        <i class="ti ti-clock-exclamation fs-48 text-muted mb-3"></i>
                                        <h5 class="text-muted">No attendance records found</h5>
                                        <p class="text-muted">Try adjusting your filters or date range</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            Showing {{ $attendances->firstItem() ?? 0 }} to {{ $attendances->lastItem() ?? 0 }}
                            of {{ $attendances->total() }} entries
                        </div>
                        <div>
                            {{ $attendances->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
function exportToExcel() {
    // TODO: Implement Excel export
    alert('Excel export feature coming soon');
}
</script>
@endsection
```

---

### Check-In Dashboard Widget

**File:** `resources/views/dashboard-widgets/check-in.blade.php`

```blade
<div class="col-md-6 col-xl-4">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-3">
                <i class="ti ti-clock-check text-primary me-2"></i>
                My Attendance Today
            </h5>

            @php
                $todayAttendance = auth()->user()->todaysAttendance();
            @endphp

            @if($todayAttendance && $todayAttendance->check_in)
                <!-- Already checked in -->
                <div class="alert alert-success mb-3">
                    <i class="ti ti-check-circle me-2"></i>
                    Checked in at <strong>{{ $todayAttendance->check_in_formatted }}</strong>
                </div>

                <div class="d-flex justify-content-between mb-3">
                    <div>
                        <small class="text-muted">Status</small>
                        <p class="mb-0">
                            <span class="badge {{ $todayAttendance->status_badge }}">
                                {{ $todayAttendance->status_label }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <small class="text-muted">Hours</small>
                        <p class="mb-0 fw-semibold">
                            {{ $todayAttendance->hours_worked ?? 'In Progress' }}
                        </p>
                    </div>
                </div>

                @if(!$todayAttendance->check_out)
                <!-- Check-out button -->
                <button class="btn btn-danger w-100" onclick="performCheckOut()">
                    <i class="ti ti-logout me-1"></i> Check Out
                </button>
                @else
                <!-- Already checked out -->
                <div class="alert alert-info mb-0">
                    <i class="ti ti-clock-stop me-2"></i>
                    Checked out at <strong>{{ $todayAttendance->check_out_formatted }}</strong>
                </div>
                @endif

            @else
                <!-- Not checked in yet -->
                <div class="alert alert-warning mb-3">
                    <i class="ti ti-alert-triangle me-2"></i>
                    You haven't checked in today
                </div>

                <button class="btn btn-success w-100" onclick="performCheckIn()">
                    <i class="ti ti-login me-1"></i> Check In Now
                </button>
            @endif

            <a href="{{ route('attendance.my-attendance') }}" class="btn btn-light w-100 mt-2">
                View My Attendance
            </a>
        </div>
    </div>
</div>

<script>
function performCheckIn() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    fetch('{{ route('attendance.check-in') }}', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Checked In!',
                text: data.message,
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Failed to check in. Please try again.'
        });
    });
}

function performCheckOut() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    Swal.fire({
        title: 'Check Out',
        text: 'Are you sure you want to check out?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, Check Out',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('{{ route('attendance.check-out') }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Checked Out!',
                        html: `${data.message}<br>Hours worked: <strong>${data.hours_worked}</strong>`,
                        confirmButtonText: 'OK'
                    }).then(() => {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message
                    });
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to check out. Please try again.'
                });
            });
        }
    });
}
</script>
```

---

## Permissions & Authorization

### Permission Seeder

**File:** `database/seeders/AttendancePermissionSeeder.php`

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AttendancePermissionSeeder extends Seeder
{
    public function run()
    {
        // Create permissions
        $permissions = [
            'view attendances',
            'create attendances',
            'edit attendances',
            'delete attendances',
            'view own attendance', // Staff can view their own
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Assign to Administrator role
        $adminRole = Role::where('name', 'administrator')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissions);
        }

        // Assign limited permissions to Staff role
        $staffRole = Role::where('name', 'staff')->first();
        if ($staffRole) {
            $staffRole->givePermissionTo('view own attendance');
        }
    }
}
```

Run: `php artisan db:seed --class=AttendancePermissionSeeder`

---

## Check-In/Check-Out Flow

### Flow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                    STAFF CHECK-IN FLOW                          │
└─────────────────────────────────────────────────────────────────┘

1. Staff opens dashboard
   └─> Check-in widget displays

2. Click "Check In Now" button
   └─> JavaScript: performCheckIn()
       └─> POST /attendance/check-in
           └─> AttendanceController@checkIn()
               ├─> Verify not already checked in
               ├─> Get or create today's attendance record
               ├─> Set check_in = now()
               ├─> Determine status (present/late)
               └─> Return JSON response

3. Success response
   └─> Show SweetAlert success message
   └─> Reload page to update widget

┌─────────────────────────────────────────────────────────────────┐
│                    STAFF CHECK-OUT FLOW                         │
└─────────────────────────────────────────────────────────────────┘

1. Staff has already checked in
   └─> Widget shows "Check Out" button

2. Click "Check Out" button
   └─> Show confirmation dialog
       └─> JavaScript: performCheckOut()
           └─> POST /attendance/check-out
               └─> AttendanceController@checkOut()
                   ├─> Verify checked in
                   ├─> Set check_out = now()
                   ├─> Calculate hours_worked
                   └─> Return JSON response

3. Success response
   └─> Show SweetAlert with hours worked
   └─> Reload page to update widget
```

---

## Reports & Analytics

### Monthly Report View

**File:** `resources/views/attendance/reports/monthly.blade.php`

Features:
- Calendar heatmap showing attendance status
- Summary statistics (present, late, absent, leave days)
- Individual staff breakdown
- Export to PDF/Excel

### Attendance Summary Report

Generate summary for specific period:

```php
public function monthlySummary(Request $request)
{
    $year = $request->input('year', now()->year);
    $month = $request->input('month', now()->month);

    $staffMembers = User::where('role', 'staff')
                       ->where('status', 'active')
                       ->get();

    $report = [];

    foreach ($staffMembers as $staff) {
        $summary = $staff->getAttendanceSummary(
            Carbon::create($year, $month, 1)->startOfMonth(),
            Carbon::create($year, $month, 1)->endOfMonth()
        );

        $report[] = [
            'staff' => $staff,
            'summary' => $summary,
        ];
    }

    return view('attendance.reports.monthly', compact('report', 'year', 'month'));
}
```

---

## Settings & Configuration

### Attendance Settings

Store in `settings` table:

```php
// Work hours configuration
Setting::set('attendance.work_start_time', '09:00', 'string');
Setting::set('attendance.work_end_time', '17:00', 'string');
Setting::set('attendance.grace_period_minutes', 15, 'integer');

// Weekend configuration
Setting::set('attendance.weekend_days', ['saturday', 'sunday'], 'array');

// Leave balances (annual allocation)
Setting::set('attendance.annual_sick_leave', 10, 'integer');
Setting::set('attendance.annual_vacation_leave', 15, 'integer');
Setting::set('attendance.annual_personal_leave', 5, 'integer');

// Overtime configuration
Setting::set('attendance.overtime_enabled', true, 'boolean');
Setting::set('attendance.overtime_multiplier', 1.5, 'decimal');
```

---

## Implementation Phases

### Phase 1: Core Attendance (Week 1)
**Priority: MUST HAVE**

1. **Database Setup** (Day 1)
   - Create attendances migration
   - Create Attendance model
   - Update User model with relationships
   - Run migration

2. **Controller & Routes** (Day 2)
   - Create AttendanceController
   - Implement CRUD methods
   - Add check-in/check-out endpoints
   - Define routes

3. **Views - List & Forms** (Day 3-4)
   - Create index view with filters
   - Create create/edit forms
   - Create show view
   - Implement DataTable

4. **Dashboard Widget** (Day 5)
   - Create check-in widget
   - Add to dashboard
   - Test check-in/check-out flow

5. **Permissions** (Day 5)
   - Create permission seeder
   - Assign to roles
   - Test authorization

### Phase 2: Leave Management (Week 2)
**Priority: SHOULD HAVE**

1. **Database** (Day 1)
   - Create leave_requests migration
   - Create leave_balances migration
   - Create LeaveRequest model
   - Create LeaveBalance model

2. **Leave CRUD** (Day 2-3)
   - Create LeaveController
   - Implement request submission
   - Implement approval workflow
   - Create views

3. **Integration** (Day 4)
   - Link leave requests to attendance
   - Auto-create attendance records for approved leaves
   - Update dashboard

### Phase 3: Work Schedules & Reports (Week 3)
**Priority: NICE TO HAVE**

1. **Work Schedules** (Day 1-2)
   - Create work_schedules migration
   - Create WorkSchedule model
   - Implement schedule management
   - Auto-calculate late status

2. **Reports** (Day 3-5)
   - Monthly summary report
   - Individual staff report
   - Punctuality analysis
   - Export to Excel/PDF

---

## Testing Plan

### Unit Tests

**File:** `tests/Unit/AttendanceTest.php`

```php
public function test_attendance_can_check_in()
public function test_attendance_can_check_out()
public function test_hours_worked_calculation()
public function test_late_detection()
public function test_duplicate_check_in_prevented()
public function test_status_badge_colors()
```

### Feature Tests

**File:** `tests/Feature/AttendanceControllerTest.php`

```php
public function test_admin_can_view_attendance_list()
public function test_staff_cannot_view_all_attendance()
public function test_staff_can_check_in()
public function test_staff_can_check_out()
public function test_manual_entry_creates_attendance()
public function test_attendance_can_be_updated()
public function test_attendance_can_be_deleted()
```

### Manual Testing Checklist

- [ ] Admin can view all attendance records
- [ ] Admin can filter by date range, staff, status
- [ ] Admin can manually create attendance record
- [ ] Admin can edit attendance record
- [ ] Admin can delete attendance record
- [ ] Staff can check in (only once per day)
- [ ] Staff can check out (only after check-in)
- [ ] Staff can view own attendance history
- [ ] Hours worked calculated correctly
- [ ] Late status detected automatically
- [ ] Dashboard widget updates correctly
- [ ] Pagination works
- [ ] Date filters work
- [ ] Status badges display correctly
- [ ] Permissions enforced correctly

---

## Future Enhancements

### Advanced Features

1. **Geolocation Tracking**
   - Capture GPS coordinates on check-in/out
   - Verify staff is at work location
   - Geofencing for remote workers

2. **Biometric Integration**
   - Face recognition check-in
   - Fingerprint scanner support
   - QR code scanning

3. **Mobile App**
   - Native iOS/Android apps
   - Push notifications for check-in reminders
   - Offline support

4. **Advanced Analytics**
   - Predictive absence analytics
   - Team productivity insights
   - Attendance trends over time

5. **Shift Management**
   - Rotating shifts
   - Shift swapping
   - Shift scheduling calendar

6. **Payroll Integration**
   - Auto-calculate pay based on hours
   - Overtime calculations
   - Deductions for absences

7. **Notifications**
   - Email reminder to check in
   - SMS notifications
   - Late arrival alerts to managers

8. **Team Features**
   - Department-wise attendance
   - Team lead approvals
   - Branch-wise tracking

---

## File Structure Summary

```
app/
├── Models/
│   ├── Attendance.php (new)
│   ├── LeaveRequest.php (Phase 2)
│   ├── WorkSchedule.php (Phase 3)
│   └── User.php (updated)
├── Http/
│   └── Controllers/
│       ├── AttendanceController.php (new)
│       └── LeaveController.php (Phase 2)

database/
├── migrations/
│   ├── YYYY_MM_DD_create_attendances_table.php (new)
│   ├── YYYY_MM_DD_create_leave_requests_table.php (Phase 2)
│   ├── YYYY_MM_DD_create_work_schedules_table.php (Phase 3)
│   └── YYYY_MM_DD_create_leave_balances_table.php (Phase 2)
├── seeders/
│   └── AttendancePermissionSeeder.php (new)

resources/
├── views/
│   ├── attendance/
│   │   ├── index.blade.php (new)
│   │   ├── create.blade.php (new)
│   │   ├── edit.blade.php (new)
│   │   ├── show.blade.php (new)
│   │   ├── my-attendance.blade.php (new)
│   │   └── reports/
│   │       └── monthly.blade.php (Phase 3)
│   └── dashboard-widgets/
│       └── check-in.blade.php (new)

routes/
└── web.php (updated)

tests/
├── Unit/
│   └── AttendanceTest.php (new)
└── Feature/
    └── AttendanceControllerTest.php (new)
```

---

## Estimated Timeline

**Phase 1 (Core):** 5 working days
**Phase 2 (Leave):** 4 working days
**Phase 3 (Schedules & Reports):** 5 working days

**Total:** 14 working days (~3 weeks)

---

## Conclusion

This comprehensive plan provides a complete staff attendance system that integrates seamlessly with the existing Laravel application. The system follows established patterns, uses existing UI components, and builds upon the current user/role management infrastructure.

**Key Benefits:**
- ✅ Easy check-in/check-out for staff
- ✅ Comprehensive attendance tracking
- ✅ Automated late detection
- ✅ Leave management workflow
- ✅ Detailed reports and analytics
- ✅ Permission-based access control
- ✅ Mobile-friendly interface
- ✅ Scalable architecture for future enhancements

**Next Steps:**
1. Review and approve this plan
2. Set up development environment
3. Begin Phase 1 implementation
4. Test thoroughly
5. Deploy to production
6. Gather feedback for Phase 2
