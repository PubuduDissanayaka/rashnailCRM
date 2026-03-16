@php
use App\Models\Appointment;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Attendance;
use App\Models\Supply;
use App\Models\User;
use App\Models\Setting;
$currencySymbol = Setting::get('payment.currency_symbol', '$');

// KPI Cards
$todayAppointmentsCount = Appointment::whereDate('appointment_date', today())->count();
$monthlyRevenue = Sale::whereMonth('sale_date', now()->month)
    ->whereYear('sale_date', now()->year)
    ->where('status', 'completed')
    ->sum('total_amount');
$activeCustomersCount = Customer::where('status', 'active')->count();
$pendingExpensesCount = Expense::where('status', 'pending')->count();

// Today's appointments list
$todayAppointments = Appointment::with(['customer', 'service', 'user'])
    ->whereDate('appointment_date', today())
    ->orderBy('appointment_date')
    ->limit(10)
    ->get();

// Appointment status counts for donut chart
$apptStatusCounts = [
    Appointment::whereDate('appointment_date', today())->where('status', 'scheduled')->count(),
    Appointment::whereDate('appointment_date', today())->where('status', 'in_progress')->count(),
    Appointment::whereDate('appointment_date', today())->where('status', 'completed')->count(),
    Appointment::whereDate('appointment_date', today())->where('status', 'cancelled')->count(),
];

// Monthly revenue for last 6 months
$revenueMonths = [];
$revenueData = [];
for ($i = 5; $i >= 0; $i--) {
    $m = now()->subMonths($i);
    $revenueMonths[] = $m->format('M Y');
    $revenueData[] = round(
        Sale::whereMonth('sale_date', $m->month)
            ->whereYear('sale_date', $m->year)
            ->where('status', 'completed')
            ->sum('total_amount'),
        2
    );
}

// Staff attendance today
$todayAttendance = Attendance::with('user')->whereDate('date', today())->get();
$totalActiveStaff = User::where('status', 'active')->count();

// Low stock supplies
$lowStockItems = Supply::where('is_active', true)
    ->whereColumn('current_stock', '<=', 'min_stock_level')
    ->with('category')
    ->limit(6)
    ->get();

// Recent sales
$recentSales = Sale::with(['customer', 'user'])
    ->orderByDesc('created_at')
    ->limit(6)
    ->get();
@endphp

@extends('layouts.vertical', ['title' => 'Dashboard'])

@section('css')
@endsection

