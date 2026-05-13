@extends('layouts.vertical', ['title' => 'Appointments Report'])

@section('scripts')
<script>
window.__reportData = {
    dailyDates:        @json($dailyTrend->pluck('date')->values()->all()),
    dailyCounts:       @json($dailyTrend->pluck('count')->values()->all()),
    statusLabels:      @json(array_keys($statusDist)),
    statusCounts:      @json(array_values($statusDist)),
    dowLabels:         @json($dowLabels),
    dowCounts:         @json($dowCounts),
    serviceNames:      @json($topServices->pluck(fn($s) => $s->service?->name ?? 'Unknown')->values()->all()),
    serviceCounts:     @json($topServices->pluck('count')->values()->all()),
};
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('export-btn').addEventListener('click', function() {
        const start = document.querySelector('input[name="start_date"]').value;
        const end = document.querySelector('input[name="end_date"]').value;
        window.location.href = '{{ route("reports.export", ["type" => "appointments"]) }}?start_date=' + start + '&end_date=' + end;
    });
});
</script>
@vite(['resources/js/pages/reports-appointments.js'])
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Appointments Report', 'subtitle' => 'Reports'])

{{-- Filter Card --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.appointments') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-auto">
                    <label class="form-label fw-medium">Quick Select</label>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-secondary preset-btn" data-range="today">Today</button>
                        <button type="button" class="btn btn-outline-secondary preset-btn" data-range="this_week">This Week</button>
                        <button type="button" class="btn btn-outline-secondary preset-btn" data-range="this_month">This Month</button>
                        <button type="button" class="btn btn-outline-secondary preset-btn" data-range="last_30">Last 30 Days</button>
                        <button type="button" class="btn btn-outline-secondary preset-btn" data-range="this_year">This Year</button>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-medium">From Date</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-medium">To Date</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-medium">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        @foreach(['scheduled','confirmed','in_progress','completed','cancelled','no_show'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-medium">Staff Member</label>
                    <select name="user_id" class="form-select">
                        <option value="">All Staff</option>
                        @foreach($staffList as $staff)
                            <option value="{{ $staff->id }}" {{ request('user_id') == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-medium">Service</label>
                    <select name="service_id" class="form-select">
                        <option value="">All Services</option>
                        @foreach($serviceList as $service)
                            <option value="{{ $service->id }}" {{ request('service_id') == $service->id ? 'selected' : '' }}>{{ $service->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Apply</button>
                    <a href="{{ route('reports.appointments') }}" class="btn btn-outline-secondary"><i class="ti ti-refresh me-1"></i>Reset</a>
                    <button type="button" class="btn btn-success" id="export-btn"><i class="ti ti-download me-1"></i>Export CSV</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    @php
    $completedCount = $statusDist['completed'] ?? 0;
    $cancelledCount = $statusDist['cancelled'] ?? 0;
    $scheduledCount = $statusDist['scheduled'] ?? 0;
    $kpis = [
        ['label' => 'Total Appointments', 'value' => number_format($totalAppts),           'icon' => 'ti-calendar-event',   'color' => 'primary'],
        ['label' => 'Completion Rate',    'value' => $completionRate . '%',                   'icon' => 'ti-check-circle',     'color' => 'success'],
        ['label' => 'Cancellation Rate',  'value' => $cancellationRate . '%',                 'icon' => 'ti-x-circle',         'color' => 'danger'],
        ['label' => 'Avg Per Day',        'value' => number_format($avgPerDay, 1),           'icon' => 'ti-trending-up',      'color' => 'info'],
        ['label' => 'Completed',          'value' => number_format($completedCount),          'icon' => 'ti-circle-check',     'color' => 'success'],
        ['label' => 'Cancelled',          'value' => number_format($cancelledCount),          'icon' => 'ti-ban',              'color' => 'danger'],
    ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-animate h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="fw-medium text-muted mb-0 fs-sm">{{ $kpi['label'] }}</p>
                        <h4 class="mt-3 mb-0 ff-secondary fw-semibold">{{ $kpi['value'] }}</h4>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-{{ $kpi['color'] }}-subtle rounded-circle fs-2">
                            <i class="ti {{ $kpi['icon'] }} text-{{ $kpi['color'] }}"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Charts Row 1 --}}
<div class="row mb-4">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Daily Appointment Trend</h5></div>
            <div class="card-body pb-0"><div id="appt-daily-trend-chart"></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Status Distribution</h5></div>
            <div class="card-body"><div id="appt-status-dist-chart"></div></div>
        </div>
    </div>
</div>

{{-- Charts Row 2 --}}
<div class="row mb-4">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">By Day of Week</h5></div>
            <div class="card-body pb-0"><div id="appt-dow-chart"></div></div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Top Services</h5></div>
            <div class="card-body pb-0"><div id="appt-top-services-chart"></div></div>
        </div>
    </div>
</div>

{{-- Staff Utilization Table --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-light"><h5 class="card-title mb-0">Staff Utilization</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-sm table-nowrap table-hover mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3">Staff</th>
                                <th class="text-center">Total</th>
                                <th class="text-center">Completed</th>
                                <th class="text-center">Cancelled</th>
                                <th class="text-center pe-3">Completion %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffUtilization as $row)
                            @php $compPct = $row->total > 0 ? round(($row->completed_count / $row->total) * 100, 1) : 0; @endphp
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs"><span class="avatar-title rounded-circle bg-primary-subtle text-primary fs-xs">{{ strtoupper(substr($row->user?->name ?? 'S', 0, 1)) }}</span></div>
                                        {{ $row->user?->name ?? 'Unknown' }}
                                    </div>
                                </td>
                                <td class="text-center"><span class="badge bg-info-subtle text-info">{{ $row->total }}</span></td>
                                <td class="text-center"><span class="badge bg-success-subtle text-success">{{ $row->completed_count }}</span></td>
                                <td class="text-center"><span class="badge bg-danger-subtle text-danger">{{ $row->cancelled_count }}</span></td>
                                <td class="text-center pe-3">
                                    <div class="d-flex align-items-center gap-2 justify-content-center">
                                        <span class="fw-semibold">{{ $compPct }}%</span>
                                        <div class="progress flex-grow-1" style="max-width:80px;height:6px">
                                            <div class="progress-bar bg-{{ $compPct >= 80 ? 'success' : ($compPct >= 50 ? 'warning' : 'danger') }}" style="width:{{ $compPct }}%"></div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No staff utilization data for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Top Services Table --}}
<div class="card">
    <div class="card-header border-light"><h5 class="card-title mb-0">Top Services by Bookings</h5></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-centered table-sm table-nowrap table-hover mb-0">
                <thead class="bg-light bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th class="ps-3">#</th><th>Service</th><th class="text-center">Bookings</th><th class="text-center pe-3">Share</th>
                    </tr>
                </thead>
                <tbody>
                    @php $maxCount = $topServices->max('count') ?: 1; @endphp
                    @forelse($topServices as $idx => $row)
                    <tr>
                        <td class="ps-3 fw-semibold text-muted">{{ $idx + 1 }}</td>
                        <td>{{ $row->service?->name ?? 'Unknown' }}</td>
                        <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $row->count }}</span></td>
                        <td class="text-center pe-3">
                            <div class="d-flex align-items-center gap-2 justify-content-center">
                                <span class="fs-sm">{{ round(($row->count / $maxCount) * 100) }}%</span>
                                <div class="progress flex-grow-1" style="max-width:80px;height:6px">
                                    <div class="progress-bar bg-primary" style="width:{{ round(($row->count / $maxCount) * 100) }}%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center py-4 text-muted">No service booking data for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Date Preset Script --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const today = new Date();
    const fmt = d => d.toISOString().split('T')[0];

    const presets = {
        today:      () => { const d = new Date(); return { start: fmt(d), end: fmt(d) }; },
        this_week:  () => { const d = new Date(); const dow = d.getDay(); const start = new Date(d); start.setDate(d.getDate()-dow); return { start: fmt(start), end: fmt(d) }; },
        this_month: () => { const d = new Date(); return { start: fmt(new Date(d.getFullYear(), d.getMonth(), 1)), end: fmt(d) }; },
        last_30:    () => { const d = new Date(); const s = new Date(d); s.setDate(d.getDate()-29); return { start: fmt(s), end: fmt(d) }; },
        this_year:  () => { const d = new Date(); return { start: fmt(new Date(d.getFullYear(), 0, 1)), end: fmt(d) }; },
    };

    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const range = this.dataset.range;
            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const { start, end } = presets[range]();
            document.querySelector('input[name="start_date"]').value = start;
            document.querySelector('input[name="end_date"]').value = end;
        });
    });
});
</script>
@endsection
