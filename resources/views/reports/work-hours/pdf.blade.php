<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Hour Report - {{ $filters->getDateRangeString() }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #3b76e1;
            padding-bottom: 15px;
        }
        
        .header h1 {
            color: #3b76e1;
            margin: 0 0 10px 0;
            font-size: 24px;
        }
        
        .header .subtitle {
            color: #666;
            font-size: 14px;
            margin: 0;
        }
        
        .metadata {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .metadata-item {
            flex: 1;
        }
        
        .metadata-label {
            font-weight: 600;
            color: #555;
            margin-bottom: 5px;
        }
        
        .metadata-value {
            font-size: 14px;
            color: #333;
        }
        
        .summary-cards {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .summary-card {
            flex: 1;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
        }
        
        .summary-card.primary {
            background: #e7f1ff;
            border: 1px solid #3b76e1;
        }
        
        .summary-card.success {
            background: #e7f7ef;
            border: 1px solid #28a745;
        }
        
        .summary-card.info {
            background: #e7f7f7;
            border: 1px solid #17a2b8;
        }
        
        .summary-card.warning {
            background: #fff3e6;
            border: 1px solid #fd7e14;
        }
        
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #333;
        }
        
        .summary-card .value {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }
        
        .summary-card.primary .value {
            color: #3b76e1;
        }
        
        .summary-card.success .value {
            color: #28a745;
        }
        
        .summary-card.info .value {
            color: #17a2b8;
        }
        
        .summary-card.warning .value {
            color: #fd7e14;
        }
        
        .section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        
        .section-title {
            background: #3b76e1;
            color: white;
            padding: 10px 15px;
            margin: 0 0 15px 0;
            font-size: 16px;
            border-radius: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        
        table th {
            background: #f8f9fa;
            padding: 10px;
            text-align: left;
            border: 1px solid #dee2e6;
            font-weight: 600;
        }
        
        table td {
            padding: 8px 10px;
            border: 1px solid #dee2e6;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-success {
            background: #e7f7ef;
            color: #28a745;
        }
        
        .badge-warning {
            background: #fff3e6;
            color: #fd7e14;
        }
        
        .badge-danger {
            background: #fdf0f0;
            color: #dc3545;
        }
        
        .badge-info {
            background: #e7f7f7;
            color: #17a2b8;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            color: #666;
            font-size: 11px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .no-data {
            text-align: center;
            padding: 20px;
            color: #666;
            font-style: italic;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Work Hour Report</h1>
        <p class="subtitle">Comprehensive staff work hour analytics</p>
        <p>Generated on: {{ \Carbon\Carbon::now()->format('F j, Y \a\t g:i A') }}</p>
    </div>
    
    <!-- Metadata -->
    <div class="metadata">
        <div class="metadata-item">
            <div class="metadata-label">Report Period</div>
            <div class="metadata-value">{{ $filters->getDateRangeString() }}</div>
        </div>
        <div class="metadata-item">
            <div class="metadata-label">Filters Applied</div>
            <div class="metadata-value">
                @if($filters->hasStaffFilter())
                    Staff: {{ $staffName }}<br>
                @endif
                @if($filters->hasRoleFilter())
                    Role: {{ $roleName }}<br>
                @endif
                @if($filters->hasStatusFilter())
                    Status: {{ ucfirst($filters->status) }}
                @endif
                @if(!$filters->hasStaffFilter() && !$filters->hasRoleFilter() && !$filters->hasStatusFilter())
                    All staff, roles, and statuses
                @endif
            </div>
        </div>
        <div class="metadata-item">
            <div class="metadata-label">Report ID</div>
            <div class="metadata-value">WH-{{ \Carbon\Carbon::now()->format('Ymd-His') }}</div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card primary">
            <h3>Total Staff</h3>
            <p class="value">{{ $summary['total_staff'] ?? 0 }}</p>
        </div>
        <div class="summary-card success">
            <h3>Total Hours</h3>
            <p class="value">{{ $summary['total_hours'] ?? 0 }}</p>
        </div>
        <div class="summary-card info">
            <h3>Attendance Rate</h3>
            <p class="value">{{ $summary['overall_attendance_rate'] ?? 0 }}%</p>
        </div>
        <div class="summary-card warning">
            <h3>Overtime Hours</h3>
            <p class="value">{{ $summary['total_overtime'] ?? 0 }}</p>
        </div>
    </div>
    
    <!-- Staff Summary Section -->
    <div class="section">
        <h2 class="section-title">Staff Summary</h2>
        
        @if($staffSummary->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Staff Member</th>
                    <th class="text-center">Total Days</th>
                    <th class="text-center">Total Hours</th>
                    <th class="text-center">Expected Hours</th>
                    <th class="text-center">Overtime</th>
                    <th class="text-center">Attendance Rate</th>
                    <th class="text-center">Compliance Rate</th>
                </tr>
            </thead>
            <tbody>
                @foreach($staffSummary as $staff)
                <tr>
                    <td>
                        <strong>{{ $staff->staff_name }}</strong><br>
                        <small>{{ $staff->email }}</small>
                    </td>
                    <td class="text-center">{{ $staff->total_days }}</td>
                    <td class="text-center">{{ number_format($staff->total_hours, 2) }}</td>
                    <td class="text-center">{{ number_format($staff->total_expected_hours, 2) }}</td>
                    <td class="text-center">
                        @if($staff->total_overtime > 0)
                            <span class="badge badge-warning">+{{ number_format($staff->total_overtime, 2) }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge badge-success">{{ number_format($staff->attendance_rate, 1) }}%</span>
                    </td>
                    <td class="text-center">
                        <span class="badge badge-info">{{ number_format($staff->compliance_rate, 1) }}%</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td><strong>Totals / Averages</strong></td>
                    <td class="text-center"><strong>{{ $summary['total_days'] ?? 0 }}</strong></td>
                    <td class="text-center"><strong>{{ $summary['total_hours'] ?? 0 }}</strong></td>
                    <td class="text-center"><strong>{{ $summary['total_expected_hours'] ?? 0 }}</strong></td>
                    <td class="text-center"><strong>{{ $summary['total_overtime'] ?? 0 }}</strong></td>
                    <td class="text-center"><strong>{{ $summary['overall_attendance_rate'] ?? 0 }}%</strong></td>
                    <td class="text-center"><strong>{{ $summary['overall_compliance_rate'] ?? 0 }}%</strong></td>
                </tr>
            </tfoot>
        </table>
        @else
        <div class="no-data">
            No staff data found for the selected filters
        </div>
        @endif
    </div>
    
    <!-- Detailed Records Section -->
    <div class="section page-break">
        <h2 class="section-title">Detailed Attendance Records</h2>
        
        @if($detailedReport->isNotEmpty())
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Staff</th>
                    <th class="text-center">Check-in</th>
                    <th class="text-center">Check-out</th>
                    <th class="text-center">Hours</th>
                    <th class="text-center">Expected</th>
                    <th class="text-center">Overtime</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detailedReport as $record)
                <tr>
                    <td>
                        {{ \Carbon\Carbon::parse($record->date)->format('M d, Y') }}<br>
                        <small>{{ \Carbon\Carbon::parse($record->date)->format('l') }}</small>
                    </td>
                    <td>{{ $record->staff_name }}</td>
                    <td class="text-center">
                        @if($record->check_in)
                            {{ \Carbon\Carbon::parse($record->check_in)->format('h:i A') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        @if($record->check_out)
                            {{ \Carbon\Carbon::parse($record->check_out)->format('h:i A') }}
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">{{ number_format($record->hours_worked, 2) }}</td>
                    <td class="text-center">{{ number_format($record->expected_hours, 2) }}</td>
                    <td class="text-center">
                        @if($record->overtime_hours > 0)
                            <span class="badge badge-warning">+{{ number_format($record->overtime_hours, 2) }}</span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        @switch($record->status)
                            @case('present')
                                <span class="badge badge-success">Present</span>
                                @break
                            @case('late')
                                <span class="badge badge-warning">Late</span>
                                @break
                            @case('absent')
                                <span class="badge badge-danger">Absent</span>
                                @break
                            @case('leave')
                                <span class="badge badge-info">Leave</span>
                                @break
                            @default
                                <span class="badge">{{ ucfirst($record->status) }}</span>
                        @endswitch
                        
                        @if($record->late_arrival_minutes > 0)
                            <br><small>Late: {{ $record->late_arrival_minutes }}m</small>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div style="margin-top: 15px; font-size: 11px; color: #666;">
            Showing {{ $detailedReport->count() }} records
        </div>
        @else
        <div class="no-data">
            No detailed records found for the selected filters
        </div>
        @endif
    </div>
    
    <!-- Statistics Section -->
    <div class="section">
        <h2 class="section-title">Report Statistics</h2>
        
        <table>
            <tbody>
                <tr>
                    <td><strong>Total Working Days in Period</strong></td>
                    <td class="text-right">{{ $summary['total_working_days'] ?? 0 }}</td>
                    <td><strong>Average Hours per Staff</strong></td>
                    <td class="text-right">{{ $summary['average_hours_per_staff'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td><strong>Total Present Days</strong></td>
                    <td class="text-right">{{ $summary['total_present_days'] ?? 0 }}</td>
                    <td><strong>Total Late Days</strong></td>
                    <td class="text-right">{{ $summary['total_late_days'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td><strong>Total Absent Days</strong></td>
                    <td class="text-right">{{ $summary['total_absent_days'] ?? 0 }}</td>
                    <td><strong>Total Leave Days</strong></td>
                    <td class="text-right">{{ $summary['total_leave_days'] ?? 0 }}</td>
                </tr>
                <tr>
                    <td><strong>Total Half Days</strong></td>
                    <td class="text-right">{{ $summary['total_half_days'] ?? 0 }}</td>
                    <td><strong>Overall Compliance Rate</strong></td>
                    <td class="text-right">{{ $summary['overall_compliance_rate'] ?? 0 }}%</td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Footer -->
    <div class="footer">
        <p>This report was generated by the Staff Work Hour Reporting System</p>
        <p>Confidential - For internal use only</p>
        <p>Page 1 of 1</p>
    </div>
</body>
</html>