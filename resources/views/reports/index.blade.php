@extends('layouts.vertical', ['title' => 'Reports Hub'])

@section('css')
@endsection

@section('scripts')
<script>
window.currencySymbol = @json($currencySymbol ?? '$');
window.__reportData = {
    months: @json($months),
    revenue: @json($revenues),
    expenses: @json($expensesArr),
    apptStatus: @json($apptStatusToday),
};
</script>
@vite(['resources/js/pages/reports-dashboard.js'])
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Reports Hub', 'subtitle' => 'Analytics & Reports'])

{{-- KPI Cards --}}
<div class="row g-3">
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-animate h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="fw-medium text-muted mb-0">Monthly Revenue</p>
                        <h3 class="mt-3 mb-1 ff-secondary fw-semibold">{{ $currencySymbol }}{{ number_format($monthRevenue, 2) }}</h3>
                        <p class="mb-0 text-muted fs-sm">This month</p>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-success-subtle rounded-circle fs-2">
                            <i class="ti ti-cash text-success"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-animate h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="fw-medium text-muted mb-0">Today's Appointments</p>
                        <h3 class="mt-3 mb-1 ff-secondary fw-semibold">{{ $todayAppts }}</h3>
                        <p class="mb-0 text-muted fs-sm">Scheduled today</p>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-info-subtle rounded-circle fs-2">
                            <i class="ti ti-calendar-check text-info"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-animate h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="fw-medium text-muted mb-0">Active Customers</p>
                        <h3 class="mt-3 mb-1 ff-secondary fw-semibold">{{ number_format($activeCustomers) }}</h3>
                        <p class="mb-0 text-muted fs-sm">Total active</p>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-primary-subtle rounded-circle fs-2">
                            <i class="ti ti-users text-primary"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-animate h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="fw-medium text-muted mb-0">Pending Expenses</p>
                        <h3 class="mt-3 mb-1 ff-secondary fw-semibold">{{ $currencySymbol }}{{ number_format($pendingExpenses, 2) }}</h3>
                        <p class="mb-0 text-muted fs-sm">Awaiting approval</p>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-warning-subtle rounded-circle fs-2">
                            <i class="ti ti-receipt text-warning"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-animate h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="fw-medium text-muted mb-0">Low Stock Items</p>
                        <h3 class="mt-3 mb-1 ff-secondary fw-semibold">{{ $lowStockCount }}</h3>
                        <p class="mb-0 text-muted fs-sm">Need restocking</p>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle rounded-circle fs-2">
                            <i class="ti ti-package text-danger"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 col-sm-6">
        <div class="card card-animate h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <p class="fw-medium text-muted mb-0">Total Staff</p>
                        <h3 class="mt-3 mb-1 ff-secondary fw-semibold">{{ $totalStaff }}</h3>
                        <p class="mb-0 text-muted fs-sm">Active members</p>
                    </div>
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-secondary-subtle rounded-circle fs-2">
                            <i class="ti ti-user-check text-secondary"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Charts --}}
<div class="row mt-4">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header border-light">
                <div class="d-flex align-items-center">
                    <h4 class="card-title mb-0 flex-grow-1">Revenue vs Expenses</h4>
                    <span class="badge bg-light text-muted">Last 6 Months</span>
                </div>
            </div>
            <div class="card-body pb-0">
                <div id="revenue-vs-expenses-chart"></div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-light">
                <h4 class="card-title mb-0">Today's Appointments</h4>
                <p class="text-muted mb-0 fs-sm">Status breakdown</p>
            </div>
            <div class="card-body">
                <div id="appt-status-donut-chart"></div>
            </div>
        </div>
    </div>
</div>

{{-- Report Module Cards --}}
<div class="row mt-4">
    <div class="col-12 mb-3">
        <h5 class="fw-semibold text-muted text-uppercase fs-sm mb-0">
            <i class="ti ti-layout-grid me-1"></i> Report Modules
        </h5>
    </div>

    @php
    $modules = [
        [
            'title'       => 'Sales & Revenue',
            'icon'        => 'ti-currency-dollar',
            'color'       => 'success',
            'desc'        => 'Revenue trends, payment methods, top services, refunds & staff performance.',
            'route'       => 'reports.sales',
            'stat_label'  => 'This month',
            'stat_value'  => $currencySymbol . number_format($monthRevenue, 2),
        ],
        [
            'title'       => 'Appointments',
            'icon'        => 'ti-calendar-check',
            'color'       => 'info',
            'desc'        => 'Booking trends, completion rates, service popularity & staff utilisation.',
            'route'       => 'reports.appointments',
            'stat_label'  => 'Today',
            'stat_value'  => $todayAppts . ' bookings',
        ],
        [
            'title'       => 'Customers',
            'icon'        => 'ti-users',
            'color'       => 'primary',
            'desc'        => 'Acquisition trends, lifetime value, gender split & inactive customer alerts.',
            'route'       => 'reports.customers',
            'stat_label'  => 'Active',
            'stat_value'  => number_format($activeCustomers) . ' customers',
        ],
        [
            'title'       => 'Expenses',
            'icon'        => 'ti-receipt',
            'color'       => 'warning',
            'desc'        => 'Monthly expense trends, category breakdown, approval pipeline & payment methods.',
            'route'       => 'reports.expenses',
            'stat_label'  => 'Pending',
            'stat_value'  => $currencySymbol . number_format($pendingExpenses, 2),
        ],
        [
            'title'       => 'Inventory',
            'icon'        => 'ti-package',
            'color'       => 'danger',
            'desc'        => 'Stock levels by category, low stock alerts, usage trends & stock value.',
            'route'       => 'reports.inventory',
            'stat_label'  => 'Low stock',
            'stat_value'  => $lowStockCount . ' items',
        ],
        [
            'title'       => 'Work Hours',
            'icon'        => 'ti-clock',
            'color'       => 'secondary',
            'desc'        => 'Staff attendance, work hours, overtime tracking & compliance rates.',
            'route'       => 'reports.work-hours.index',
            'stat_label'  => 'Staff total',
            'stat_value'  => $totalStaff . ' members',
        ],
        [
            'title'       => 'Coupon Reports',
            'icon'        => 'ti-discount',
            'color'       => 'pink',
            'desc'        => 'Redemption analytics, coupon performance, top coupons & usage trends.',
            'route'       => 'reports.coupons.index',
            'stat_label'  => 'Report type',
            'stat_value'  => 'Coupon analytics',
        ],
    ];
    @endphp

    @foreach ($modules as $mod)
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-md flex-shrink-0 me-3">
                        <span class="avatar-title bg-{{ $mod['color'] }}-subtle rounded-3 fs-1">
                            <i class="ti {{ $mod['icon'] }} text-{{ $mod['color'] }}"></i>
                        </span>
                    </div>
                    <div>
                        <h5 class="mb-1 fw-semibold">{{ $mod['title'] }}</h5>
                        <span class="badge bg-{{ $mod['color'] }}-subtle text-{{ $mod['color'] }} fs-xxs">
                            {{ $mod['stat_label'] }}: {{ $mod['stat_value'] }}
                        </span>
                    </div>
                </div>
                <p class="text-muted fs-sm mb-3">{{ $mod['desc'] }}</p>
                <a href="{{ route($mod['route']) }}" class="btn btn-sm btn-{{ $mod['color'] }} w-100">
                    <i class="ti ti-arrow-right me-1"></i> View Report
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection
