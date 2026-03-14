@extends('layouts.vertical', ['title' => 'Staff Attendance'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Staff Attendance', 'subtitle' => 'Manage individual staff attendance records'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="header-title">Staff Attendance</h4>
                        <div class="d-flex gap-2">
                            @can('manage attendances')
                            <a href="{{ route('attendance.manual.create') }}" class="btn btn-primary">
                                <i class="ti ti-plus me-1"></i> Add Attendance
                            </a>
                            @endcan
                            <select class="form-select" id="staff-select">
                                <option value="">Select Staff Member</option>
                                @foreach($staffMembers as $staff)
                                    <option value="{{ $staff->id }}" {{ request('staff_id') == $staff->id ? 'selected' : '' }}>
                                        {{ $staff->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Staff Attendance Filters -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start-date" value="{{ request('start_date', today()->startOfMonth()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end-date" value="{{ request('end_date', today()->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="status-filter">
                                <option value="">All Statuses</option>
                                <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                                <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                                <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                                <option value="leave" {{ request('status') == 'leave' ? 'selected' : '' }}>On Leave</option>
                                <option value="half_day" {{ request('status') == 'half_day' ? 'selected' : '' }}>Half Day</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button class="btn btn-primary" id="apply-filters-btn">Apply Filters</button>
                            </div>
                        </div>
                    </div>

                    <!-- Staff Attendance Stats -->
                    @if($selectedStaff)
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary-subtle">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="text-primary fw-bold mb-0">{{ $staffStats['total_days'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0">Total Days</p>
                                        </div>
                                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ti ti-calendar-stats text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success-subtle">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="text-success fw-bold mb-0">{{ $staffStats['present_days'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0">Present Days</p>
                                        </div>
                                        <div class="avatar-sm bg-success rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ti ti-user-check text-white"></i>
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
                                            <h5 class="text-warning fw-bold mb-0">{{ $staffStats['late_days'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0">Late Arrivals</p>
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
                                            <h5 class="text-danger fw-bold mb-0">{{ $staffStats['absent_days'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0">Absent Days</p>
                                        </div>
                                        <div class="avatar-sm bg-danger rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ti ti-user-x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Staff Information -->
                    <div class="card bg-light mb-4">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="avatar-lg me-3">
                                    <span class="avatar-title bg-primary rounded-circle fs-20">
                                        {{ strtoupper(substr($selectedStaff->name, 0, 2)) }}
                                    </span>
                                </div>
                                <div>
                                    <h5 class="mb-1">{{ $selectedStaff->name }}</h5>
                                    <p class="text-muted mb-0">{{ $selectedStaff->email }}</p>
                                    <p class="text-muted mb-0">{{ $selectedStaff->phone }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Staff Attendance Records Table -->
                    <div class="table-responsive">
                        <table class="table table-centered table-striped dt-responsive nowrap w-100" id="staff-attendance-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Check-in Time</th>
                                    <th>Check-out Time</th>
                                    <th>Hours Worked</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                    @can('manage attendances')
                                    <th>Actions</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($attendanceRecords as $record)
                                <tr>
                                    <td class="fw-medium">{{ $record->date->format('M d, Y') }}</td>
                                    <td>
                                        @if($record->check_in)
                                            <span class="badge bg-soft-success text-success">{{ $record->check_in->format('h:i A') }}</span>
                                            @if($record->late_arrival_minutes > 0)
                                                <br><small class="text-danger">Late: {{ $record->late_arrival_minutes }}m</small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record->check_out)
                                            <span class="badge bg-soft-info text-info">{{ $record->check_out->format('h:i A') }}</span>
                                            @if($record->early_departure_minutes > 0)
                                                <br><small class="text-warning">Early: {{ $record->early_departure_minutes }}m</small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record->hours_worked)
                                            <span class="fw-bold">{{ number_format($record->hours_worked, 2) }} hrs</span>
                                            @if($record->overtime_hours > 0)
                                                <br><small class="text-success">OT: {{ number_format($record->overtime_hours, 2) }} hrs</small>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($record->status)
                                            @case('present') <span class="badge bg-success-subtle text-success">Present</span> @break
                                            @case('late') <span class="badge bg-warning-subtle text-warning">Late</span> @break
                                            @case('absent') <span class="badge bg-danger-subtle text-danger">Absent</span> @break
                                            @case('leave') <span class="badge bg-info-subtle text-info">On Leave</span> @break
                                            @case('half_day') <span class="badge bg-secondary-subtle text-secondary">Half Day</span> @break
                                            @default <span class="badge bg-light text-dark">{{ ucfirst($record->status) }}</span>
                                        @endswitch
                                        @if($record->is_approved)
                                            <span class="badge bg-success ms-1" title="Approved"><i class="ti ti-check"></i></span>
                                        @else
                                            <span class="badge bg-secondary ms-1" title="Pending Approval"><i class="ti ti-clock"></i></span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            @if($record->latitude && $record->longitude)
                                                <a href="https://maps.google.com/?q={{ $record->latitude }},{{ $record->longitude }}" target="_blank" class="btn btn-xs btn-light border" title="Check-in Location">
                                                    <i class="ti ti-map-pin text-success"></i> In
                                                </a>
                                            @endif
                                            @if($record->latitude_out && $record->longitude_out)
                                                <a href="https://maps.google.com/?q={{ $record->latitude_out }},{{ $record->longitude_out }}" target="_blank" class="btn btn-xs btn-light border" title="Check-out Location">
                                                    <i class="ti ti-map-pin text-info"></i> Out
                                                </a>
                                            @endif
                                            @if(!$record->latitude && !$record->latitude_out)
                                                <span class="text-muted fs-12">Unrecorded</span>
                                            @endif
                                        </div>
                                    </td>
                                    @can('manage attendances')
                                    <td>
                                        <div class="d-flex gap-1 flex-wrap">
                                            <button class="btn btn-sm btn-light border" onclick="viewAttendance({{ $record->id }})" title="View">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                            <a href="{{ route('attendance.manual.update', $record) }}"
                                               class="btn btn-sm btn-light border"
                                               onclick="event.preventDefault(); editAttendanceRecord({{ $record->id }})"
                                               title="Edit">
                                                <i class="ti ti-pencil"></i>
                                            </a>
                                            @if(!$record->is_approved)
                                            <button class="btn btn-sm btn-success" onclick="approveAttendance({{ $record->id }})" title="Approve">
                                                <i class="ti ti-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="rejectAttendance({{ $record->id }})" title="Reject">
                                                <i class="ti ti-x"></i>
                                            </button>
                                            @endif
                                            <button class="btn btn-sm btn-danger" onclick="deleteAttendanceRecord({{ $record->id }})" title="Delete">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                    @endcan
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <i class="ti ti-calendar-off fs-2 mb-2 d-block opacity-50"></i>
                                        @if($selectedStaff)
                                            No attendance records found for {{ $selectedStaff->name }} in the selected period.
                                        @else
                                            Select a staff member to view their attendance records.
                                        @endif
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($attendanceRecords instanceof \Illuminate\Pagination\LengthAwarePaginator && $attendanceRecords->count() > 0)
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing {{ $attendanceRecords->firstItem() }} to {{ $attendanceRecords->lastItem() }} of {{ $attendanceRecords->total() }} entries
                        </div>
                        <div>{{ $attendanceRecords->appends(request()->query())->links() }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/attendance.js', 'resources/js/pages/staff-attendance.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
@endsection