@section('content')
    @include('layouts.partials/page-title', ['title' => 'Dashboard'])

    {{-- KPI Stat Cards --}}
    <div class="row row-cols-xxl-4 row-cols-md-2 row-cols-1 g-3 mb-3">
        {{-- Today's Appointments --}}
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="avatar avatar-lg flex-shrink-0">
                            <span class="avatar-title bg-info-subtle text-info rounded-circle fs-24">
                                <i class="ti ti-calendar-check"></i>
                            </span>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-1 fw-semibold">{{ $todayAppointmentsCount }}</h3>
                            <p class="mb-0 text-muted fs-sm">Today's Appointments</p>
                            <a href="{{ route('appointments.index') }}" class="text-info fs-xs">View all →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Monthly Revenue --}}
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="avatar avatar-lg flex-shrink-0">
                            <span class="avatar-title bg-success-subtle text-success rounded-circle fs-24">
                                <i class="ti ti-currency-dollar"></i>
                            </span>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-1 fw-semibold">{{ $currencySymbol }}{{ number_format($monthlyRevenue, 2) }}</h3>
                            <p class="mb-0 text-muted fs-sm">Monthly Revenue</p>
                            <span class="text-muted fs-xs">{{ now()->format('F Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Active Customers --}}
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="avatar avatar-lg flex-shrink-0">
                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-24">
                                <i class="ti ti-users"></i>
                            </span>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-1 fw-semibold">{{ $activeCustomersCount }}</h3>
                            <p class="mb-0 text-muted fs-sm">Active Customers</p>
                            <a href="{{ route('customers.index') }}" class="text-primary fs-xs">Manage →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Pending Expenses --}}
        <div class="col">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="avatar avatar-lg flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-24">
                                <i class="ti ti-clock-exclamation"></i>
                            </span>
                        </div>
                        <div class="text-end">
                            <h3 class="mb-1 fw-semibold">{{ $pendingExpensesCount }}</h3>
                            <p class="mb-0 text-muted fs-sm">Pending Expenses</p>
                            <a href="{{ route('expenses.index') }}" class="text-warning fs-xs">Review →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- end row -->

    {{-- Charts Row --}}
    <div class="row g-3 mb-3">
        {{-- Monthly Revenue Chart --}}
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center border-dashed">
                    <div>
                        <h4 class="card-title mb-0">Revenue Analytics</h4>
                        <p class="text-muted fs-xs mb-0">Monthly revenue for the last 6 months</p>
                    </div>
                    <a href="{{ route('pos.transactions') }}" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-receipt me-1"></i> View Sales
                    </a>
                </div>
                <div class="card-body">
                    <div id="revenue-chart"></div>
                </div>
            </div>
        </div>

        {{-- Appointment Status Donut --}}
        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header border-dashed">
                    <h4 class="card-title mb-0">Today's Appointments</h4>
                    <p class="text-muted fs-xs mb-0">Breakdown by status</p>
                </div>
                <div class="card-body d-flex flex-column justify-content-center">
                    @if(array_sum($apptStatusCounts) > 0)
                        <div id="appointment-status-chart"></div>
                    @else
                        <div class="text-center py-4">
                            <i class="ti ti-calendar-off fs-48 text-muted"></i>
                            <p class="text-muted mt-2 mb-0">No appointments today</p>
                            <a href="{{ route('appointments.create') }}" class="btn btn-sm btn-primary mt-2">
                                <i class="ti ti-plus me-1"></i> Book Appointment
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div><!-- end row -->

    {{-- Today's Appointments Table --}}
    <div class="row g-3 mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center border-dashed">
                    <div>
                        <h4 class="card-title mb-0">
                            Today's Schedule
                            <span class="badge bg-info-subtle text-info ms-2">{{ $todayAppointmentsCount }}</span>
                        </h4>
                    </div>
                    <div class="d-flex gap-2">
                        @can('create appointments')
                        <a href="{{ route('appointments.create') }}" class="btn btn-sm btn-primary">
                            <i class="ti ti-plus me-1"></i> Book
                        </a>
                        @endcan
                        <a href="{{ route('appointments.index') }}" class="btn btn-sm btn-outline-secondary">
                            View All
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    @if($todayAppointments->isEmpty())
                        <div class="text-center py-5">
                            <i class="ti ti-calendar-off fs-48 text-muted"></i>
                            <p class="text-muted mt-2">No appointments scheduled for today.</p>
                            @can('create appointments')
                            <a href="{{ route('appointments.create') }}" class="btn btn-primary btn-sm">
                                <i class="ti ti-plus me-1"></i> Book First Appointment
                            </a>
                            @endcan
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-centered table-custom table-sm table-nowrap table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Customer</th>
                                        <th>Service</th>
                                        <th>Staff</th>
                                        <th>Status</th>
                                        <th style="width:40px;"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($todayAppointments as $appt)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold text-body">
                                                {{ \Carbon\Carbon::parse($appt->appointment_date)->format('h:i A') }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($appt->customer)
                                                <a href="{{ route('customers.show', $appt->customer->id) }}" class="text-body fw-semibold">
                                                    {{ $appt->customer->first_name }} {{ $appt->customer->last_name }}
                                                </a>
                                                <br><span class="text-muted fs-xs">{{ $appt->customer->phone }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($appt->service)
                                                <span class="text-body">{{ $appt->service->name }}</span>
                                                <br><span class="text-muted fs-xs">{{ $appt->service->duration }} min</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($appt->user)
                                                <span class="text-body">{{ $appt->user->name }}</span>
                                            @else
                                                <span class="text-muted">Unassigned</span>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                                $statusMap = [
                                                    'scheduled'   => ['bg-info-subtle text-info', 'Scheduled'],
                                                    'in_progress' => ['bg-warning-subtle text-warning', 'In Progress'],
                                                    'completed'   => ['bg-success-subtle text-success', 'Completed'],
                                                    'cancelled'   => ['bg-danger-subtle text-danger', 'Cancelled'],
                                                ];
                                                [$cls, $label] = $statusMap[$appt->status] ?? ['bg-secondary-subtle text-secondary', ucfirst($appt->status)];
                                            @endphp
                                            <span class="badge {{ $cls }}">{{ $label }}</span>
                                        </td>
                                        <td>
                                            <div class="dropdown">
                                                <a class="dropdown-toggle text-muted drop-arrow-none card-drop p-0"
                                                    data-bs-toggle="dropdown" href="#">
                                                    <i class="ti ti-dots-vertical fs-lg"></i>
                                                </a>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="{{ route('appointments.show', $appt->id) }}">
                                                        <i class="ti ti-eye me-1"></i> View
                                                    </a>
                                                    <a class="dropdown-item" href="{{ route('appointments.edit', $appt->id) }}">
                                                        <i class="ti ti-edit me-1"></i> Edit
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div><!-- end row -->

    {{-- Staff Attendance + Low Stock --}}
    <div class="row g-3 mb-3">
        {{-- Staff Attendance --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center border-dashed">
                    <div>
                        <h4 class="card-title mb-0">Staff Attendance Today</h4>
                        <p class="text-muted fs-xs mb-0">
                            {{ $todayAttendance->count() }} of {{ $totalActiveStaff }} staff checked in
                        </p>
                    </div>
                    @can('view attendances')
                    <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                    @endcan
                </div>
                <div class="card-body p-0">
                    @if($todayAttendance->isEmpty())
                        <div class="text-center py-5">
                            <i class="ti ti-user-x fs-48 text-muted"></i>
                            <p class="text-muted mt-2">No staff attendance recorded yet today.</p>
                        </div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($todayAttendance as $att)
                            <li class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar avatar-sm">
                                            <span class="avatar-title bg-primary-subtle text-primary rounded-circle fs-14">
                                                {{ strtoupper(substr($att->user->name ?? 'U', 0, 1)) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="fw-semibold text-body fs-sm">{{ $att->user->name ?? 'Unknown' }}</span>
                                            <br>
                                            <span class="text-muted fs-xs">
                                                In: {{ $att->check_in ? \Carbon\Carbon::parse($att->check_in)->format('h:i A') : '—' }}
                                                @if($att->check_out)
                                                    · Out: {{ \Carbon\Carbon::parse($att->check_out)->format('h:i A') }}
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                    @php
                                        $attStatusMap = [
                                            'present'  => 'bg-success-subtle text-success',
                                            'late'     => 'bg-warning-subtle text-warning',
                                            'absent'   => 'bg-danger-subtle text-danger',
                                            'leave'    => 'bg-info-subtle text-info',
                                            'half_day' => 'bg-secondary-subtle text-secondary',
                                        ];
                                        $attCls = $attStatusMap[$att->status] ?? 'bg-secondary-subtle text-secondary';
                                    @endphp
                                    <span class="badge {{ $attCls }}">{{ ucfirst(str_replace('_', ' ', $att->status)) }}</span>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>

        {{-- Low Stock Alerts --}}
        <div class="col-xl-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center border-dashed">
                    <div>
                        <h4 class="card-title mb-0">Low Stock Alerts</h4>
                        <p class="text-muted fs-xs mb-0">Supplies at or below minimum level</p>
                    </div>
                    @can('inventory.view')
                    <a href="{{ route('inventory.supplies.index') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                    @endcan
                </div>
                <div class="card-body p-0">
                    @if($lowStockItems->isEmpty())
                        <div class="text-center py-5">
                            <i class="ti ti-package-check fs-48 text-success"></i>
                            <p class="text-success mt-2 mb-0 fw-semibold">All supplies are well stocked!</p>
                        </div>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach($lowStockItems as $supply)
                            <li class="list-group-item px-3 py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <span class="fw-semibold text-body fs-sm">{{ $supply->name }}</span>
                                        <br>
                                        <span class="text-muted fs-xs">{{ $supply->category->name ?? 'Uncategorized' }}</span>
                                    </div>
                                    <div class="text-end">
                                        @if($supply->current_stock <= 0)
                                            <span class="badge bg-danger-subtle text-danger">Out of Stock</span>
                                        @else
                                            <span class="badge bg-warning-subtle text-warning">
                                                {{ $supply->current_stock }} {{ $supply->unit_type }} left
                                            </span>
                                        @endif
                                        <br>
                                        <span class="text-muted fs-xs">Min: {{ $supply->min_stock_level }}</span>
                                    </div>
                                </div>
                            </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div><!-- end row -->

    {{-- Recent Sales --}}
    <div class="row g-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center border-dashed">
                    <h4 class="card-title mb-0">Recent Sales</h4>
                    <a href="{{ route('pos.transactions') }}" class="btn btn-sm btn-outline-secondary">View All</a>
                </div>
                <div class="card-body p-0">
                    @if($recentSales->isEmpty())
                        <div class="text-center py-5">
                            <i class="ti ti-receipt-off fs-48 text-muted"></i>
                            <p class="text-muted mt-2">No sales recorded yet.</p>
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-centered table-custom table-sm table-nowrap table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Sale #</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Payment</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($recentSales as $sale)
                                    <tr>
                                        <td>
                                            <span class="fw-semibold text-body">{{ $sale->sale_number }}</span>
                                        </td>
                                        <td>
                                            @if($sale->customer)
                                                <a href="{{ route('customers.show', $sale->customer->id) }}" class="text-body">
                                                    {{ $sale->customer->first_name }} {{ $sale->customer->last_name }}
                                                </a>
                                            @else
                                                <span class="text-muted">Walk-in</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fw-semibold">{{ $currencySymbol }}{{ number_format($sale->total_amount, 2) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                {{ ucfirst(str_replace('_', ' ', $sale->payments->first()?->payment_method ?? '—')) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-muted">
                                                {{ \Carbon\Carbon::parse($sale->sale_date)->format('M d, Y') }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $saleStatusMap = [
                                                    'completed' => 'bg-success-subtle text-success',
                                                    'pending'   => 'bg-warning-subtle text-warning',
                                                    'cancelled' => 'bg-danger-subtle text-danger',
                                                    'refunded'  => 'bg-secondary-subtle text-secondary',
                                                ];
                                                $sCls = $saleStatusMap[$sale->status] ?? 'bg-secondary-subtle text-secondary';
                                            @endphp
                                            <span class="badge {{ $sCls }}">{{ ucfirst($sale->status) }}</span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div><!-- end row -->

@endsection

@section('scripts')
<script>
window.__dashboardData = {
    revenueMonths: @json($revenueMonths),
    revenueData: @json($revenueData),
    apptStatusCounts: @json($apptStatusCounts),
    hasAppts: {{ array_sum($apptStatusCounts) > 0 ? 'true' : 'false' }},
};
</script>
@vite(['resources/js/pages/dashboard.js'])
@endsection
