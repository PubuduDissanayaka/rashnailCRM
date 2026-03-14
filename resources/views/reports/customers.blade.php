@extends('layouts.vertical', ['title' => 'Customers Report'])

@section('scripts')
<script>
window.currencySymbol = @json($currencySymbol ?? '$');
window.__reportData = {
    acqMonths:       @json($acqMonths),
    acqCounts:       @json($acqCounts),
    genderLabels:    @json(array_keys($genderBreakdown)),
    genderCounts:    @json(array_values($genderBreakdown)),
    statusLabels:    @json(array_keys($statusBreakdown)),
    statusCounts:    @json(array_values($statusBreakdown)),
    topNames:        @json($topCustomers->map(fn($c) => $c->first_name . ' ' . $c->last_name)->values()->all()),
    topSpend:        @json($topCustomers->pluck('total_spent')->values()->all()),
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

{{-- Filter --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.customers') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label fw-medium">Joined From</label>
                    <input type="date" name="start_date" class="form-control" value="{{ $startDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-medium">Joined To</label>
                    <input type="date" name="end_date" class="form-control" value="{{ $endDate->format('Y-m-d') }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-medium">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="active"   {{ request('status') === 'active'   ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-medium">Gender</label>
                    <select name="gender" class="form-select">
                        <option value="">All Genders</option>
                        @foreach(['male','female','other','prefer_not_to_say'] as $g)
                            <option value="{{ $g }}" {{ request('gender') === $g ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$g)) }}</option>
                        @endforeach
                    </select>
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
        ['label' => 'Total Customers',   'value' => number_format($totalCustomers), 'icon' => 'ti-users',         'color' => 'primary'],
        ['label' => 'New This Month',    'value' => number_format($newThisMonth),   'icon' => 'ti-user-plus',     'color' => 'success'],
        ['label' => 'Active Rate',       'value' => $activeRate . '%',              'icon' => 'ti-activity',      'color' => 'info'],
        ['label' => 'Avg Lifetime Value','value' => ($currencySymbol) . number_format($avgLTV, 2), 'icon' => 'ti-wallet', 'color' => 'warning'],
    ];
    @endphp
    @foreach($kpis as $kpi)
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="fw-medium text-muted mb-0 fs-sm">{{ $kpi['label'] }}</p>
                        <h3 class="mt-3 mb-0 ff-secondary fw-semibold">{{ $kpi['value'] }}</h3>
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
            <div class="card-header border-light"><h5 class="card-title mb-0">New Customer Acquisitions</h5></div>
            <div class="card-body pb-0"><div id="cust-acquisition-chart"></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Gender Distribution</h5></div>
            <div class="card-body"><div id="cust-gender-chart"></div></div>
        </div>
    </div>
</div>

{{-- Charts Row 2 --}}
<div class="row mb-4">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Customer Status</h5></div>
            <div class="card-body"><div id="cust-status-chart"></div></div>
        </div>
    </div>
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Top Customers by Spend</h5></div>
            <div class="card-body pb-0"><div id="cust-top-spend-chart"></div></div>
        </div>
    </div>
</div>

{{-- Tables --}}
<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header border-light"><h5 class="card-title mb-0">Top Customers by Spend</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-centered table-sm table-nowrap table-hover mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs"><th class="ps-3">#</th><th>Customer</th><th class="text-end pe-3">Total Spent</th></tr>
                        </thead>
                        <tbody>
                            @forelse($topCustomers as $i => $cust)
                            <tr>
                                <td class="ps-3 text-muted">{{ $i + 1 }}</td>
                                <td>
                                    <div class="fw-semibold">{{ $cust->first_name }} {{ $cust->last_name }}</div>
                                    <small class="text-muted">{{ $cust->phone }}</small>
                                </td>
                                <td class="text-end pe-3 fw-semibold text-success">{{ $currencySymbol }}{{ number_format($cust->total_spent, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">No data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header border-light"><h5 class="card-title mb-0">Recently Joined</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-centered table-sm table-nowrap table-hover mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs"><th class="ps-3">Customer</th><th class="text-center">Status</th><th class="text-end pe-3">Joined</th></tr>
                        </thead>
                        <tbody>
                            @forelse($recentCustomers as $cust)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold">{{ $cust->first_name }} {{ $cust->last_name }}</div>
                                    <small class="text-muted">{{ $cust->phone }}</small>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $cust->status === 'active' ? 'success' : 'secondary' }}-subtle text-{{ $cust->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($cust->status) }}</span>
                                </td>
                                <td class="text-end pe-3 text-muted fs-sm">{{ $cust->created_at->format('d M Y') }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">No data.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header border-light">
                <h5 class="card-title mb-0">Inactive Customers</h5>
                <p class="text-muted mb-0 fs-sm">No visit in 90+ days</p>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-centered table-sm table-nowrap table-hover mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs"><th class="ps-3">Customer</th><th class="text-end pe-3">Phone</th></tr>
                        </thead>
                        <tbody>
                            @forelse($inactiveCustomers as $cust)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold">{{ $cust->first_name }} {{ $cust->last_name }}</div>
                                    <small class="text-muted">{{ $cust->email }}</small>
                                </td>
                                <td class="text-end pe-3 text-muted">{{ $cust->phone }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-center py-4 text-success"><i class="ti ti-circle-check me-1"></i>All customers are active!</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
