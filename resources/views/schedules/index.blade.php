@extends('layouts.vertical', ['title' => 'Work Schedules'])

@section('css')
<style>
    .schedule-grid th { vertical-align: middle; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; background: #f8f9fa; }
    .schedule-grid td { vertical-align: middle; padding: 6px 8px; }
    .schedule-cell { min-width: 105px; text-align: center; }
    .shift-badge { display: inline-block; padding: 3px 10px; border-radius: 4px; font-size: 0.72rem; font-weight: 600; font-family: 'JetBrains Mono', monospace; cursor: pointer; transition: all .15s; }
    .shift-badge:hover { transform: scale(1.05); }
    .shift-on { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .shift-off { background: #f3f4f6; color: #9ca3af; border: 1px solid #e5e7eb; cursor: pointer; }
    .shift-off:hover { background: #fee2e2; color: #dc2626; border-color: #fecaca; }
    .staff-name { font-weight: 600; font-size: 0.85rem; white-space: nowrap; }
    .total-hours { font-weight: 700; font-size: 0.8rem; color: #0d6efd; }
    .grace-tag { font-size: 0.6rem; color: #9ca3af; display: block; margin-top: 1px; }

    /* Print styles */
    @media print {
        .no-print { display: none !important; }
        .schedule-grid td { padding: 4px 6px; }
        .shift-badge { font-size: 0.65rem; padding: 2px 6px; }
        .card { box-shadow: none !important; border: 1px solid #ddd; }
    }

    /* Stat cards */
    .stat-card { transition: transform .15s; }
    .stat-card:hover { transform: translateY(-2px); }
</style>
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Work Schedules'])

<!-- Stats Row -->
<div class="row no-print">
    @if(isset($stats))
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-start border-3 border-primary shadow-none stat-card">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="avatar avatar-md bg-primary-subtle rounded-circle"><i class="ti ti-users fs-18 text-primary"></i></div>
                <div><h5 class="mb-0 fw-bold">{{ $stats['total_staff'] }}</h5><small class="text-muted">Staff</small></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-start border-3 border-success shadow-none stat-card">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="avatar avatar-md bg-success-subtle rounded-circle"><i class="ti ti-calendar-check fs-18 text-success"></i></div>
                <div><h5 class="mb-0 fw-bold">{{ $stats['with_schedules'] }}</h5><small class="text-muted">Scheduled</small></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-start border-3 border-info shadow-none stat-card">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="avatar avatar-md bg-info-subtle rounded-circle"><i class="ti ti-list-details fs-18 text-info"></i></div>
                <div><h5 class="mb-0 fw-bold">{{ $stats['total_entries'] }}</h5><small class="text-muted">Entries</small></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-start border-3 border-warning shadow-none stat-card">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="avatar avatar-md bg-warning-subtle rounded-circle"><i class="ti ti-briefcase fs-18 text-warning"></i></div>
                <div><h5 class="mb-0 fw-bold">{{ $stats['working_days'] }}</h5><small class="text-muted">Working Days</small></div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Schedule Grid -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <!-- Header toolbar -->
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div class="d-flex align-items-center gap-2">
                    <h4 class="card-title mb-0">Weekly Schedule</h4>
                    <!-- Staff filter -->
                    <select id="staff-filter" class="form-select form-select-sm" style="width:auto;min-width:140px">
                        <option value="">All Staff</option>
                        @foreach($staffMembers as $staff)
                        <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="d-flex gap-1 no-print">
                    @can('manage work schedules')
                    <a href="{{ route('schedules.create') }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-plus me-1"></i>Add
                    </a>
                    @endcan
                    <button id="print-schedule-btn" class="btn btn-light btn-sm">
                        <i class="ti ti-printer me-1"></i>Print
                    </button>
                    @can('manage work schedules')
                    <div class="dropdown">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i>More
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" data-bs-toggle="modal" data-bs-target="#copyWeekModal" href="#">
                                <i class="ti ti-copy me-2"></i>Copy Week
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('schedules.create') }}">
                                <i class="ti ti-plus me-2"></i>New Schedule
                            </a></li>
                        </ul>
                    </div>
                    @endcan
                </div>
            </div>

            <!-- Table -->
            <div class="card-body p-0">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show m-3 mb-0" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0 schedule-grid">
                        <thead>
                            <tr>
                                <th style="width:140px" class="ps-3">Staff</th>
                                @php $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']; @endphp
                                @foreach($days as $day)
                                <th class="schedule-cell">{{ substr(ucfirst($day), 0, 3) }}</th>
                                @endforeach
                                <th style="width:60px;text-align:center">Hrs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php $grandTotal = 0; @endphp
                            @forelse($staffMembers as $staff)
                            @php
                                $schedules = $staff->workSchedules->keyBy('day_of_week');
                                $weeklyHours = 0;
                            @endphp
                            <tr data-staff-id="{{ $staff->id }}">
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center">
                                            <span class="fw-semibold fs-xs">{{ substr($staff->name, 0, 1) }}</span>
                                        </div>
                                        <span class="staff-name">{{ $staff->name }}</span>
                                    </div>
                                </td>
                                @foreach($days as $day)
                                <td class="schedule-cell" data-day-cell>
                                    @if(isset($schedules[$day]))
                                        @php $sched = $schedules[$day]; @endphp
                                        @if($sched->is_working_day)
                                            @php
                                                $start = \Carbon\Carbon::parse($sched->start_time);
                                                $end = \Carbon\Carbon::parse($sched->end_time);
                                                $diffHours = $start->diffInMinutes($end) / 60;
                                                $weeklyHours += $diffHours;
                                            @endphp
                                            <span class="shift-badge shift-on" title="Click to toggle off">
                                                {{ substr($sched->start_time_formatted, 0, 5) }}-{{ substr($sched->end_time_formatted, 0, 5) }}
                                            </span>
                                            @if($sched->grace_period_minutes > 0)
                                            <span class="grace-tag">G:{{ $sched->grace_period_minutes }}m</span>
                                            @endif
                                            @can('manage work schedules')
                                            <div class="mt-1">
                                                <a href="{{ route('schedules.edit', $sched->id) }}" class="text-primary" style="font-size:0.6rem"><i class="ti ti-edit"></i></a>
                                                <button type="button" class="btn btn-sm p-0 ms-1 text-danger" style="font-size:0.6rem;line-height:1;border:none;background:none"
                                                    onclick="if(confirm('Delete this schedule?')) { document.getElementById('del-sched-{{ $sched->id }}').submit(); }">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                                <form id="del-sched-{{ $sched->id }}" action="{{ route('schedules.destroy', $sched->id) }}" method="POST" class="d-none">@csrf @method('DELETE')</form>
                                            </div>
                                            @endcan
                                        @else
                                            <span class="shift-badge shift-off" data-toggle-day data-schedule-id="{{ $sched->id }}" data-is-working-day="0" title="Click to set as working day">OFF</span>
                                        @endif
                                    @else
                                        <span class="text-muted" style="font-size:0.65rem">—</span>
                                    @endif
                                </td>
                                @endforeach
                                <td style="text-align:center">
                                    @if($weeklyHours > 0)
                                    <span class="total-hours" data-total-hours>{{ number_format($weeklyHours, 1) }}h</span>
                                    @php $grandTotal += $weeklyHours; @endphp
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="ti ti-calendar-off fs-24 text-muted mb-2 d-block"></i>
                                    <p class="text-muted mb-0">No staff members found.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <td class="ps-3 fw-semibold">Total Hours</td>
                                <td colspan="7"></td>
                                <td style="text-align:center"><span class="total-hours">{{ number_format($grandTotal, 1) }}h</span></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Copy Week Modal -->
@can('manage work schedules')
<div class="modal fade no-print" id="copyWeekModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Copy Week Schedule</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="copy-week-form" action="{{ route('schedules.copy-week') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Copy From</label>
                        <select name="from_user_id" class="form-select" required>
                            <option value="">Select staff...</option>
                            @foreach($staffMembers as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Copy To</label>
                        <select name="to_user_id" class="form-select" required>
                            <option value="">Select staff...</option>
                            @foreach($staffMembers as $staff)
                            <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="text-muted small mb-0">This will replace ALL existing schedules for the target staff member.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="ti ti-copy me-1"></i> Copy Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan

<!-- CSRF token for JS -->
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('scripts')
@vite(['resources/js/pages/schedule-grid.js'])
@endsection
