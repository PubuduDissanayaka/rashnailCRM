@extends('layouts.vertical', ['title' => 'Dashboard'])

@section('css')
<style>
.stat-card { transition: transform .15s ease, box-shadow .15s ease; }
.stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,.08); }
.stat-card .avatar-title { font-size: 1.5rem; }
.sparkline-area { height: 40px; }
#revenueChart, #apptDoughnut { min-height: 260px; }
.quick-action-btn { cursor: pointer; }
.dashboard-table td, .dashboard-table th { padding: 0.4rem 0.5rem; font-size: 0.8rem; white-space: nowrap; }
@media (max-width: 576px) {
.stat-card .avatar-lg { width: 36px; height: 36px; }
.stat-card .avatar-title { font-size: 1rem; }
.stat-card h3 { font-size: 1rem; }
}
</style>
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Dashboard'])

{{-- ===== KPI ROW ===== --}}
<div class="row row-cols-xxl-4 row-cols-lg-2 row-cols-1 g-3 mb-3">
    {{-- Today's Appointments --}}
    <div class="col">
        <div class="card stat-card h-100 border-start border-info border-3">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="avatar avatar-lg flex-shrink-0">
                        <span class="avatar-title bg-info-subtle text-info rounded-circle"><i class="ti ti-calendar-check"></i></span>
                    </div>
                    <div class="text-end flex-grow-1 ms-3">
                        <h3 class="mb-0 fw-bold">{{ $todayAppointmentsCount }}</h3>
                        <p class="mb-0 text-muted small">Today's Appointments</p>
                        <a href="{{ route('appointments.index') }}" class="text-info small">View all →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('manage system')
    {{-- Today's Revenue --}}
    <div class="col">
        <div class="card stat-card h-100 border-start border-success border-3">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="avatar avatar-lg flex-shrink-0">
                        <span class="avatar-title bg-success-subtle text-success rounded-circle"><i class="ti ti-coin"></i></span>
                    </div>
                    <div class="text-end flex-grow-1 ms-3">
                        <h3 class="mb-0 fw-bold">{{ $currencySymbol }}{{ number_format($todayRevenue, 2) }}</h3>
                        <p class="mb-0 text-muted small">Today's Revenue</p>
                        <span class="small text-muted">{{ $currencySymbol }}{{ number_format($monthlyRevenue, 0) }} this month</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endcan

    {{-- Active Customers --}}
    <div class="col">
        <div class="card stat-card h-100 border-start border-warning border-3">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="avatar avatar-lg flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle text-warning rounded-circle"><i class="ti ti-users"></i></span>
                    </div>
                    <div class="text-end flex-grow-1 ms-3">
                        <h3 class="mb-0 fw-bold">{{ $activeCustomersCount }}</h3>
                        <p class="mb-0 text-muted small">Active Customers</p>
                        <a href="{{ route('customers.index') }}" class="text-warning small">Manage →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Staff Online --}}
    <div class="col">
        <div class="card stat-card h-100 border-start border-primary border-3">
            <div class="card-body py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="avatar avatar-lg flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle"><i class="ti ti-user-check"></i></span>
                    </div>
                    <div class="text-end flex-grow-1 ms-3">
                        <h3 class="mb-0 fw-bold">{{ $staffCheckedIn }}<small class="fw-normal text-muted fs-6">/{{ $totalStaff }}</small></h3>
                        <p class="mb-0 text-muted small">Staff Checked In</p>
                        <a href="{{ route('attendance.dashboard') }}" class="text-primary small">View →</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== QUICK ACTIONS ROW ===== --}}
