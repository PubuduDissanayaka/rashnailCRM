@extends('layouts.vertical', ['title' => 'Sales & Revenue Report'])

@section('scripts')
<script>
window.currencySymbol = @json($currencySymbol ?? '$');
window.__reportData = {
    trendDates:       @json($trendDates),
    trendTotals:      @json($trendTotals),
    paymentLabels:    @json(array_keys($paymentBreakdown)),
    paymentValues:    @json(array_values($paymentBreakdown)),
    typeLabels:       @json($salesByType->pluck('sale_type')->map(fn($t) => ucfirst(str_replace('_',' ',$t)))->values()->all()),
    typeTotals:       @json($salesByType->pluck('total')->values()->all()),
    serviceNames:     @json($topServices->pluck('item_name')->values()->all()),
    serviceRevenues:  @json($topServices->pluck('revenue')->values()->all()),
};
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('export-btn').addEventListener('click', function() {
        const start = document.querySelector('input[name="start_date"]').value;
        const end = document.querySelector('input[name="end_date"]').value;
        window.location.href = '{{ route("reports.export", ["type" => "sales"]) }}?start_date=' + start + '&end_date=' + end;
    });
});
</script>
@vite(['resources/js/pages/reports-sales.js'])
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Sales & Revenue Report', 'subtitle' => 'Reports'])

{{-- Filter Card --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.sales') }}">
            <div class="row g-3 align-items-end">
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
                        @foreach(['completed','pending','cancelled','refunded'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-medium">Payment Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="">All Methods</option>
                        @foreach(['cash','card','bank_transfer','online','check'] as $pm)
                            <option value="{{ $pm }}" {{ request('payment_method') === $pm ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$pm)) }}</option>
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
                <div class="col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Apply</button>
                    <a href="{{ route('reports.sales') }}" class="btn btn-outline-secondary"><i class="ti ti-refresh me-1"></i>Reset</a>
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
        ['label' => 'Total Revenue',   'value' => $currencySymbol . number_format($totalRevenue, 2),   'icon' => 'ti-cash',          'color' => 'success'],
        ['label' => 'Completed Sales', 'value' => number_format($completedSales),                      'icon' => 'ti-check-circle',  'color' => 'primary'],
        ['label' => 'Avg Order Value', 'value' => $currencySymbol . number_format($avgOrderValue, 2),  'icon' => 'ti-trending-up',   'color' => 'info'],
        ['label' => 'Total Refunds',   'value' => $currencySymbol . number_format($totalRefunds, 2),   'icon' => 'ti-arrow-back-up', 'color' => 'danger'],
        ['label' => 'Tax Collected',   'value' => $currencySymbol . number_format($taxCollected, 2),   'icon' => 'ti-file-invoice',  'color' => 'warning'],
        ['label' => 'Discount Given',  'value' => $currencySymbol . number_format($discountGiven, 2),  'icon' => 'ti-discount',      'color' => 'secondary'],
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
            <div class="card-header border-light"><h5 class="card-title mb-0">Revenue Trend</h5></div>
            <div class="card-body pb-0"><div id="sales-revenue-trend-chart"></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Payment Methods</h5></div>
            <div class="card-body"><div id="sales-payment-method-chart"></div></div>
        </div>
    </div>
</div>

{{-- Charts Row 2 --}}
<div class="row mb-4">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Sales by Type</h5></div>
            <div class="card-body pb-0"><div id="sales-by-type-chart"></div></div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Top Services by Revenue</h5></div>
            <div class="card-body pb-0"><div id="sales-top-services-chart"></div></div>
        </div>
    </div>
</div>

{{-- Tables --}}
<div class="row mb-4">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Revenue by Date</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-centered table-sm table-nowrap table-hover mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs"><th class="ps-3">Date</th><th class="text-center">Sales</th><th class="text-end pe-3">Revenue</th></tr>
                        </thead>
                        <tbody>
                            @forelse($salesByDate as $row)
                            <tr>
                                <td class="ps-3">{{ \Carbon\Carbon::parse($row->date)->format('d M Y') }}</td>
                                <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $row->count }}</span></td>
                                <td class="text-end pe-3 fw-semibold">{{ $currencySymbol }}{{ number_format($row->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">No data for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Top Staff by Revenue</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-centered table-sm table-nowrap table-hover mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs"><th class="ps-3">Staff</th><th class="text-center">Sales</th><th class="text-end pe-3">Revenue</th></tr>
                        </thead>
                        <tbody>
                            @forelse($topStaff as $row)
                            <tr>
                                <td class="ps-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-xs"><span class="avatar-title rounded-circle bg-primary-subtle text-primary fs-xs">{{ strtoupper(substr($row->user?->name ?? 'U', 0, 1)) }}</span></div>
                                        {{ $row->user?->name ?? 'Unknown' }}
                                    </div>
                                </td>
                                <td class="text-center">{{ $row->sale_count }}</td>
                                <td class="text-end pe-3 fw-semibold text-success">{{ $currencySymbol }}{{ number_format($row->revenue, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">No data for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Sales --}}
<div class="card">
    <div class="card-header border-light">
        <div class="d-flex align-items-center">
            <h5 class="card-title mb-0 flex-grow-1">Recent Sales</h5>
            <a href="{{ route('pos.transactions') }}" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-centered table-sm table-nowrap table-hover mb-0">
                <thead class="bg-light bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th class="ps-3">Sale #</th><th>Date</th><th>Customer</th><th>Staff</th>
                        <th class="text-center">Type</th><th class="text-end">Total</th><th class="text-center pe-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentSales as $sale)
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $sale->sale_number }}</td>
                        <td>{{ $sale->sale_date?->format('d M Y, H:i') }}</td>
                        <td>{{ $sale->customer ? $sale->customer->first_name . ' ' . $sale->customer->last_name : 'Walk-in' }}</td>
                        <td>{{ $sale->user?->name ?? '—' }}</td>
                        <td class="text-center"><span class="badge bg-info-subtle text-info">{{ ucfirst(str_replace('_', ' ', $sale->sale_type ?? '')) }}</span></td>
                        <td class="text-end fw-semibold">{{ $currencySymbol }}{{ number_format($sale->total_amount, 2) }}</td>
                        <td class="text-center pe-3">
                            @php $sc = ['completed'=>'success','pending'=>'warning','cancelled'=>'danger','refunded'=>'secondary'][$sale->status] ?? 'light' @endphp
                            <span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($sale->status) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted"><i class="ti ti-receipt-off fs-2 d-block mb-2"></i>No sales found for this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
