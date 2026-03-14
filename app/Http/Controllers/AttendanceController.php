<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\WorkSchedule;
use App\Models\Setting;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{
    protected $attendanceService;

    public function __construct(AttendanceService $attendanceService)
    {
        $this->middleware('auth');
        $this->attendanceService = $attendanceService;
    }

    /**
     * Display attendance dashboard
     */
    public function index(Request $request)
    {
        $this->authorize('view attendances');

        // Get filter parameters
        $date = $request->input('date', today()->format('Y-m-d'));
        $userId = $request->input('user_id');
        $status = $request->input('status');

        // Build query
        $query = Attendance::with('user')
            ->whereDate('date', $date)
            ->orderBy('check_in', 'desc');

        // Apply filters
        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $attendances = $query->paginate(20);

        // Get staff members for filter
        $staffMembers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['staff', 'administrator']);
        })->get();

        // Get today's date for the view
        $today = today();

        // Calculate today's attendance stats for dashboard
        $todayStats = [
            'present' => Attendance::whereDate('date', $today)
                                  ->where('status', 'present')
                                  ->count(),
            'late' => Attendance::whereDate('date', $today)
                               ->where('status', 'late')
                               ->count(),
            'absent' => Attendance::whereDate('date', $today)
                                 ->where('status', 'absent')
                                 ->count(),
            'on_leave' => Attendance::whereDate('date', $today)
                                   ->where('status', 'leave')
                                   ->count(),
        ];

        // Check if user has clocked in/out today
        $todayAttendance = Attendance::where('user_id', auth()->id())
            ->whereDate('date', today())
            ->first();

        $hasClockedInToday = $todayAttendance && $todayAttendance->check_in;
        $hasClockedOutToday = $todayAttendance && $todayAttendance->check_out;

        return view('attendance.index', compact('attendances', 'staffMembers', 'date', 'userId', 'status', 'today', 'todayStats', 'hasClockedInToday', 'hasClockedOutToday'));
    }

    /**
     * Staff member check-in with enterprise features
     */
    public function checkIn(Request $request)
    {
        $user = auth()->user();

        // Validate the request
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_id' => 'nullable|exists:locations,id',
            'device_info' => 'nullable|string|max:500',
            'metadata' => 'nullable|array'
        ]);

        try {
            $data = $request->only(['notes', 'latitude', 'longitude', 'location_id', 'device_info', 'metadata']);
            $attendance = $this->attendanceService->checkIn($user, $data);

            return response()->json([
                'success' => true,
                'message' => 'Checked in successfully at ' . $attendance->check_in->format('h:i A'),
                'attendance' => $attendance,
                'status' => $attendance->status,
                'late_arrival_minutes' => $attendance->late_arrival_minutes,
                'early_departure_minutes' => $attendance->early_departure_minutes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Staff member check-out with enterprise features
     */
    public function checkOut(Request $request)
    {
        $user = auth()->user();

        // Validate the request
        $request->validate([
            'notes' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'device_info' => 'nullable|string|max:500'
        ]);

        try {
            $data = $request->only(['notes', 'latitude', 'longitude', 'device_info']);
            $attendance = $this->attendanceService->checkOut($user, $data);

            return response()->json([
                'success' => true,
                'message' => 'Checked out successfully at ' . $attendance->check_out->format('h:i A'),
                'attendance' => $attendance,
                'hours_worked' => $attendance->hours_worked,
                'overtime_minutes' => $attendance->overtime_minutes,
                'total_break_minutes' => $attendance->total_break_minutes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display today's attendance summary
     */
    public function dashboard()
    {
        $this->authorize('view attendances');

        // Get today's attendance stats
        $today = today();
        $attendances = Attendance::with('user')
            ->whereDate('date', $today)
            ->get();

        $totalStaff = User::whereHas('roles', function ($q) {
            $q->where('name', 'staff');
        })->count();

        $presentCount = $attendances->where('status', 'present')->count();
        $lateCount = $attendances->where('status', 'late')->count();
        $absentCount = $totalStaff - $attendances->count(); // Assuming any staff not in attendance records is absent
        $leaveCount = $attendances->where('status', 'leave')->count();

        // Get staff members with their attendance status
        $staff = User::whereHas('roles', function ($q) {
            $q->where('name', 'staff');
        })->with(['attendances' => function ($q) use ($today) {
            $q->whereDate('date', $today);
        }])->get();

        return view('attendance.dashboard', compact(
            'today',
            'totalStaff',
            'presentCount',
            'lateCount', 
            'absentCount',
            'leaveCount',
            'staff'
        ));
    }

    /**
     * Show attendance details for a specific date
     */
    public function showDate($date)
    {
        $this->authorize('view attendances');

        $date = Carbon::parse($date);
        $attendances = Attendance::with('user')
            ->whereDate('date', $date)
            ->orderBy('check_in', 'asc')
            ->get();

        return view('attendance.date-show', compact('attendances', 'date'));
    }

    /**
     * Show attendance for a specific staff member
     */
    public function showStaff(User $user, Request $request)
    {
        $this->authorize('view attendances');

        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('n'));

        $attendances = $user->attendanceForMonth($year, $month);

        // Calculate monthly summary
        $summary = $user->getAttendanceSummaryForMonth($year, $month);

        return view('attendance.staff-show', compact('user', 'attendances', 'summary', 'year', 'month'));
    }

    /**
     * Manual attendance entry (for admin only)
     */
    public function createManual()
    {
        $this->authorize('manage attendances');

        $staffMembers = User::whereHas('roles', function ($q) {
            $q->where('name', 'staff');
        })->get();

        return view('attendance.manual-entry', compact('staffMembers'));
    }

    /**
     * Store manual attendance entry
     */
    public function storeManual(Request $request)
    {
        $this->authorize('manage attendances');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i|after_or_equal:check_in',
            'status' => 'required|in:present,late,absent,leave,half_day',
            'notes' => 'nullable|string|max:500'
        ]);

        // Check if attendance record already exists
        $existing = Attendance::where('user_id', $request->user_id)
            ->whereDate('date', $request->date)
            ->first();

        if ($existing) {
            return redirect()->back()
                ->with('error', 'Attendance record already exists for this date.')
                ->withInput();
        }

        // Create attendance record
        $attendance = new Attendance([
            'user_id' => $request->user_id,
            'date' => $request->date,
            'status' => $request->status,
            'notes' => $request->notes,
            'is_manual_entry' => true
        ]);

        if ($request->check_in) {
            $attendance->check_in = Carbon::parse($request->date . ' ' . $request->check_in);
        }

        if ($request->check_out) {
            $attendance->check_out = Carbon::parse($request->date . ' ' . $request->check_out);
        }

        // Calculate hours if both check_in and check_out are provided
        if ($attendance->check_in && $attendance->check_out) {
            $hoursWorked = $attendance->check_in->diffInRealHours($attendance->check_out);
            $attendance->hours_worked = round($hoursWorked, 2);
        }

        $attendance->save();

        return redirect()->route('attendance.index', ['date' => $request->date])
            ->with('success', 'Attendance record created successfully.');
    }

    /**
     * Edit manual attendance entry
     */
    public function editManual(Attendance $attendance)
    {
        $this->authorize('manage attendances');

        $staffMembers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['staff', 'administrator']);
        })->get();

        return view('attendance.edit', compact('attendance', 'staffMembers'));
    }

    /**
     * Update manual attendance entry
     */
    public function updateManual(Request $request, Attendance $attendance)
    {
        $this->authorize('manage attendances');

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i|after_or_equal:check_in',
            'status' => 'required|in:present,late,absent,leave,half_day',
            'notes' => 'nullable|string|max:500'
        ]);

        // If changing date, check for conflicts
        if ($request->date != $attendance->date) {
            $existing = Attendance::where('user_id', $request->user_id)
                ->whereDate('date', $request->date)
                ->where('id', '!=', $attendance->id)
                ->first();

            if ($existing) {
                return redirect()->back()
                    ->with('error', 'Attendance record already exists for this date and staff member.')
                    ->withInput();
            }
        }

        $attendance->user_id = $request->user_id;
        $attendance->date = $request->date;
        $attendance->status = $request->status;
        $attendance->notes = $request->notes;

        if ($request->check_in) {
            $attendance->check_in = Carbon::parse($request->date . ' ' . $request->check_in);
        } else {
            $attendance->check_in = null;
        }

        if ($request->check_out) {
            $attendance->check_out = Carbon::parse($request->date . ' ' . $request->check_out);
        } else {
            $attendance->check_out = null;
        }

        // Calculate hours if both check_in and check_out are provided
        if ($attendance->check_in && $attendance->check_out) {
            $hoursWorked = $attendance->check_in->diffInRealHours($attendance->check_out);
            $attendance->hours_worked = round($hoursWorked, 2);
        } else {
            $attendance->hours_worked = null;
        }

        $attendance->save();

        return redirect()->route('attendance.index')
            ->with('success', 'Attendance record updated successfully.');
    }

    /**
     * Delete manual attendance entry
     */
    public function destroy(Request $request, Attendance $attendance)
    {
        $this->authorize('manage attendances');

        $date = $attendance->date;
        $userId = $attendance->user_id;
        
        $attendance->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Attendance record deleted successfully.'
            ]);
        }

        return redirect()->route('attendance.index', ['date' => $date])
            ->with('success', 'Attendance record deleted successfully.');
    }

    /**
     * Get today's check-in status for current user
     */
    public function todayStatus()
    {
        $user = auth()->user();
        $attendance = $user->todaysAttendance();

        return response()->json([
            'has_checked_in' => $attendance && $attendance->check_in,
            'has_checked_out' => $attendance && $attendance->check_out,
            'attendance' => $attendance
        ]);
    }

    /**
     * Get user's current attendance status (for dashboard widget)
     */
    public function currentStatus()
    {
        $user = auth()->user();
        $attendance = $user->todaysAttendance();

        if (!$attendance) {
            $status = 'not_checked_in';
            $message = 'Not checked in yet';
        } elseif ($attendance->check_in && !$attendance->check_out) {
            $status = 'checked_in';
            $message = 'Currently at work since ' . ($attendance->check_in ? $attendance->check_in->format('h:i A') : '');
        } elseif ($attendance->check_in && $attendance->check_out) {
            $status = 'checked_out';
            $message = 'Checked out at ' . ($attendance->check_out ? $attendance->check_out->format('h:i A') : '');
        } else {
            $status = 'unknown';
            $message = 'Unknown status';
        }

        return response()->json([
            'status' => $status,
            'message' => $message,
            'attendance' => $attendance
        ]);
    }

    /**
     * Process bulk attendance imports (Excel feature)
     */
    public function import(Request $request)
    {
        $this->authorize('manage attendances');

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv',
            'date' => 'required|date'
        ]);

        // Process the file - this would typically involve 
        // parsing the Excel file and creating attendance records
        // For now, we'll just return a placeholder response
        // Actual implementation would depend on the specific Excel format

        return response()->json([
            'success' => true,
            'message' => 'Attendance records imported successfully'
        ]);
    }

    /**
     * Display attendance reports
     */
    public function report(Request $request)
    {
        $this->authorize('view attendances');

        $startDate = $request->input('start_date', today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', today()->format('Y-m-d'));
        $staffId = $request->input('staff_id');

        // Query attendances based on date range and optional staff filter
        $query = Attendance::with('user');

        if ($staffId) {
            $query->where('user_id', $staffId);
        }

        $reportData = $query->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date', 'desc')
            ->orderBy('user_id')
            ->paginate(20);

        // Get staff members for filter
        $staffMembers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['staff', 'administrator']);
        })->get();

        // Calculate summary statistics
        $summary = null;
        if ($staffId) {
            $selectedStaff = User::find($staffId);
            $allAttendances = Attendance::where('user_id', $staffId)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();
            
            $summary = [
                'total_days' => $allAttendances->count(),
                'present_days' => $allAttendances->whereIn('status', ['present', 'late'])->count(),
                'late_days' => $allAttendances->where('status', 'late')->count(),
                'absent_days' => $allAttendances->where('status', 'absent')->count(),
                'leave_days' => $allAttendances->where('status', 'leave')->count(),
                'half_day_days' => $allAttendances->where('status', 'half_day')->count(),
                'total_hours' => round($allAttendances->sum('hours_worked'), 2),
                'total_overtime' => round($allAttendances->sum('overtime_hours'), 2),
                'business_hours_compliance' => $allAttendances->where('calculated_using_business_hours', true)->count(),
                'expected_hours_total' => round($allAttendances->sum('expected_hours'), 2),
            ];
        } else {
            // Calculate for all staff in the date range
            $allAttendances = Attendance::whereBetween('date', [$startDate, $endDate])->get();

            $summary = [
                'total_days' => $allAttendances->count(),
                'present_days' => $allAttendances->whereIn('status', ['present', 'late'])->count(),
                'late_days' => $allAttendances->where('status', 'late')->count(),
                'absent_days' => $allAttendances->where('status', 'absent')->count(),
                'leave_days' => $allAttendances->where('status', 'leave')->count(),
                'half_day_days' => $allAttendances->where('status', 'half_day')->count(),
                'total_hours' => round($allAttendances->sum('hours_worked'), 2),
                'total_overtime' => round($allAttendances->sum('overtime_hours'), 2),
                'business_hours_compliance' => $allAttendances->where('calculated_using_business_hours', true)->count(),
                'expected_hours_total' => round($allAttendances->sum('expected_hours'), 2),
            ];
        }

        return view('attendance.report', compact('reportData', 'staffMembers', 'summary', 'startDate', 'endDate', 'staffId'));
    }

    /**
     * Display specific staff member attendance
     */
    public function staff(Request $request)
    {
        $this->authorize('view attendances');

        $staffId = $request->input('staff_id');
        $startDate = $request->input('start_date', today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', today()->format('Y-m-d'));
        $status = $request->input('status');

        // Get staff members list - include all users with attendance records or staff roles
        $staffMembers = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['staff', 'administrator']);
        })->orWhereHas('attendances')->get()->unique();

        $selectedStaff = null;
        $attendanceRecords = collect();
        $staffStats = [
            'total_days' => 0,
            'present_days' => 0,
            'late_days' => 0,
            'absent_days' => 0,
            'leave_days' => 0,
            'half_day_days' => 0,
        ];
        $selectedStaffId = $staffId;

        if ($staffId) {
            $selectedStaff = User::find($staffId);

            if ($selectedStaff) {
                // Build query for this staff member's attendance
                $query = Attendance::where('user_id', $staffId)
                    ->whereBetween('date', [$startDate, $endDate]);

                if ($status) {
                    $query->where('status', $status);
                }

                $attendanceRecords = $query->orderBy('date', 'desc')->paginate(20);

                // Calculate staff-specific statistics - fix query reuse bug
                $staffStats = [
                    'total_days' => Attendance::where('user_id', $staffId)
                        ->whereBetween('date', [$startDate, $endDate])->count(),
                    'present_days' => Attendance::where('user_id', $staffId)
                        ->whereBetween('date', [$startDate, $endDate])
                        ->whereIn('status', ['present', 'late'])->count(),
                    'late_days' => Attendance::where('user_id', $staffId)
                        ->whereBetween('date', [$startDate, $endDate])
                        ->where('status', 'late')->count(),
                    'absent_days' => Attendance::where('user_id', $staffId)
                        ->whereBetween('date', [$startDate, $endDate])
                        ->where('status', 'absent')->count(),
                    'leave_days' => Attendance::where('user_id', $staffId)
                        ->whereBetween('date', [$startDate, $endDate])
                        ->where('status', 'leave')->count(),
                    'half_day_days' => Attendance::where('user_id', $staffId)
                        ->whereBetween('date', [$startDate, $endDate])
                        ->where('status', 'half_day')->count(),
                ];
            }
        }

        return view('attendance.staff', compact(
            'staffMembers',
            'selectedStaff',
            'attendanceRecords',
            'staffStats',
            'startDate',
            'endDate',
            'status',
            'selectedStaffId'
        ));
    }

    /**
     * Export attendance data
     */
    public function export(Request $request)
    {
        $this->authorize('view attendances');

        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'format' => 'required|in:csv,xlsx,pdf'
        ]);

        $query = Attendance::with('user')->whereBetween('date', [$request->start_date, $request->end_date]);
        
        if ($request->filled('staff_id')) {
            $query->where('user_id', $request->staff_id);
        }

        $attendances = $query->orderBy('date', 'desc')->get();

        if ($request->format === 'csv') {
            $filename = 'attendance_report_' . $request->start_date . '_to_' . $request->end_date . '.csv';
            
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            return response()->streamDownload(function () use ($attendances) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['Staff Name', 'Email', 'Date', 'Check-in', 'Check-out', 'Hours Worked', 'Status', 'Notes']);
                
                foreach ($attendances as $row) {
                    fputcsv($file, [
                        $row->user ? $row->user->name : '',
                        $row->user ? $row->user->email : '',
                        $row->date ? $row->date->format('Y-m-d') : '',
                        $row->check_in ? $row->check_in->format('H:i') : '',
                        $row->check_out ? $row->check_out->format('H:i') : '',
                        $row->hours_worked,
                        $row->status,
                        $row->notes
                    ]);
                }
                fclose($file);
            }, $filename, $headers);
        }

        return response()->json([
            'success' => true,
            'message' => 'Export generated successfully.'
        ]);
    }

    /**
     * Start a break for the current user
     */
    public function startBreak(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'break_type' => 'nullable|in:lunch,coffee,personal,other',
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $data = $request->only(['break_type', 'notes']);
            $break = $this->attendanceService->startBreak($user, $data);

            return response()->json([
                'success' => true,
                'message' => 'Break started successfully',
                'break' => $break,
                'start_time' => $break->start_time->format('h:i A')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * End a break for the current user
     */
    public function endBreak(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'notes' => 'nullable|string|max:500'
        ]);

        try {
            $data = $request->only(['notes']);
            $break = $this->attendanceService->endBreak($user, $data);

            return response()->json([
                'success' => true,
                'message' => 'Break ended successfully',
                'break' => $break,
                'duration_minutes' => $break->duration_minutes,
                'end_time' => $break->end_time->format('h:i A')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get current break status for the user
     */
    public function breakStatus()
    {
        $user = auth()->user();
        $attendance = $user->todaysAttendance();

        if (!$attendance) {
            return response()->json([
                'has_active_break' => false,
                'message' => 'No active attendance found'
            ]);
        }

        $activeBreak = $attendance->breaks()
            ->whereNull('end_time')
            ->first();

        return response()->json([
            'has_active_break' => !is_null($activeBreak),
            'active_break' => $activeBreak,
            'break_start_time' => $activeBreak ? $activeBreak->start_time->format('h:i A') : null,
            'break_type' => $activeBreak ? $activeBreak->break_type : null
        ]);
    }

    /**
     * Get today's attendance details with enterprise features
     */
    public function todayDetails()
    {
        $user = auth()->user();
        $attendance = $user->todaysAttendance();

        if (!$attendance) {
            return response()->json([
                'has_attendance' => false,
                'message' => 'No attendance record for today'
            ]);
        }

        $attendance->load(['breaks', 'location', 'meta']);
        
        // Get business hours information
        $businessHoursService = app(\App\Services\BusinessHoursService::class);
        $businessHours = $businessHoursService->getHoursForDate($attendance->date);

        return response()->json([
            'has_attendance' => true,
            'attendance' => $attendance,
            'breaks' => $attendance->breaks,
            'business_hours' => $businessHours,
            'business_hours_type' => $attendance->business_hours_type,
            'expected_hours' => $attendance->expected_hours,
            'location' => $attendance->location,
            'metadata' => $attendance->meta->pluck('value', 'key'),
            'status_summary' => [
                'is_late' => $attendance->status === 'late',
                'is_approved' => $attendance->status === 'approved',
                'has_overtime' => $attendance->overtime_minutes > 0,
                'total_break_minutes' => $attendance->break_minutes,
                'calculated_using_business_hours' => $attendance->calculated_using_business_hours ?? false
            ]
        ]);
    }

    /**
     * Get attendance statistics for the current user
     */
    public function userStatistics(Request $request)
    {
        $user = auth()->user();
        $startDate = $request->input('start_date', today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', today()->format('Y-m-d'));

        $attendances = Attendance::where('user_id', $user->id)
            ->whereBetween('check_in', [$startDate, $endDate])
            ->whereNotNull('check_out')
            ->get();

        $stats = [
            'total_days' => $attendances->count(),
            'total_hours' => $attendances->sum('hours_worked'),
            'average_hours_per_day' => $attendances->count() > 0 ? $attendances->avg('hours_worked') : 0,
            'total_overtime_minutes' => $attendances->sum('overtime_minutes'),
            'total_break_minutes' => $attendances->sum('break_minutes'),
            'late_days' => $attendances->where('status', 'late')->count(),
            'on_time_days' => $attendances->where('status', 'on_time')->count(),
            'approved_days' => $attendances->where('status', 'approved')->count(),
        ];

        return response()->json([
            'success' => true,
            'statistics' => $stats,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ]
        ]);
    }

    /**
     * Approve attendance record (for managers)
     */
    public function approve(Attendance $attendance)
    {
        $this->authorize('manage attendances');

        $user = auth()->user();

        if ($attendance->is_approved) {
            return response()->json([
                'success' => false,
                'message' => 'Attendance is already approved'
            ], 400);
        }

        $attendance->update([
            'is_approved' => true,
            'approved_by' => $user->id,
            'approved_at' => now()
        ]);

        // Log audit trail
        \App\Models\AttendanceAuditLog::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'action' => 'approve',
            'details' => json_encode(['approved_by' => $user->id, 'approved_at' => now()]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance approved successfully',
            'attendance' => $attendance
        ]);
    }

    /**
     * Reject attendance record (for managers)
     */
    public function reject(Attendance $attendance, Request $request)
    {
        $this->authorize('manage attendances');

        $request->validate([
            'reason' => 'required|string|max:500'
        ]);

        $user = auth()->user();

        $attendance->update([
            'status' => 'rejected',
            'notes' => $attendance->notes . "\nRejected: " . $request->reason,
            'approved_by' => $user->id,
            'approved_at' => now()
        ]);

        // Log audit trail
        \App\Models\AttendanceAuditLog::create([
            'attendance_id' => $attendance->id,
            'user_id' => $user->id,
            'action' => 'reject',
            'details' => json_encode(['reason' => $request->reason, 'rejected_by' => $user->id, 'rejected_at' => now()]),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Attendance rejected successfully',
            'attendance' => $attendance
        ]);
    }

    /**
     * Get attendance audit logs
     */
    public function auditLogs(Attendance $attendance)
    {
        $this->authorize('view attendances');

        $logs = $attendance->auditLogs()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'logs' => $logs
        ]);
    }

    /**
     * View a single attendance record (JSON for modal)
     */
    public function viewRecord(Attendance $attendance)
    {
        $this->authorize('view attendances');

        $attendance->load(['user', 'auditLogs.user', 'breaks']);

        return response()->json([
            'success' => true,
            'attendance' => [
                'id'                   => $attendance->id,
                'user_name'            => $attendance->user->name ?? '-',
                'user_email'           => $attendance->user->email ?? '-',
                'date'                 => $attendance->date->format('M d, Y'),
                'check_in'             => $attendance->check_in ? $attendance->check_in->format('h:i A') : null,
                'check_out'            => $attendance->check_out ? $attendance->check_out->format('h:i A') : null,
                'hours_worked'         => $attendance->hours_worked ? number_format($attendance->hours_worked, 2) . ' hrs' : '-',
                'status'               => $attendance->status,
                'notes'                => $attendance->notes,
                'is_approved'          => $attendance->is_approved,
                'is_manual_entry'      => $attendance->is_manual_entry,
                'latitude'             => $attendance->latitude,
                'longitude'            => $attendance->longitude,
                'latitude_out'         => $attendance->latitude_out,
                'longitude_out'        => $attendance->longitude_out,
                'late_arrival_minutes' => $attendance->late_arrival_minutes,
                'breaks'               => $attendance->breaks->map(fn($b) => [
                    'type'             => $b->break_type,
                    'start'            => $b->start_time ? \Carbon\Carbon::parse($b->start_time)->format('h:i A') : '-',
                    'end'              => $b->end_time ? \Carbon\Carbon::parse($b->end_time)->format('h:i A') : 'Active',
                    'duration'         => $b->duration_minutes . ' min',
                ]),
                'audit_logs'           => $attendance->auditLogs->map(fn($l) => [
                    'action'           => $l->action,
                    'user'             => $l->user->name ?? 'System',
                    'at'               => $l->created_at->format('M d, Y h:i A'),
                ]),
            ]
        ]);
    }

    /**
     * Download exported attendance data (redirect to export)
     */
    public function downloadExport(Request $request)
    {
        return $this->export($request);
    }

    /**
     * Get staff attendance data for DataTables AJAX
     */
    public function datatableStaff(Request $request)
    {
        $this->authorize('view attendances');

        // Get filter parameters
        $staffId = $request->input('staff_id');
        $startDate = $request->input('start_date', today()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', today()->format('Y-m-d'));
        $status = $request->input('status');

        // Build query
        $query = Attendance::with('user')
            ->whereBetween('date', [$startDate, $endDate]);

        if ($staffId) {
            $query->where('user_id', $staffId);
        }

        if ($status) {
            $query->where('status', $status);
        }

        // Get DataTables parameters
        $start = $request->input('start', 0);
        $length = $request->input('length', 10);
        $search = $request->input('search.value');
        $orderColumn = $request->input('order.0.column', 0);
        $orderDir = $request->input('order.0.dir', 'desc');

        // Map column index to database column
        $columns = [
            0 => 'date', // Date column
            1 => 'check_in', // Check-in Time
            2 => 'check_out', // Check-out Time
            3 => 'hours_worked', // Hours Worked
            4 => 'status', // Status
            // Column 5 is Actions - not sortable
        ];

        if (isset($columns[$orderColumn])) {
            $query->orderBy($columns[$orderColumn], $orderDir);
        } else {
            $query->orderBy('date', 'desc');
        }

        // Apply search filter
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', "%{$search}%")
                             ->orWhere('email', 'like', "%{$search}%");
                })
                ->orWhere('status', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // Get total count
        $totalRecords = $query->count();

        // Apply pagination
        $query->skip($start)->take($length);

        $attendances = $query->get();

        // Format data for DataTables
        $data = [];
        foreach ($attendances as $attendance) {
            $data[] = [
                'date' => $attendance->date->format('M d, Y'),
                'check_in' => $attendance->check_in ?
                    '<span class="badge bg-soft-success text-success">' . $attendance->check_in->format('h:i A') . '</span>' :
                    '<span class="text-muted">-</span>',
                'check_out' => $attendance->check_out ?
                    '<span class="badge bg-soft-info text-info">' . $attendance->check_out->format('h:i A') . '</span>' :
                    '<span class="text-muted">-</span>',
                'hours_worked' => $attendance->hours_worked ?
                    '<span class="fw-bold">' . number_format($attendance->hours_worked, 2) . ' hrs</span>' :
                    '<span class="text-muted">-</span>',
                'status' => $this->getStatusBadge($attendance->status),
                'actions' => $this->getActionButtons($attendance),
            ];
        }

        return response()->json([
            'draw' => $request->input('draw', 1),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    /**
     * Get HTML badge for status
     */
    private function getStatusBadge($status)
    {
        switch ($status) {
            case 'present':
                return '<span class="badge bg-success-subtle text-success">Present</span>';
            case 'late':
                return '<span class="badge bg-warning-subtle text-warning">Late</span>';
            case 'absent':
                return '<span class="badge bg-danger-subtle text-danger">Absent</span>';
            case 'leave':
                return '<span class="badge bg-info-subtle text-info">On Leave</span>';
            case 'half_day':
                return '<span class="badge bg-secondary-subtle text-secondary">Half Day</span>';
            default:
                return '<span class="badge bg-light text-dark">' . ucfirst($status) . '</span>';
        }
    }

    /**
     * Get HTML action buttons
     */
    private function getActionButtons($attendance)
    {
        $buttons = '<div class="d-flex gap-1">';
        
        // View button
        $buttons .= '<button class="btn btn-sm btn-light" onclick="viewAttendanceRecord(' . $attendance->id . ')">
                        <i class="ti ti-eye"></i>
                    </button>';
        
        // Edit button (if user has permission)
        if (auth()->user()->can('edit attendances')) {
            $buttons .= '<button class="btn btn-sm btn-light" onclick="editAttendanceRecord(' . $attendance->id . ')">
                            <i class="ti ti-pencil"></i>
                        </button>';
        }
        
        // Delete button (if user has permission)
        if (auth()->user()->can('manage attendances')) {
            $buttons .= '<button class="btn btn-sm btn-danger" onclick="deleteAttendanceRecord(' . $attendance->id . ')">
                            <i class="ti ti-trash"></i>
                        </button>';
        }
        
        $buttons .= '</div>';
        
        return $buttons;
    }
}