<div class="row g-2 mb-3">
    <div class="col-12">
        <div class="card border-0 shadow-sm bg-primary bg-gradient text-white">
            <div class="card-body py-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5 class="mb-0 fw-semibold text-white"><i class="ti ti-bolt me-1"></i> Quick Actions</h5>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('appointments.create') }}" class="btn btn-light btn-sm px-3 fw-semibold">
                            <i class="ti ti-plus me-1"></i>New Appointment
                        </a>
                        <a href="{{ route('customers.create') }}" class="btn btn-light btn-sm px-3 fw-semibold">
                            <i class="ti ti-user-plus me-1"></i>Add Customer
                        </a>
                        <a href="{{ route('pos.index') }}" class="btn btn-warning btn-sm px-3 fw-semibold text-dark">
                            <i class="ti ti-shopping-cart me-1"></i>New Sale
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== CHARTS ROW ===== --}}
<div class="row g-3 mb-3">
    {{-- Revenue Chart --}}
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <div>
                    <h5 class="card-title mb-0 fs-6">Revenue Analytics</h5>
                    <small class="text-muted">Monthly revenue for the last 6 months</small>
                </div>
                <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary">View Reports</a>
            </div>
            <div class="card-body">
                <div id="revenueChart"></div>
            </div>
        </div>
    </div>

    {{-- Appointment Doughnut --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <div>
                    <h5 class="card-title mb-0 fs-6">Today's Appointments</h5>
                    <small class="text-muted">{{ array_sum($apptStatusCounts) }} total</small>
                </div>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <div id="apptDoughnut" class="w-100"></div>
                <div class="d-flex flex-wrap gap-3 mt-2 justify-content-center small">
                    <div><span class="badge bg-warning me-1">&nbsp;</span> Scheduled {{ $apptStatusCounts[0] }}</div>
                    <div><span class="badge bg-info me-1">&nbsp;</span> In Progress {{ $apptStatusCounts[1] }}</div>
                    <div><span class="badge bg-success me-1">&nbsp;</span> Completed {{ $apptStatusCounts[2] }}</div>
                    <div><span class="badge bg-secondary me-1">&nbsp;</span> Cancelled {{ $apptStatusCounts[3] }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== LISTS ROW ===== --}}
<div class="row g-3 mb-3">
    {{-- Today's Schedule --}}
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <h5 class="card-title mb-0 fs-6"><i class="ti ti-calendar-event me-1 text-primary"></i> Today's Schedule</h5>
                <div class="d-flex gap-1">
                    <a href="{{ route('appointments.create') }}" class="btn btn-sm btn-primary"><i class="ti ti-plus me-1"></i>Book</a>
                    <a href="{{ route('appointments.calendar') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
            </div>
            <div class="card-body p-0">
                @if($todayAppointments->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 dashboard-table">
                        <thead class="table-light small">
                            <tr>
                                <th>Time</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Staff</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($todayAppointments as $apt)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($apt->appointment_date)->format('h:i A') }}</td>
                                <td><a href="{{ route('customers.show', $apt->customer) }}" class="text-reset">{{ $apt->customer->full_name }}</a></td>
                                <td class="text-muted">{{ $apt->service?->name ?? '-' }} <small>{{ $apt->service?->duration ?? '' }}m</small></td>
                                <td>{{ $apt->user?->name ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-{{ $apt->status === 'completed' ? 'success' : ($apt->status === 'in_progress' ? 'info' : ($apt->status === 'cancelled' ? 'secondary' : 'warning')) }}">
                                        {{ ucfirst($apt->status) }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5 text-muted">
                    <i class="ti ti-calendar-off fs-1 d-block mb-2"></i>
                    <p class="mb-0">No appointments scheduled for today.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Recent Sales --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <h5 class="card-title mb-0 fs-6"><i class="ti ti-receipt me-1 text-success"></i> Recent Sales</h5>
                <a href="{{ route('pos.transactions') }}" class="btn btn-sm btn-outline-secondary">View All</a>
            </div>
            <div class="card-body p-0">
                @if($recentSales->count())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 dashboard-table">
                        <thead class="table-light small">
                            <tr><th>#</th><th>Customer</th><th>Amount</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            @foreach($recentSales as $sale)
                            <tr>
                                <td class="text-muted">{{ $sale->sale_number }}</td>
                                <td>{{ $sale->customer?->full_name ?? 'Walk-in' }}</td>
                                <td class="fw-medium">{{ $currencySymbol }}{{ number_format($sale->total_amount, 2) }}</td>
                                <td><span class="badge bg-success-subtle text-success">Completed</span></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5 text-muted">
                    <i class="ti ti-shopping-cart-off fs-1 d-block mb-2"></i>
                    <p class="mb-0">No sales yet.</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Right Sidebar: Staff + Stock + Quick Actions --}}
    <div class="col-lg-3">
        {{-- Staff Attendance --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <h5 class="card-title mb-0 fs-6"><i class="ti ti-clock-check me-1 text-primary"></i> Staff</h5>
                <a href="{{ route('attendance.dashboard') }}" class="small">View All</a>
            </div>
            <div class="card-body py-2">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <div class="flex-shrink-0">
                        @if($staffCheckedIn > 0)
                        <span class="avatar avatar-sm bg-success text-white">{{ $staffCheckedIn }}</span>
                        @else
                        <span class="avatar avatar-sm bg-light text-muted">0</span>
                        @endif
                    </div>
                    <div>
                        <p class="mb-0 fw-medium">{{ $staffCheckedIn }} of {{ $totalStaff }} checked in</p>
                        <small class="text-muted">{{ $totalStaff - $staffCheckedIn }} still to clock in</small>
                    </div>
                </div>
                <div class="progress mt-1" style="height:4px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ $totalStaff > 0 ? ($staffCheckedIn / $totalStaff * 100) : 0 }}%"></div>
                </div>
            </div>
        </div>

        {{-- Low Stock --}}
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <h5 class="card-title mb-0 fs-6"><i class="ti ti-package me-1 text-warning"></i> Supplies</h5>
                <a href="{{ route('inventory.supplies.index') }}" class="small">Manage</a>
            </div>
            <div class="card-body py-2">
                @if($lowStockItems->count())
                <ul class="list-unstyled mb-0 small">
                    @foreach($lowStockItems as $item)
                    <li class="d-flex justify-content-between py-1 border-bottom border-light">
                        <span>{{ $item->name }}</span>
                        <span class="text-danger fw-medium">{{ $item->current_stock }}/{{ $item->min_stock_level }}</span>
                    </li>
                    @endforeach
                </ul>
                @else
                <div class="text-center py-3 text-muted small">
                    <i class="ti ti-circle-check text-success d-block mb-1 fs-5"></i>
                    All supplies well stocked
                </div>
                @endif
            </div>
        </div>

    </div>
</div>

{{-- ===== WEEKLY SPARKLINE + PAYMENT METHODS ===== --}}
<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-2">
                <h5 class="card-title mb-0 fs-6"><i class="ti ti-trending-up me-1 text-success"></i> This Week</h5>
            </div>
            <div class="card-body py-2">
                <div id="weeklySparkline"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header py-2">
                <h5 class="card-title mb-0 fs-6"><i class="ti ti-credit-card me-1 text-info"></i> Payment Methods <small class="text-muted">(this month)</small></h5>
            </div>
            <div class="card-body py-2">
                @if(!empty($paymentMethods))
                @php $pmTotal = array_sum($paymentMethods); @endphp
                <div class="row g-2">
                    @foreach($paymentMethods as $method => $amount)
                    <div class="col-6">
                        <div class="d-flex justify-content-between small mb-1">
                            <span class="text-capitalize">{{ $method }}</span>
                            <span class="fw-medium">{{ $currencySymbol }}{{ number_format($amount, 0) }}</span>
                        </div>
                        <div class="progress" style="height:6px;">
                            <div class="progress-bar bg-{{ $method === 'cash' ? 'success' : ($method === 'card' ? 'primary' : 'info') }}" 
                                 style="width: {{ $pmTotal > 0 ? ($amount / $pmTotal * 100) : 0 }}%"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <p class="text-muted small mb-0 text-center py-3">No payment data this month.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@4"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue bar chart
    var revOpts = {
        chart: { type: 'bar', height: 260, toolbar: { show: false }, fontFamily: 'inherit' },
        series: [{ name: 'Revenue', data: @json($revenueData) }],
        xaxis: { categories: @json($revenueMonths), labels: { style: { fontSize: '11px' } } },
        yaxis: { labels: { formatter: function(v) { return '{{ $currencySymbol }}' + v.toFixed(0); }, style: { fontSize: '11px' } } },
        colors: ['#0d6efd'],
        plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
        dataLabels: { enabled: false },
        grid: { borderColor: '#e9ecef', strokeDashArray: 4 },
        tooltip: { y: { formatter: function(v) { return '{{ $currencySymbol }}' + v.toFixed(2); } } }
    };
    new ApexCharts(document.querySelector('#revenueChart'), revOpts).render();

    // Appointment doughnut
    var donutOpts = {
        chart: { type: 'donut', height: 220, fontFamily: 'inherit' },
        series: @json($apptStatusCounts),
        labels: ['Scheduled', 'In Progress', 'Completed', 'Cancelled'],
        colors: ['#f7b924', '#0dcaf0', '#198754', '#6c757d'],
        legend: { show: false },
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '65%' } } },
        tooltip: { y: { formatter: function(v) { return v + ' appointment' + (v !== 1 ? 's' : ''); } } }
    };
    new ApexCharts(document.querySelector('#apptDoughnut'), donutOpts).render();

    // Weekly sparkline
    var sparkOpts = {
        chart: { type: 'area', height: 50, sparkline: { enabled: true }, fontFamily: 'inherit' },
        series: [{ data: @json($weeklyRevenue) }],
        stroke: { curve: 'smooth', width: 2 },
        fill: { opacity: 0.2 },
        colors: ['#198754'],
        tooltip: { y: { formatter: function(v) { return '{{ $currencySymbol }}' + v.toFixed(2); } } }
    };
    new ApexCharts(document.querySelector('#weeklySparkline'), sparkOpts).render();
});
</script>
@endsection
