@extends('layouts.vertical', ['title' => 'Attendance Reports'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Attendance Reports', 'subtitle' => 'Generate and analyze attendance reports'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h4 class="header-title">Attendance Reports</h4>
                        <div class="d-inline-flex gap-2">
                            <button class="btn btn-outline-secondary btn-sm" id="print-report-btn" title="Print / Save as PDF">
                                <i class="ti ti-printer me-1"></i>Print / PDF
                            </button>
                            <button class="btn btn-primary btn-sm" id="export-csv-btn">
                                <i class="ti ti-file-spreadsheet me-1"></i>Export CSV
                            </button>
                        </div>
                    </div>

                    <!-- Report Filters -->
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
                            <label class="form-label">Staff Member</label>
                            <select class="form-select" id="staff-member">
                                <option value="">All Staff</option>
                                @foreach($staffMembers as $staff)
                                    <option value="{{ $staff->id }}" {{ request('staff_id') == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="d-grid">
                                <button class="btn btn-primary" type="button" id="filter-report-btn">Generate Report</button>
                            </div>
                        </div>
                    </div>

                    <!-- Report Summary Cards -->
                    @if($summary)
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-primary-subtle">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="text-primary fw-bold mb-0">{{ $summary['total_days'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0">Total Days</p>
                                        </div>
                                        <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ti ti-calendar-month text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success-subtle">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="text-success fw-bold mb-0">{{ $summary['present_days'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0">Days Present</p>
                                        </div>
                                        <div class="avatar-sm bg-success rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ti ti-user-check text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning-subtle">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="text-warning fw-bold mb-0">{{ $summary['late_days'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0">Late Arrivals</p>
                                        </div>
                                        <div class="avatar-sm bg-warning rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ti ti-clock-hour-9 text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-danger-subtle">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="text-danger fw-bold mb-0">{{ $summary['absent_days'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0">Days Absent</p>
                                        </div>
                                        <div class="avatar-sm bg-danger rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ti ti-user-x text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info-subtle">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="text-info fw-bold mb-0">{{ $summary['total_hours'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0">Total Hours</p>
                                        </div>
                                        <div class="avatar-sm bg-info rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ti ti-clock text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-purple-subtle">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h5 class="text-purple fw-bold mb-0">{{ $summary['business_hours_compliance'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0">Business Hours</p>
                                        </div>
                                        <div class="avatar-sm bg-purple rounded-circle d-flex align-items-center justify-content-center">
                                            <i class="ti ti-building text-white"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Report Table -->
                    <div class="table-responsive">
                        <table class="table table-centered table-striped dt-responsive nowrap w-100" id="report-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Staff Member</th>
                                    <th>Date</th>
                                    <th>Check-in Time</th>
                                    <th>Check-out Time</th>
                                    <th>Hours Worked</th>
                                    <th>Expected Hours</th>
                                    <th>Business Hours</th>
                                    <th>Status</th>
                                    <th>Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($reportData as $record)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs me-2">
                                                <span class="avatar-title bg-primary rounded-circle">
                                                    {{ strtoupper(substr($record->user->name, 0, 2)) }}
                                                </span>
                                            </div>
                                            <div>
                                                <h5 class="mt-0 mb-0">{{ $record->user->name }}</h5>
                                                <span class="text-muted fs-12">{{ $record->user->email }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $record->date->format('M d, Y') }}</td>
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
                                        @if($record->expected_hours)
                                            <span class="fw-bold">{{ number_format($record->expected_hours, 2) }} hrs</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record->calculated_using_business_hours)
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="ti ti-check me-1"></i>Business Hours
                                            </span>
                                            @if($record->business_hours_type)
                                                <br><small class="text-muted">{{ ucfirst($record->business_hours_type) }}</small>
                                            @endif
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                <i class="ti ti-clock me-1"></i>Standard
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @switch($record->status)
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
                                                <span class="badge bg-light text-dark">{{ ucfirst($record->status) }}</span>
                                        @endswitch
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            @if($record->latitude && $record->longitude)
                                                <a href="https://maps.google.com/?q={{ $record->latitude }},{{ $record->longitude }}" target="_blank" class="btn btn-sm btn-light border" title="Check-in Location">
                                                    <i class="ti ti-map-pin text-success"></i> In
                                                </a>
                                            @endif
                                            @if($record->latitude_out && $record->longitude_out)
                                                <a href="https://maps.google.com/?q={{ $record->latitude_out }},{{ $record->longitude_out }}" target="_blank" class="btn btn-sm btn-light border" title="Check-out Location">
                                                    <i class="ti ti-map-pin text-info"></i> Out
                                                </a>
                                            @endif
                                            @if(!$record->latitude && !$record->latitude_out)
                                                <span class="text-muted fs-12">Unrecorded</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="ti ti-calendar-off fs-2 d-block mb-2 opacity-50"></i>
                                        No attendance records found for the selected criteria.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($reportData && $reportData->count() > 0)
                            <tfoot class="table-light fw-bold">
                                <tr>
                                    <td colspan="5" class="text-end">Totals:</td>
                                    <td>{{ number_format($reportData->sum('hours_worked'), 2) }} hrs</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>

                    <!-- Pagination -->
                    @if($reportData && $reportData->count() > 0)
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted">
                            Showing <span>{{ $reportData->firstItem() ?? 0 }}</span> to <span>{{ $reportData->lastItem() ?? 0 }}</span> of <span>{{ $reportData->total() }}</span> entries
                        </div>
                        <div class="pagination">
                            {{ $reportData->links() }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/attendance-reports.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        // Initialize report filtering functionality
        document.addEventListener('DOMContentLoaded', function () {
            const startDateInput = document.getElementById('start-date');
            const endDateInput = document.getElementById('end-date');
            const staffMemberSelect = document.getElementById('staff-member');
            const filterReportBtn = document.getElementById('filter-report-btn');
            const exportBtn = document.getElementById('export-btn');
            
            // Filter report button click
            filterReportBtn.addEventListener('click', function () {
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;
                const staffId = staffMemberSelect.value;
                
                if (!startDate || !endDate) {
                    alert('Please select both start and end dates.');
                    return;
                }
                
                // Validate date range
                if (new Date(startDate) > new Date(endDate)) {
                    alert('Start date must be before or equal to end date.');
                    return;
                }
                
                // Construct URL with parameters
                let url = `{{ route('attendance.report') }}?start_date=${startDate}&end_date=${endDate}`;
                if (staffId) url += `&staff_id=${staffId}`;
                
                window.location.href = url;
            });
            
            // Export report button click
            exportBtn.addEventListener('click', function () {
                const startDate = startDateInput.value;
                const endDate = endDateInput.value;
                const staffId = staffMemberSelect.value;
                
                if (!startDate || !endDate) {
                    alert('Please select both start and end dates for export.');
                    return;
                }
                
                // Construct export URL
                let url = `{{ route('attendance.export') }}?start_date=${startDate}&end_date=${endDate}&format=csv`;
                if (staffId) url += `&staff_id=${staffId}`;
                
                window.location.href = url;
            });
        });
    </script>
@endsection