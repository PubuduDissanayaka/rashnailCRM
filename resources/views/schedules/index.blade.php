@extends('layouts.vertical', ['title' => 'Work Schedules'])

@section('css')
<style>
    .schedule-grid th { vertical-align: middle; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px; }
    .schedule-grid td { vertical-align: middle; }
    .schedule-day { min-width: 110px; text-align: center; }
    .schedule-time { font-size: 0.75rem; font-family: 'JetBrains Mono', monospace; }
    .staff-name { font-weight: 600; white-space: nowrap; }
    .off-day { opacity: 0.5; }
</style>
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Work Schedules'])

<div class="row">
    @if(isset($stats))
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-start border-3 border-primary shadow-none">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="avatar avatar-md bg-primary-subtle rounded-circle"><i class="ti ti-users fs-18 text-primary"></i></div>
                <div><h5 class="mb-0 fw-bold">{{ $stats['total_staff'] }}</h5><small class="text-muted">Staff</small></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-start border-3 border-success shadow-none">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="avatar avatar-md bg-success-subtle rounded-circle"><i class="ti ti-calendar-check fs-18 text-success"></i></div>
                <div><h5 class="mb-0 fw-bold">{{ $stats['with_schedules'] }}</h5><small class="text-muted">Scheduled Staff</small></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-start border-3 border-info shadow-none">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="avatar avatar-md bg-info-subtle rounded-circle"><i class="ti ti-list-details fs-18 text-info"></i></div>
                <div><h5 class="mb-0 fw-bold">{{ $stats['total_entries'] }}</h5><small class="text-muted">Total Entries</small></div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-3">
        <div class="card border-start border-3 border-warning shadow-none">
            <div class="card-body d-flex align-items-center gap-3 py-3">
                <div class="avatar avatar-md bg-warning-subtle rounded-circle"><i class="ti ti-briefcase fs-18 text-warning"></i></div>
                <div><h5 class="mb-0 fw-bold">{{ $stats['working_days'] }}</h5><small class="text-muted">Working Days</small></div>
            </div>
        </div>
    </div>
    @endif
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="card-title mb-0">Staff Weekly Schedules</h4>
                @can('manage work schedules')
                <div class="d-flex gap-2">
                    <a href="{{ route('schedules.create') }}" class="btn btn-primary btn-sm">
                        <i class="ti ti-plus me-1"></i>Add Schedule
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="ti ti-settings me-1"></i>More
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#" onclick="alert('Select a staff member to bulk edit from the table below.'); return false;">
                                <i class="ti ti-layers me-2"></i>Bulk Edit Week
                            </a></li>
                        </ul>
                    </div>
                </div>
                @endcan
            </div>
            <div class="card-body p-0">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show m-3 mb-0" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover table-bordered mb-0 schedule-grid">
                        <thead class="table-light">
                            <tr>
                                <th style="width:160px" class="ps-3">Staff</th>
                                @php $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']; @endphp
                                @foreach($days as $day)
                                <th class="schedule-day">{{ ucfirst(substr($day, 0, 3)) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffMembers as $staff)
                            @php $schedules = $staff->workSchedules->keyBy('day_of_week'); @endphp
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center">
                                            <span class="fw-semibold fs-xs">{{ substr($staff->name, 0, 1) }}</span>
                                        </div>
                                        <span class="staff-name">{{ $staff->name }}</span>
                                    </div>
                                </td>
                                @foreach($days as $day)
                                <td class="schedule-day">
                                    @if(isset($schedules[$day]) && $schedules[$day]->is_working_day)
                                        @php $sched = $schedules[$day]; @endphp
                                        <div>
                                            <span class="schedule-time">{{ $sched->start_time_formatted }}</span>
                                            <span class="text-muted mx-1">-</span>
                                            <span class="schedule-time">{{ $sched->end_time_formatted }}</span>
                                        </div>
                                        @if($sched->grace_period_minutes > 0)
                                        <small class="text-muted d-block" style="font-size:0.65rem">Grace: {{ $sched->grace_period_minutes }}m</small>
                                        @endif
                                        @can('manage work schedules')
                                        <a href="{{ route('schedules.edit', $sched) }}" class="text-primary" style="font-size:0.65rem">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        @endcan
                                    @else
                                        <span class="text-muted off-day fw-semibold" style="font-size:0.7rem">—</span>
                                    @endif
                                </td>
                                @endforeach
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="ti ti-users fs-24 text-muted mb-2 d-block"></i>
                                    <p class="text-muted mb-0">No staff members with schedules found.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
