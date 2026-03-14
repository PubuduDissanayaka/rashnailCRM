@extends('layouts.vertical', ['title' => 'Work Hour Reports'])

@section('css')
    @vite(['node_modules/daterangepicker/daterangepicker.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
    <style>
        .summary-card {
            border-radius: 10px;
            border: none;
            transition: transform 0.2s;
        }
        
        .summary-card:hover {
            transform: translateY(-2px);
        }
        
        .filter-card {
            background: #f8f9fa;
            border-left: 4px solid #3b76e1;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-top: none;
        }
        
        .export-btn-group .btn {
            border-radius: 5px;
        }
        
        .date-range-picker {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 8px 12px;
            cursor: pointer;
        }
        
        .badge-attendance-rate {
            background-color: #e7f7ef;
            color: #28a745;
        }
        
        .badge-compliance-rate {
            background-color: #e7f1ff;
            color: #3b76e1;
        }
        
        .badge-overtime {
            background-color: #fff3e6;
            color: #fd7e14;
        }
        
        .staff-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #3b76e1;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
    </style>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Work Hour Reports', 'subtitle' => 'Comprehensive staff work hour analytics'])

    <div class="row">
        <div class="col-12">
            <!-- Filter Card -->
            <div class="card filter-card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Report Filters</h5>
                    <form id="report-filters-form" method="GET" action="{{ route('reports.work-hours.index') }}">
                        <div class="row g-3">
                            <!-- Date Range -->
                            <div class="col-md-3">
                                <label class="form-label">Date Range</label>
                                <div class="date-range-picker" id="date-range-picker">
                                    <i class="ti ti-calendar me-2"></i>
                                    <span id="date-range-text">
                                        {{ $filters->startDate->format('M d, Y') }} - {{ $filters->endDate->format('M d, Y') }}
                                    </span>
                                    <input type="hidden" name="start_date" id="start-date" value="{{ $filters->startDate->format('Y-m-d') }}">
                                    <input type="hidden" name="end_date" id="end-date" value="{{ $filters->endDate->format('Y-m-d') }}">
                                </div>
                            </div>
                            
                            <!-- Staff Filter -->
                            <div class="col-md-3">
                                <label class="form-label">Staff Member</label>
                                <select class="form-select" name="staff_id" id="staff-filter">
                                    <option value="">All Staff</option>
                                    @foreach($filterOptions['staff_users'] ?? [] as $staff)
                                        <option value="{{ $staff->id }}" {{ $filters->staffId == $staff->id ? 'selected' : '' }}>
                                            {{ $staff->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Role Filter -->
                            <div class="col-md-3">
                                <label class="form-label">Role</label>
                                <select class="form-select" name="role_id" id="role-filter">
                                    <option value="">All Roles</option>
                                    @foreach($filterOptions['roles'] ?? [] as $role)
                                        <option value="{{ $role->id }}" {{ $filters->roleId == $role->id ? 'selected' : '' }}>
                                            {{ ucfirst($role->name) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Status Filter -->
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status" id="status-filter">
                                    <option value="">All Statuses</option>
                                    @foreach($filterOptions['status_options'] ?? [] as $value => $label)
                                        <option value="{{ $value }}" {{ $filters->status == $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <!-- Quick Date Range Buttons -->
                            <div class="col-12">
                                <div class="d-flex gap-2 flex-wrap">
                                    <small class="text-muted me-2">Quick ranges:</small>
                                    @foreach($filterOptions['date_ranges'] ?? [] as $key => $range)
                                        <button type="button" class="btn btn-sm btn-outline-secondary quick-range-btn" data-range="{{ $key }}">
                                            {{ $range['label'] }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="col-12 mt-3">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="ti ti-filter me-1"></i> Apply Filters
                                        </button>
                                        <button type="button" class="btn btn-light" id="reset-filters-btn">
                                            <i class="ti ti-refresh me-1"></i> Reset
                                        </button>
                                    </div>
                                    <div class="export-btn-group">
                                        <button type="button" class="btn btn-success" id="export-csv-btn">
                                            <i class="ti ti-file-type-csv me-1"></i> Export CSV
                                        </button>
                                        <button type="button" class="btn btn-danger" id="export-pdf-btn">
                                            <i class="ti ti-file-type-pdf me-1"></i> Export PDF
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card summary-card bg-primary-subtle">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="text-primary fw-bold mb-0">{{ $summary['total_staff'] ?? 0 }}</h5>
                                    <p class="text-muted mb-0">Total Staff</p>
                                </div>
                                <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="ti ti-users text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card summary-card bg-success-subtle">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="text-success fw-bold mb-0">{{ $summary['total_hours'] ?? 0 }}</h5>
                                    <p class="text-muted mb-0">Total Hours</p>
                                </div>
                                <div class="avatar-sm bg-success rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="ti ti-clock text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card summary-card bg-info-subtle">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="text-info fw-bold mb-0">{{ $summary['overall_attendance_rate'] ?? 0 }}%</h5>
                                    <p class="text-muted mb-0">Attendance Rate</p>
                                </div>
                                <div class="avatar-sm bg-info rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="ti ti-chart-bar text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3">
                    <div class="card summary-card bg-warning-subtle">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="text-warning fw-bold mb-0">{{ $summary['total_overtime'] ?? 0 }}</h5>
                                    <p class="text-muted mb-0">Overtime Hours</p>
                                </div>
                                <div class="avatar-sm bg-warning rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="ti ti-alert-circle text-white"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Staff Summary Table -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Staff Summary</h5>
                        <div class="text-muted">
                            <i class="ti ti-info-circle me-1"></i>
                            {{ $filterDescription }}
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-centered table-striped data-table">
                            <thead>
                                <tr>
                                    <th>Staff Member</th>
                                    <th>Total Days</th>
                                    <th>Total Hours</th>
                                    <th>Expected Hours</th>
                                    <th>Overtime</th>
                                    <th>Attendance Rate</th>
                                    <th>Compliance Rate</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($staffSummary as $staff)
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="staff-avatar me-2">
                                                {{ strtoupper(substr($staff->staff_name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <h6 class="mb-0">{{ $staff->staff_name }}</h6>
                                                <small class="text-muted">{{ $staff->email }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $staff->total_days }}</td>
                                    <td>
                                        <span class="fw-bold">{{ number_format($staff->total_hours, 2) }}</span>
                                        <small class="text-muted d-block">Net: {{ number_format($staff->net_hours, 2) }}</small>
                                    </td>
                                    <td>{{ number_format($staff->total_expected_hours, 2) }}</td>
                                    <td>
                                        <span class="badge badge-overtime p-2">
                                            {{ number_format($staff->total_overtime, 2) }} hrs
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-attendance-rate p-2">
                                            {{ number_format($staff->attendance_rate, 1) }}%
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-compliance-rate p-2">
                                            {{ number_format($staff->compliance_rate, 1) }}%
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-light view-details-btn" data-staff-id="{{ $staff->staff_id }}">
                                            <i class="ti ti-eye"></i> Details
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No staff data found for the selected filters
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Detailed Report -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">Detailed Attendance Records</h5>
                    
                    <div class="table-responsive">
                        <table class="table table-centered table-striped data-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Staff</th>
                                    <th>Check-in</th>
                                    <th>Check-out</th>
                                    <th>Hours</th>
                                    <th>Expected</th>
                                    <th>Overtime</th>
                                    <th>Status</th>
                                    <th>Type</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($detailedReport as $record)
                                <tr>
                                    <td>
                                        {{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}
                                        <br>
                                        <small class="text-muted">{{ \Carbon\Carbon::parse($record->date)->format('l') }}</small>
                                    </td>
                                    <td>{{ $record->staff_name }}</td>
                                    <td>
                                        @if($record->check_in)
                                            <span class="badge bg-soft-success text-success">
                                                {{ \Carbon\Carbon::parse($record->check_in)->format('h:i A') }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($record->check_out)
                                            <span class="badge bg-soft-info text-info">
                                                {{ \Carbon\Carbon::parse($record->check_out)->format('h:i A') }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="fw-bold">{{ number_format($record->hours_worked, 2) }}</span>
                                        <br>
                                        <small class="text-muted">
                                            Breaks: {{ $record->total_break_minutes }}m
                                        </small>
                                    </td>
                                    <td>{{ number_format($record->expected_hours, 2) }}</td>
                                    <td>
                                        @if($record->overtime_hours > 0)
                                            <span class="badge badge-overtime">
                                                +{{ number_format($record->overtime_hours, 2) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
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
                                                <span class="badge bg-info-subtle text-info">Leave</span>
                                                @break
                                            @case('half_day')
                                                <span class="badge bg-secondary-subtle text-secondary">Half Day</span>
                                                @break
                                            @default
                                                <span class="badge bg-light text-dark">{{ ucfirst($record->status) }}</span>
                                        @endswitch
                                        
                                        @if($record->late_arrival_minutes > 0)
                                            <br>
                                            <small class="text-muted">
                                                Late: {{ $record->late_arrival_minutes }}m
                                            </small>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            {{ ucfirst($record->attendance_type) }}
                                        </span>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        No detailed records found for the selected filters
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    @if($detailedReport->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing <span>{{ $detailedReport->firstItem() ?? 0 }}</span> to 
                            <span>{{ $detailedReport->lastItem() ?? 0 }}</span> of 
                            <span>{{ $detailedReport->total() }}</span> entries
                        </div>
                        <div class="pagination">
                            {{ $detailedReport->links() }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['node_modules/daterangepicker/daterangepicker.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize date range picker
            const dateRangePicker = $('#date-range-picker');
            const startDateInput = $('#start-date');
            const endDateInput = $('#end-date');
            const dateRangeText = $('#date-range-text');
            
            dateRangePicker.daterangepicker({
                opens: 'left',
                startDate: moment('{{ $filters->startDate->format('Y-m-d') }}'),
                endDate: moment('{{ $filters->endDate->format('Y-m-d') }}'),
                ranges: {
                    'Today': [moment(), moment()],
                    'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                    'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                    'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                    'This Month': [moment().startOf('month'), moment().endOf('month')],
                    'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                },
                locale: {
                    format: 'MMM D, YYYY',
                    cancelLabel: 'Clear'
                }
            }, function(start, end, label) {
                startDateInput.val(start.format('YYYY-MM-DD'));
                endDateInput.val(end.format('YYYY-MM-DD'));
                dateRangeText.text(start.format('MMM D, YYYY') + ' - ' + end.format('MMM D, YYYY'));
            });
            
            // Quick range buttons
            document.querySelectorAll('.quick-range-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const range = this.dataset.range;
                    const ranges = {
                        'today': [moment(), moment()],
                        'yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'week': [moment().subtract(7, 'days'), moment()],
                        'month': [moment().subtract(30, 'days'), moment()],
                        'quarter': [moment().subtract(90, 'days'), moment()],
                        'year': [moment().subtract(365, 'days'), moment()]
                    };
                    
                    if (ranges[range]) {
                        const [start, end] = ranges[range];
                        startDateInput.val(start.format('YYYY-MM-DD'));
                        endDateInput.val(end.format('YYYY-MM-DD'));
                        dateRangeText.text(start.format('MMM D, YYYY') + ' - ' + end.format('MMM D, YYYY'));
                        
                        // Submit form
                        document.getElementById('report-filters-form').submit();
                    }
                });
            });
            
            // Reset filters
            document.getElementById('reset-filters-btn').addEventListener('click', function() {
                // Reset form fields
                document.getElementById('staff-filter').value = '';
                document.getElementById('role-filter').value = '';
                document.getElementById('status-filter').value = '';
                
                // Reset date range to default (last 30 days)
                const defaultStart = moment().subtract(30, 'days');
                const defaultEnd = moment();
                startDateInput.val(defaultStart.format('YYYY-MM-DD'));
                endDateInput.val(defaultEnd.format('YYYY-MM-DD'));
                dateRangeText.text(defaultStart.format('MMM D, YYYY') + ' - ' + defaultEnd.format('MMM D, YYYY'));
                
                // Submit form
                document.getElementById('report-filters-form').submit();
            });
            
            // Export CSV
            document.getElementById('export-csv-btn').addEventListener('click', function() {
                const form = document.getElementById('report-filters-form');
                const originalAction = form.action;
                const originalMethod = form.method;
                
                // Change to export endpoint
                form.action = '{{ route('reports.work-hours.export.csv') }}';
                form.method = 'POST';
                
                // Add CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = csrfToken;
                form.appendChild(csrfInput);
                
                // Submit form
                form.submit();
                
                // Restore original form
                form.removeChild(csrfInput);
                form.action = originalAction;
                form.method = originalMethod;
            });
            
            // Export PDF
            document.getElementById('export-pdf-btn').addEventListener('click', function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Export PDF',
                        text: 'PDF export is currently in development. Would you like to export CSV instead?',
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonText: 'Export CSV',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            document.getElementById('export-csv-btn').click();
                        }
                    });
                } else {
                    alert('PDF export is currently in development. Please use CSV export for now.');
                }
            });
            
            // View staff details
            document.querySelectorAll('.view-details-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const staffId = this.dataset.staffId;
                    const startDate = startDateInput.value;
                    const endDate = endDateInput.value;
                    
                    // Redirect to staff detail page
                    window.location.href = `/reports/work-hours/staff/${staffId}?start_date=${startDate}&end_date=${endDate}`;
                });
            });
            
            // Auto-submit on filter change (optional)
            const autoSubmitFilters = ['staff-filter', 'role-filter', 'status-filter'];
            autoSubmitFilters.forEach(filterId => {
                const element = document.getElementById(filterId);
                if (element) {
                    element.addEventListener('change', function() {
                        document.getElementById('report-filters-form').submit();
                    });
                }
            });
        });
    </script>
@endsection