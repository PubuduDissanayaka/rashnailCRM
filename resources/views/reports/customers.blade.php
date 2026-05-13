@extends('layouts.vertical', ['title' => 'Customers Report'])

@section('scripts')
<script>
window.currencySymbol = @json($currencySymbol ?? '$');
window.__reportData = {
    acqMonths:    @json($acqMonths),
    acqCounts:    @json($acqCounts),
    genderLabels: @json(array_keys($genderBreakdown)),
    genderCounts: @json(array_values($genderBreakdown)),
    statusLabels: @json(array_keys($statusBreakdown)),
    statusCounts: @json(array_values($statusBreakdown)),
    topNames:     @json($topCustomers->map(fn($c) => $c->first_name . ' ' . $c->last_name)->values()->all()),
    topSpend:     @json($topCustomers->map(fn($c) => (float) ($c->total_spent ?? 0))->values()->all()),
};
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('export-btn').addEventListener('click', function() {
        const start = document.querySelector('input[name="start_date"]').value;
        const end = document.querySelector('input[name="end_date"]').value;
        window.location.href = '{{ route("reports.export", ["type" => "customers"]) }}?start_date=' + start + '&end_date=' + end;
    });
});
</script>
@vite(['resources/js/pages/reports-customers.js'])
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Customers Report', 'subtitle' => 'Reports'])

{{-- Filter Card --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.customers') }}">
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
                <div class="col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Apply</button>
                    <a href="{{ route('reports.customers') }}" class="btn btn-outline-secondary"><i class="ti ti-refresh me-1"></i>Reset</a>
                    <button type="button" class="btn btn-success" id="export-btn"><i class="ti ti-download me-1"></i>Export CSV</button>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    @php
    $kpis = [
        ['label' => 'Total Customers',  'value' => number_format($totalCustomers),                       'icon' => 'ti-users',           'color' => 'primary'],
        ['label' => 'New This Month',   'value' => number_format($newThisMonth),                          'icon' => 'ti-user-plus',       'color' => 'success'],
        ['label' => 'Active Rate',      'value' => $activeRate . '%',                                     'icon' => 'ti-user-check',      'color' => 'info'],
        ['label' => 'Avg LTV',          'value' => $currencySymbol . number_format($avgLTV, 2),           'icon' => 'ti-cash',            'color' => 'warning'],
        ['label' => 'Active Customers', 'value' => number_format($statusBreakdown['active'] ?? 0),        'icon' => 'ti-activity',        'color' => 'success'],
        ['label' => 'Inactive',         'value' => number_format($statusBreakdown['inactive'] ?? 0),      'icon' => 'ti-user-off',        'color' => 'secondary'],
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
            <div class="card-header border-light"><h5 class="card-title mb-0">Customer Acquisitions (12 Months)</h5></div>
            <div class="card-body pb-0"><div id="cust-acquisition-chart"></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Gender Breakdown</h5></div>
            <div class="card-body"><div id="cust-gender-chart"></div></div>
        </div>
    </div>
</div>

{{-- Charts Row 2 --}}
<div class="row mb-4">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Status Distribution</h5></div>
            <div class="card-body"><div id="cust-status-chart"></div></div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Top Customers by Spend</h5></div>
            <div class="card-body pb-0"><div id="cust-top-spend-chart"></div></div>
        </div>
    </div>
</div>

{{-- Tables Row --}}
<div class="row mb-4">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Top Customers by Spend</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-sm table-nowrap table-hover mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3">Customer</th><th class="text-center">Status</th><th class="text-end pe-3">Total Spent</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($topCustomers as $row)
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs"><span class="avatar-title rounded-circle bg-warning-subtle text-warning fs-xs">{{ strtoupper(substr($row->first_name ?? 'C', 0, 1)) }}</span></div>
                                        <div>
                                            <span class="fw-semibold">{{ $row->first_name }} {{ $row->last_name }}</span>
                                            <small class="d-block text-muted fs-xs">{{ $row->phone ?? '—' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @php $sc = $row->status === 'active' ? 'success' : ($row->status === 'inactive' ? 'secondary' : 'warning') @endphp
                                    <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($row->status ?? 'unknown') }}</span>
                                </td>
                                <td class="text-end pe-3 fw-semibold text-success">{{ $currencySymbol }}{{ number_format($row->total_spent ?? 0, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">No customer spend data available.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Recently Joined</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-sm table-nowrap table-hover mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3">Customer</th><th class="text-center">Gender</th><th class="text-center">Status</th><th class="text-end pe-3">Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentCustomers as $row)
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs"><span class="avatar-title rounded-circle bg-primary-subtle text-primary fs-xs">{{ strtoupper(substr($row->first_name ?? 'C', 0, 1)) }}</span></div>
                                        <div>
                                            <span class="fw-semibold">{{ $row->first_name }} {{ $row->last_name }}</span>
                                            <small class="d-block text-muted fs-xs">{{ $row->email ?? '—' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-center">{{ ucfirst($row->gender ?? 'N/A') }}</td>
                                <td class="text-center">
                                    @php $sc = $row->status === 'active' ? 'success' : ($row->status === 'inactive' ? 'secondary' : 'warning') @endphp
                                    <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($row->status) }}</span>
                                </td>
                                <td class="text-end pe-3 fs-sm">{{ $row->created_at?->format('d M Y') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">No recent customers found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Inactive Customers Alert --}}
<div class="card">
    <div class="card-header border-light">
        <div class="d-flex align-items-center">
            <h5 class="card-title mb-0 flex-grow-1">At-Risk Customers <small class="text-muted fs-sm">(no appointment in 90+ days)</small></h5>
            <span class="badge bg-danger-subtle text-danger fs-sm">{{ $inactiveCustomers->count() }} customers</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-centered table-sm table-nowrap table-hover mb-0">
                <thead class="bg-light bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th class="ps-3">Customer</th><th>Phone</th><th>Email</th><th>Gender</th><th class="text-end pe-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inactiveCustomers as $row)
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $row->first_name }} {{ $row->last_name }}</td>
                        <td>{{ $row->phone ?? '—' }}</td>
                        <td>{{ $row->email ?? '—' }}</td>
                        <td>{{ ucfirst($row->gender ?? 'N/A') }}</td>
                        <td class="text-end pe-3">
                            <span class="badge bg-danger-subtle text-danger">Inactive Risk</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center py-4 text-muted"><i class="ti ti-mood-happy fs-2 d-block mb-2"></i>All customers are active — great job!</td></tr>
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
