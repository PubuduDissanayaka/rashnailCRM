@extends('layouts.vertical', ['title' => 'Attendance Management'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
    <style>
        /* Enhanced button processing state */
        .btn-processing {
            position: relative;
            opacity: 0.8;
            cursor: not-allowed !important;
        }
        
        .btn-processing::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.3);
            border-radius: inherit;
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% {
                opacity: 0.3;
            }
            50% {
                opacity: 0.6;
            }
            100% {
                opacity: 0.3;
            }
        }
        
        /* Success state for buttons */
        .btn-success-processing {
            background-color: #198754 !important;
            border-color: #198754 !important;
        }
        
        .btn-danger-processing {
            background-color: #dc3545 !important;
            border-color: #dc3545 !important;
        }
        
        /* Enhanced spinner animation */
        .spinner-border {
            animation-duration: 0.75s;
        }
        
        /* Visual feedback for success/error states */
        .clock-in-success {
            animation: success-pulse 2s ease-in-out;
        }
        
        .clock-in-error {
            animation: error-shake 0.5s ease-in-out;
        }
        
        @keyframes success-pulse {
            0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
            100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
        }
        
        @keyframes error-shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
    </style>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Attendance Management', 'subtitle' => 'Track and manage staff attendance'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="header-title">
                            Attendance Dashboard
                            @php $pendingCount = $attendances->where('is_approved', false)->count(); @endphp
                            @if($pendingCount > 0)
                                <span class="badge bg-warning ms-2">{{ $pendingCount }} Pending</span>
                            @endif
                        </h4>
                        <div class="d-flex gap-2 align-items-center">
                            <div class="input-group input-group-sm" style="width: 220px;">
                                <input type="date" class="form-control" id="attendance-date" value="{{ today()->format('Y-m-d') }}">
                                <button class="btn btn-primary" type="button" id="filter-date-btn">Filter</button>
                            </div>
                            @if(!$hasClockedInToday)
                                <button class="btn btn-success" id="clock-in-btn">
                                    <i class="ti ti-clock me-1"></i> Clock In
                                </button>
                            @elseif(!$hasClockedOutToday)
                                <button class="btn btn-danger" id="clock-out-btn">
                                    <i class="ti ti-clock-pause me-1"></i> Clock Out
                                </button>
                            @else
                                <span class="badge bg-success fs-6 px-3 py-2">
                                    <i class="ti ti-check me-1"></i> Completed for today
                                </span>
                            @endif
                            @can('manage attendances')
                                <a href="{{ route('attendance.manual.create') }}" class="btn btn-outline-primary btn-sm">
                                    <i class="ti ti-plus me-1"></i>Add Record
                                </a>
                            @endcan
                        </div>
                    </div>

                    <!-- Live Clock Widget -->
                    <div class="card bg-dark text-white mb-4" id="live-clock-widget" style="border-radius:12px; overflow:hidden;">
                        <div class="card-body py-3 px-4">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="avatar-md bg-white bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center">
                                        <i class="ti ti-clock fs-2 text-white"></i>
                                    </div>
                                    <div>
                                        <div id="live-clock-time" class="fw-bold lh-1" style="font-size:2.2rem; letter-spacing:2px; font-variant-numeric:tabular-nums;">
                                            --:--:--
                                        </div>
                                        <div id="live-clock-date" class="text-white-50 small mt-1">Loading...</div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="text-white-50 small text-uppercase tracking-wide mb-1">Your Status Today</div>
                                    @if(!$hasClockedInToday)
                                        <span class="badge bg-secondary px-3 py-2 fs-6">
                                            <i class="ti ti-clock-off me-1"></i> Not Clocked In
                                        </span>
                                    @elseif(!$hasClockedOutToday)
                                        <span class="badge bg-success px-3 py-2 fs-6">
                                            <i class="ti ti-clock-check me-1"></i> Clocked In
                                        </span>
                                        <div class="text-white-50 small mt-1">
                                            Since {{ optional($attendances->where('user_id', auth()->id())->first())->check_in?->format('h:i A') ?? '' }}
                                            &mdash; <span id="live-elapsed">calculating...</span>
                                        </div>
                                    @else
                                        <span class="badge bg-primary px-3 py-2 fs-6">
                                            <i class="ti ti-check me-1"></i> Completed for today
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    @php
                        $myTodayRecord = $attendances->where('user_id', auth()->id())->first();
                        $checkInTs = $myTodayRecord?->check_in?->timestamp;
                    @endphp
                    <script>
                        (function () {
                            const timeEl   = document.getElementById('live-clock-time');
                            const dateEl   = document.getElementById('live-clock-date');
                            const elapsedEl = document.getElementById('live-elapsed');
                            const checkInTs = {{ $checkInTs ?? 'null' }};

                            const dayNames  = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
                            const monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];

                            function pad(n) { return String(n).padStart(2, '0'); }

                            function tick() {
                                const now = new Date();
                                const h = now.getHours();
                                const m = now.getMinutes();
                                const s = now.getSeconds();
                                const ampm = h >= 12 ? 'PM' : 'AM';
                                const h12 = h % 12 || 12;

                                timeEl.textContent = pad(h12) + ':' + pad(m) + ':' + pad(s) + ' ' + ampm;
                                dateEl.textContent = dayNames[now.getDay()] + ', ' + monthNames[now.getMonth()] + ' ' + now.getDate() + ' ' + now.getFullYear();

                                if (elapsedEl && checkInTs) {
                                    const secs = Math.floor(now.getTime() / 1000) - checkInTs;
                                    if (secs >= 0) {
                                        const hh = Math.floor(secs / 3600);
                                        const mm = Math.floor((secs % 3600) / 60);
                                        const ss = secs % 60;
                                        elapsedEl.textContent = pad(hh) + 'h ' + pad(mm) + 'm ' + pad(ss) + 's elapsed';
                                    }
                                }
                            }

                            tick();
                            setInterval(tick, 1000);
                        })();
                    </script>

                    <!-- Attendance Stats -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary-subtle">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="text-primary fw-bold mb-0">{{ $todayStats['present'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0">Present</p>
                                        </div>
                                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ti ti-check text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning-subtle">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="text-warning fw-bold mb-0">{{ $todayStats['late'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0">Late</p>
                                        </div>
                                        <div class="avatar-sm bg-warning rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ti ti-clock-hour-9 text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger-subtle">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="text-danger fw-bold mb-0">{{ $todayStats['absent'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0">Absent</p>
                                        </div>
                                        <div class="avatar-sm bg-danger rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ti ti-x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info-subtle">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="text-info fw-bold mb-0">{{ $todayStats['on_leave'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0">On Leave</p>
                                        </div>
                                        <div class="avatar-sm bg-info rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ti ti-user-off text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Filters -->
                    <div class="row mb-3">
                        @if($canViewAll ?? false)
                        <div class="col-md-4">
                            <label class="form-label">Filter by Staff</label>
                            <select class="form-select" id="staff-filter">
                                <option value="">All Staff</option>
                                @foreach($staffMembers ?? [] as $staff)
                                    <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-4">
                            <label class="form-label">Filter by Status</label>
                            <select class="form-select" id="status-filter">
                                <option value="">All Statuses</option>
                                <option value="present">Present</option>
                                <option value="late">Late</option>
                                <option value="absent">Absent</option>
                                <option value="leave">On Leave</option>
                                <option value="half_day">Half Day</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button class="btn btn-light" id="reset-filters-btn">Reset Filters</button>
                            </div>
                        </div>
                    </div>

                    {{-- Break Panel (only shown if clocked in and not yet clocked out) --}}
                    @if($hasClockedInToday && !$hasClockedOutToday)
                    <div class="alert alert-info d-flex align-items-center gap-3 mb-3" id="break-panel">
                        <i class="ti ti-coffee fs-4"></i>
                        <div class="flex-grow-1">
                            <strong>Break Management</strong>
                            <span id="break-timer-wrap" style="display:none;" class="ms-3">
                                <i class="ti ti-stopwatch me-1"></i>
                                <span id="break-timer" class="fw-bold font-monospace">00:00</span>
                            </span>
                        </div>
                        <div class="d-flex gap-2">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-warning" id="start-break-btn" data-bs-toggle="dropdown">
                                    <i class="ti ti-pause me-1"></i>Start Break
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="#" onclick="startBreak('lunch')"><i class="ti ti-salad me-2"></i>Lunch Break</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="startBreak('coffee')"><i class="ti ti-coffee me-2"></i>Coffee Break</a></li>
                                    <li><a class="dropdown-item" href="#" onclick="startBreak('personal')"><i class="ti ti-user me-2"></i>Personal Break</a></li>
                                </ul>
                            </div>
                            <button class="btn btn-sm btn-success" id="end-break-btn" style="display:none;" onclick="endBreak()">
                                <i class="ti ti-play me-1"></i>End Break
                            </button>
                        </div>
                    </div>
                    @endif

                    {{-- Bulk action bar --}}
                    @can('manage attendances')
                    <div class="d-flex gap-2 mb-3 align-items-center">
                        <button class="btn btn-sm btn-outline-success" onclick="bulkApprove()">
                            <i class="ti ti-checks me-1"></i>Bulk Approve Selected
                        </button>
                    </div>
                    @endcan

                    <!-- Attendance Table -->
                    <div class="table-responsive">
                        <table class="table table-centered table-striped dt-responsive nowrap w-100" id="attendance-table">
                            <thead class="table-light">
                                <tr>
                                    @can('manage attendances')
                                    <th style="width:36px;"><input type="checkbox" id="select-all-checkbox" class="form-check-input"></th>
                                    @endcan
                                    <th>Staff Member</th>
                                    <th>Check-in Time</th>
                                    <th>Check-out Time</th>
                                    <th>Hours Worked</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendances as $attendance)
                                <tr>
                                    @can('manage attendances')
                                    <td><input type="checkbox" class="form-check-input attendance-checkbox" data-id="{{ $attendance->id }}"></td>
                                    @endcan
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs me-2">
                                                <span class="avatar-title bg-primary rounded-circle">
                                                    {{ strtoupper(substr($attendance->user->name, 0, 2)) }}
                                                </span>
                                            </div>
                                            <div>
                                                <h5 class="mt-0 mb-0">{{ $attendance->user->name }}</h5>
                                                <span class="text-muted fs-12">{{ $attendance->user->email }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($attendance->check_in)
                                            <span class="badge bg-soft-success text-success">{{ $attendance->check_in->format('h:i A') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->check_out)
                                            <span class="badge bg-soft-info text-info">{{ $attendance->check_out->format('h:i A') }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($attendance->hours_worked)
                                            <span class="fw-bold">{{ number_format($attendance->hours_worked, 2) }} hrs</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($attendance->status)
                                            @case('present')
                                                <span class="badge bg-success-subtle text-success">Present</span>
                                                @break
                                            @case('late')
                                                <span class="badge bg-warning-subtle text-warning">Late</span>
                                                @break
                                            @case('absent')
                                                <span class="badge bg-danger-subtle text-danger">Absent</span>
                                                @break
                                            @case('leave')
                                                <span class="badge bg-info-subtle text-info">On Leave</span>
                                                @break
                                            @case('half_day')
                                                <span class="badge bg-secondary-subtle text-secondary">Half Day</span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-dark">{{ ucfirst($attendance->status) }}</span>
                                        @endswitch

                                            @if(!$attendance->is_approved)
                                            <br><span class="badge bg-secondary-subtle text-secondary mt-1">Pending Approval</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            @if($attendance->latitude && $attendance->longitude)
                                                <a href="https://maps.google.com/?q={{ $attendance->latitude }},{{ $attendance->longitude }}" target="_blank" class="btn btn-sm btn-light border" title="Check-in Location">
                                                    <i class="ti ti-map-pin text-success"></i> In
                                                </a>
                                            @endif
                                            @if($attendance->latitude_out && $attendance->longitude_out)
                                                <a href="https://maps.google.com/?q={{ $attendance->latitude_out }},{{ $attendance->longitude_out }}" target="_blank" class="btn btn-sm btn-light border" title="Check-out Location">
                                                    <i class="ti ti-map-pin text-info"></i> Out
                                                </a>
                                            @endif
                                            @if(!$attendance->latitude && !$attendance->latitude_out)
                                                <span class="text-muted fs-12">Unrecorded</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <button class="btn btn-sm btn-light border" onclick="viewAttendance({{ $attendance->id }})" title="View Details">
                                                <i class="ti ti-eye"></i>
                                            </button>

                                            @can('manage attendances')
                                            <a href="{{ route('attendance.manual.update', $attendance) }}" class="btn btn-sm btn-light border" title="Edit"
                                               onclick="event.preventDefault(); editAttendanceRecord({{ $attendance->id }})">
                                                <i class="ti ti-pencil"></i>
                                            </a>

                                            @if(!$attendance->is_approved)
                                            <button class="btn btn-sm btn-success" onclick="approveAttendance({{ $attendance->id }})" title="Approve">
                                                <i class="ti ti-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="rejectAttendance({{ $attendance->id }})" title="Reject">
                                                <i class="ti ti-x"></i>
                                            </button>
                                            @else
                                            <span class="badge bg-success-subtle text-success align-self-center"><i class="ti ti-check me-1"></i>Approved</span>
                                            @endif

                                            <button class="btn btn-sm btn-danger" onclick="deleteAttendanceRecord({{ $attendance->id }})" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="ti ti-calendar-off fs-2 mb-2 d-block text-muted opacity-50"></i>
                                        No attendance records found for the selected date
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Showing <span>{{ $attendances->firstItem() ?? 0 }}</span> to <span>{{ $attendances->lastItem() ?? 0 }}</span> of <span>{{ $attendances->total() }}</span> entries
                        </div>
                        <div class="pagination">
                            {{ $attendances->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

{{-- View Detail Modal --}}
<div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="attendanceModalLabel">
                    <i class="ti ti-clipboard-list me-2"></i>Attendance Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="attendanceModalBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Loading...</p>
                </div>
            </div>
            <div class="modal-footer">
                @can('manage attendances')
                <button type="button" class="btn btn-success btn-sm"
                    onclick="approveAttendance(document.getElementById('attendanceModal').dataset.attendanceId); bootstrap.Modal.getInstance(document.getElementById('attendanceModal')).hide()">
                    <i class="ti ti-check me-1"></i>Approve
                </button>
                <button type="button" class="btn btn-warning btn-sm"
                    onclick="rejectAttendance(document.getElementById('attendanceModal').dataset.attendanceId); bootstrap.Modal.getInstance(document.getElementById('attendanceModal')).hide()">
                    <i class="ti ti-x me-1"></i>Reject
                </button>
                @endcan
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@section('scripts')
    @vite(['resources/js/pages/attendance.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        // Initialize date picker and filter functionality
        document.addEventListener('DOMContentLoaded', function () {
            // Check if SweetAlert2 is loaded
            if (typeof Swal === 'undefined') {
                console.warn('SweetAlert2 (Swal) is not loaded. Falling back to native alerts.');
            } else {
                console.log('SweetAlert2 loaded successfully');
            }
            
            const dateInput = document.getElementById('attendance-date');
            const filterBtn = document.getElementById('filter-date-btn');
            const staffFilter = document.getElementById('staff-filter');
            const statusFilter = document.getElementById('status-filter');
            const resetBtn = document.getElementById('reset-filters-btn');
            
            // Filter by date when date changes
            dateInput.addEventListener('change', function () {
                applyFilters();
            });
            
            // Filter when button is clicked
            filterBtn.addEventListener('click', function () {
                applyFilters();
            });
            
            // Filter when staff or status changes
            staffFilter.addEventListener('change', function () {
                applyFilters();
            });
            
            statusFilter.addEventListener('change', function () {
                applyFilters();
            });
            
            // Reset filters
            resetBtn.addEventListener('click', function () {
                staffFilter.value = '';
                statusFilter.value = '';
                dateInput.value = '{{ today()->format('Y-m-d') }}';
                applyFilters();
            });
            
            // Apply filters function
            function applyFilters() {
                const date = dateInput.value;
                const staff = staffFilter.value;
                const status = statusFilter.value;

                // Construct URL with filters
                const baseUrl = '{{ route('attendance.index') }}';
                let url = baseUrl + '?date=' + date;
                if (staff) url += '&staff=' + staff;
                if (status) url += '&status=' + status;

                // Navigate to filtered URL
                window.location.href = url;
            }
            
            // Clock in functionality
            const clockInBtn = document.getElementById('clock-in-btn');
            if (clockInBtn) {
                clockInBtn.addEventListener('click', function () {
                    // Show loading state with Bootstrap spinner
                    const originalHTML = clockInBtn.innerHTML;
                    const originalClass = clockInBtn.className;
                    clockInBtn.disabled = true;
                    clockInBtn.className = originalClass + ' btn-processing';
                    clockInBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Clocking In...';

                    // Debug logging
                    console.log('Clock In button clicked');
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    console.log('CSRF Token present:', !!csrfToken);

                    // Reusable function to perform the fetch
                    const performClockIn = (latitude = null, longitude = null) => {
                        // Set timeout for slow network
                        const timeoutDuration = 30000; // 30 seconds
                        const timeoutPromise = new Promise((_, reject) => {
                            setTimeout(() => reject(new Error('Request timeout. Please check your network connection and try again.')), timeoutDuration);
                        });

                        // Make API request to clock in
                        const payload = {
                            notes: '',
                            device_info: navigator.userAgent
                        };
                        if (latitude && longitude) {
                            payload.latitude = latitude;
                            payload.longitude = longitude;
                        }

                        const fetchPromise = fetch('/api/attendance/check-in', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        // Race between fetch and timeout
                        Promise.race([fetchPromise, timeoutPromise])
                        .then(response => {
                            if (!response || !response.ok) {
                                throw new Error(`HTTP error! status: ${response?.status || 'unknown'}`);
                            }
                            
                            console.log('Clock-in response status:', response.status, response.statusText);
                            
                            // Check if response is JSON
                            const contentType = response.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                throw new Error(`Expected JSON response but got ${contentType}`);
                            }
                            
                            return response.json().then(data => {
                                // Add status to data for handling
                                data.status = response.status;
                                return data;
                            });
                        })
                        .then(data => {
                            console.log('Clock-in response data:', data);
                            
                            if (data.success) {
                                // Show success notification with enhanced visual feedback
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '✅ Clocked In Successfully!',
                                        text: data.message,
                                        showConfirmButton: false,
                                        timer: 2000,
                                        background: '#f0f9f0',
                                        iconColor: '#28a745',
                                        timerProgressBar: true,
                                        didClose: () => {
                                            location.reload();
                                        }
                                    });
                                } else {
                                    // Fallback to alert if SweetAlert2 not available
                                    alert('✅ Clocked In Successfully! ' + data.message);
                                    location.reload();
                                }
                            } else {
                                // Show error notification with detailed information
                                const errorMessage = data.message || 'Clock-in failed without specific error message';
                                console.error('Clock-in failed:', errorMessage);
                                
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'error',
                                        title: '❌ Clock-in Failed!',
                                        text: errorMessage,
                                        showConfirmButton: true,
                                        confirmButtonText: 'Try Again',
                                        background: '#fdf0f0',
                                        iconColor: '#dc3545'
                                    });
                                } else {
                                    alert('❌ Clock-in Failed! ' + errorMessage);
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Clock-in error:', error);
                            
                            let errorMessage = 'An error occurred while processing your clock-in. Please try again.';
                            if (error.message.includes('JSON')) {
                                errorMessage = 'Server returned an invalid response. Please check your network connection.';
                            } else if (error.message.includes('timeout')) {
                                errorMessage = 'Request timeout. Please check your network connection and try again.';
                            } else if (error.message.includes('HTTP error')) {
                                errorMessage = `Network error: ${error.message}. Please try again or contact support.`;
                            }
                            
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: '❌ Clock-in Failed!',
                                    text: errorMessage,
                                    showConfirmButton: true,
                                    confirmButtonText: 'Retry',
                                    background: '#fdf0f0',
                                    iconColor: '#dc3545',
                                    allowOutsideClick: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Retry the clock-in action
                                        clockInBtn.click();
                                    }
                                });
                            } else {
                                if (confirm('❌ Clock-in Failed! ' + errorMessage + '\n\nWould you like to try again?')) {
                                    clockInBtn.click();
                                }
                            }
                        })
                        .finally(() => {
                            // Restore button state
                            clockInBtn.disabled = false;
                            clockInBtn.className = originalClass;
                            clockInBtn.innerHTML = originalHTML;
                        });
                    };

                    // Try to get geolocation first
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                console.log('Geolocation acquired');
                                performClockIn(position.coords.latitude, position.coords.longitude);
                            },
                            (error) => {
                                console.warn('Geolocation error or denied. Proceeding without location.', error);
                                performClockIn();
                            },
                            { timeout: 10000, enableHighAccuracy: true } // 10 second timeout for GPS
                        );
                    } else {
                        console.warn('Geolocation not supported by this browser. Proceeding without location.');
                        performClockIn();
                    }

                    // Race between fetch and timeout
                    Promise.race([fetchPromise, timeoutPromise])
                    .then(response => {
                        if (!response || !response.ok) {
                            throw new Error(`HTTP error! status: ${response?.status || 'unknown'}`);
                        }
                        
                        console.log('Clock-in response status:', response.status, response.statusText);
                        
                        // Check if response is JSON
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            throw new Error(`Expected JSON response but got ${contentType}`);
                        }
                        
                        return response.json().then(data => {
                            // Add status to data for handling
                            data.status = response.status;
                            return data;
                        });
                    })
                    .then(data => {
                        console.log('Clock-in response data:', data);
                        
                        if (data.success) {
                            // Show success notification with enhanced visual feedback
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: '✅ Clocked In Successfully!',
                                    text: data.message,
                                    showConfirmButton: false,
                                    timer: 2000,
                                    background: '#f0f9f0',
                                    iconColor: '#28a745',
                                    timerProgressBar: true,
                                    didClose: () => {
                                        location.reload();
                                    }
                                });
                            } else {
                                // Fallback to alert if SweetAlert2 not available
                                alert('✅ Clocked In Successfully! ' + data.message);
                                location.reload();
                            }
                        } else {
                            // Show error notification with detailed information
                            const errorMessage = data.message || 'Clock-in failed without specific error message';
                            console.error('Clock-in failed:', errorMessage);
                            
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: '❌ Clock-in Failed!',
                                    text: errorMessage,
                                    showConfirmButton: true,
                                    confirmButtonText: 'Try Again',
                                    background: '#fdf0f0',
                                    iconColor: '#dc3545'
                                });
                            } else {
                                alert('❌ Clock-in Failed! ' + errorMessage);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Clock-in error:', error);
                        
                        let errorMessage = 'An error occurred while processing your clock-in. Please try again.';
                        if (error.message.includes('JSON')) {
                            errorMessage = 'Server returned an invalid response. Please check your network connection.';
                        } else if (error.message.includes('timeout')) {
                            errorMessage = 'Request timeout. Please check your network connection and try again.';
                        } else if (error.message.includes('HTTP error')) {
                            errorMessage = `Network error: ${error.message}. Please try again or contact support.`;
                        }
                        
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: '❌ Clock-in Failed!',
                                text: errorMessage,
                                showConfirmButton: true,
                                confirmButtonText: 'Retry',
                                background: '#fdf0f0',
                                iconColor: '#dc3545',
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Retry the clock-in action
                                    clockInBtn.click();
                                }
                            });
                        } else {
                            if (confirm('❌ Clock-in Failed! ' + errorMessage + '\n\nWould you like to try again?')) {
                                clockInBtn.click();
                            }
                        }
                    })
                    .finally(() => {
                        // Restore button state
                        clockInBtn.disabled = false;
                        clockInBtn.className = originalClass;
                        clockInBtn.innerHTML = originalHTML;
                    });
                });
            }

            // Clock out functionality
            const clockOutBtn = document.getElementById('clock-out-btn');
            if (clockOutBtn) {
                clockOutBtn.addEventListener('click', function () {
                    // Show loading state with Bootstrap spinner
                    const originalHTML = clockOutBtn.innerHTML;
                    const originalClass = clockOutBtn.className;
                    clockOutBtn.disabled = true;
                    clockOutBtn.className = originalClass + ' btn-processing';
                    clockOutBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Clocking Out...';

                    // Debug logging
                    console.log('Clock Out button clicked');
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    console.log('CSRF Token present:', !!csrfToken);

                    // Reusable function to perform the fetch
                    const performClockOut = (latitude = null, longitude = null) => {
                        // Set timeout for slow network
                        const timeoutDuration = 30000; // 30 seconds
                        const timeoutPromise = new Promise((_, reject) => {
                            setTimeout(() => reject(new Error('Request timeout. Please check your network connection and try again.')), timeoutDuration);
                        });

                        // Prepare payload
                        const payload = {
                            notes: '',
                            device_info: navigator.userAgent
                        };
                        if (latitude && longitude) {
                            payload.latitude = latitude;
                            payload.longitude = longitude;
                        }

                        // Make API request to clock out
                        const fetchPromise = fetch('/api/attendance/check-out', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });

                        // Race between fetch and timeout
                        Promise.race([fetchPromise, timeoutPromise])
                        .then(response => {
                            if (!response || !response.ok) {
                                throw new Error(`HTTP error! status: ${response?.status || 'unknown'}`);
                            }
                            
                            console.log('Clock-out response status:', response.status, response.statusText);
                            
                            // Check if response is JSON
                            const contentType = response.headers.get('content-type');
                            if (!contentType || !contentType.includes('application/json')) {
                                throw new Error(`Expected JSON response but got ${contentType}`);
                            }
                            
                            return response.json().then(data => {
                                // Add status to data for handling
                                data.status = response.status;
                                return data;
                            });
                        })
                        .then(data => {
                            console.log('Clock-out response data:', data);
                            
                            if (data.success) {
                                // Show success notification with enhanced visual feedback
                                const successMessage = `${data.message}\nTotal hours worked: ${data.hours_worked || 0} hours`;
                                
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: '✅ Clocked Out Successfully!',
                                        text: successMessage,
                                        showConfirmButton: false,
                                        timer: 2500,
                                        background: '#f0f9f0',
                                        iconColor: '#28a745',
                                        timerProgressBar: true,
                                        didClose: () => {
                                            location.reload();
                                        }
                                    });
                                } else {
                                    // Fallback to alert if SweetAlert2 not available
                                    alert('✅ Clocked Out Successfully! ' + successMessage);
                                    location.reload();
                                }
                            } else {
                                // Show error notification with detailed information
                                const errorMessage = data.message || 'Clock-out failed without specific error message';
                                console.error('Clock-out failed:', errorMessage);
                                
                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'error',
                                        title: '❌ Clock-out Failed!',
                                        text: errorMessage,
                                        showConfirmButton: true,
                                        confirmButtonText: 'Try Again',
                                        background: '#fdf0f0',
                                        iconColor: '#dc3545'
                                    });
                                } else {
                                    alert('❌ Clock-out Failed! ' + errorMessage);
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Clock-out error:', error);
                            
                            let errorMessage = 'An error occurred while processing your clock-out. Please try again.';
                            if (error.message.includes('JSON')) {
                                errorMessage = 'Server returned an invalid response. Please check your network connection.';
                            } else if (error.message.includes('timeout')) {
                                errorMessage = 'Request timeout. Please check your network connection and try again.';
                            } else if (error.message.includes('HTTP error')) {
                                errorMessage = `Network error: ${error.message}. Please try again or contact support.`;
                            }
                            
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: '❌ Clock-out Failed!',
                                    text: errorMessage,
                                    showConfirmButton: true,
                                    confirmButtonText: 'Retry',
                                    background: '#fdf0f0',
                                    iconColor: '#dc3545',
                                    allowOutsideClick: false
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Retry the clock-out action
                                        clockOutBtn.click();
                                    }
                                });
                            } else {
                                if (confirm('❌ Clock-out Failed! ' + errorMessage + '\n\nWould you like to try again?')) {
                                    clockOutBtn.click();
                                }
                            }
                        })
                        .finally(() => {
                            // Restore button state
                            clockOutBtn.disabled = false;
                            clockOutBtn.className = originalClass;
                            clockOutBtn.innerHTML = originalHTML;
                        });
                    };

                    // Try to get geolocation first
                    if (navigator.geolocation) {
                        navigator.geolocation.getCurrentPosition(
                            (position) => {
                                console.log('Geolocation acquired');
                                performClockOut(position.coords.latitude, position.coords.longitude);
                            },
                            (error) => {
                                console.warn('Geolocation error or denied. Proceeding without location.', error);
                                performClockOut();
                            },
                            { timeout: 10000, enableHighAccuracy: true } // 10 second timeout for GPS
                        );
                    } else {
                        console.warn('Geolocation not supported by this browser. Proceeding without location.');
                        performClockOut();
                    }

                    // Race between fetch and timeout
                    Promise.race([fetchPromise, timeoutPromise])
                    .then(response => {
                        if (!response || !response.ok) {
                            throw new Error(`HTTP error! status: ${response?.status || 'unknown'}`);
                        }
                        
                        console.log('Clock-out response status:', response.status, response.statusText);
                        
                        // Check if response is JSON
                        const contentType = response.headers.get('content-type');
                        if (!contentType || !contentType.includes('application/json')) {
                            throw new Error(`Expected JSON response but got ${contentType}`);
                        }
                        
                        return response.json().then(data => {
                            // Add status to data for handling
                            data.status = response.status;
                            return data;
                        });
                    })
                    .then(data => {
                        console.log('Clock-out response data:', data);
                        
                        if (data.success) {
                            // Show success notification with enhanced visual feedback
                            const successMessage = `${data.message}\nTotal hours worked: ${data.hours_worked || 0} hours`;
                            
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: '✅ Clocked Out Successfully!',
                                    text: successMessage,
                                    showConfirmButton: false,
                                    timer: 2500,
                                    background: '#f0f9f0',
                                    iconColor: '#28a745',
                                    timerProgressBar: true,
                                    didClose: () => {
                                        location.reload();
                                    }
                                });
                            } else {
                                // Fallback to alert if SweetAlert2 not available
                                alert('✅ Clocked Out Successfully! ' + successMessage);
                                location.reload();
                            }
                        } else {
                            // Show error notification with detailed information
                            const errorMessage = data.message || 'Clock-out failed without specific error message';
                            console.error('Clock-out failed:', errorMessage);
                            
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: '❌ Clock-out Failed!',
                                    text: errorMessage,
                                    showConfirmButton: true,
                                    confirmButtonText: 'Try Again',
                                    background: '#fdf0f0',
                                    iconColor: '#dc3545'
                                });
                            } else {
                                alert('❌ Clock-out Failed! ' + errorMessage);
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Clock-out error:', error);
                        
                        let errorMessage = 'An error occurred while processing your clock-out. Please try again.';
                        if (error.message.includes('JSON')) {
                            errorMessage = 'Server returned an invalid response. Please check your network connection.';
                        } else if (error.message.includes('timeout')) {
                            errorMessage = 'Request timeout. Please check your network connection and try again.';
                        } else if (error.message.includes('HTTP error')) {
                            errorMessage = `Network error: ${error.message}. Please try again or contact support.`;
                        }
                        
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: '❌ Clock-out Failed!',
                                text: errorMessage,
                                showConfirmButton: true,
                                confirmButtonText: 'Retry',
                                background: '#fdf0f0',
                                iconColor: '#dc3545',
                                allowOutsideClick: false
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Retry the clock-out action
                                    clockOutBtn.click();
                                }
                            });
                        } else {
                            if (confirm('❌ Clock-out Failed! ' + errorMessage + '\n\nWould you like to try again?')) {
                                clockOutBtn.click();
                            }
                        }
                    })
                    .finally(() => {
                        // Restore button state
                        clockOutBtn.disabled = false;
                        clockOutBtn.className = originalClass;
                        clockOutBtn.innerHTML = originalHTML;
                    });
                });
            }
        });
        
        // Attendance action functions
        function viewAttendance(id) {
            // TODO: Implement proper view route for attendance records
            alert('View attendance feature not yet implemented');
        }
        
        function editAttendance(id) {
            window.location.href = `/attendance/manual/${id}/edit`;
        }
        
        function deleteAttendance(id) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const confirmMessage = 'Are you sure you want to delete this attendance record?';
            
            // Use SweetAlert2 if available, otherwise use native confirm
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Delete Attendance Record?',
                    text: 'This action cannot be undone.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Delete',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performDeletion(id, csrfToken);
                    }
                });
            } else {
                if (confirm(confirmMessage)) {
                    performDeletion(id, csrfToken);
                }
            }
        }
        
        function performDeletion(id, csrfToken) {
            console.log('Deleting attendance ID:', id);
            
            fetch(`/api/attendance/${id}`, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Delete response status:', response.status);
                return response.json().then(data => {
                    data.status = response.status;
                    return data;
                });
            })
            .then(data => {
                console.log('Delete response data:', data);
                
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Attendance record deleted successfully.',
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        alert('Attendance record deleted successfully.');
                        location.reload();
                    }
                } else {
                    const errorMessage = data.message || 'Failed to delete attendance record';
                    console.error('Delete failed:', errorMessage);
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Delete Failed!',
                            text: errorMessage,
                            showConfirmButton: true
                        });
                    } else {
                        alert('Delete Failed! ' + errorMessage);
                    }
                }
            })
            .catch(error => {
                console.error('Delete error:', error);
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Delete Failed!',
                        text: 'Error deleting attendance record',
                        showConfirmButton: true
                    });
                } else {
                    alert('Error deleting attendance record');
                }
            });
        }

        function approveAttendance(id) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            // Use SweetAlert2 if available, otherwise use native confirm
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Approve Attendance?',
                    text: 'This will mark the attendance record as approved.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, Approve',
                    cancelButtonText: 'Cancel',
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33'
                }).then((result) => {
                    if (result.isConfirmed) {
                        performApproval(id, csrfToken);
                    }
                });
            } else {
                if (confirm('Approve Attendance?\nThis will mark the attendance record as approved.')) {
                    performApproval(id, csrfToken);
                }
            }
        }
        
        function performApproval(id, csrfToken) {
            console.log('Approving attendance ID:', id);
            
            fetch(`/api/attendance/${id}/approve`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                console.log('Approval response status:', response.status);
                return response.json().then(data => {
                    data.status = response.status;
                    return data;
                });
            })
            .then(data => {
                console.log('Approval response data:', data);
                
                if (data.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Approved!',
                            text: data.message,
                            showConfirmButton: false,
                            timer: 2000
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        alert('Approved! ' + data.message);
                        location.reload();
                    }
                } else {
                    const errorMessage = data.message || 'Approval failed without specific error message';
                    console.error('Approval failed:', errorMessage);
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: errorMessage,
                            showConfirmButton: true
                        });
                    } else {
                        alert('Error! ' + errorMessage);
                    }
                }
            })
            .catch(error => {
                console.error('Approval error:', error);
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to approve attendance',
                        showConfirmButton: true
                    });
                } else {
                    alert('Error! Failed to approve attendance');
                }
            });
        }
    </script>
@endsection