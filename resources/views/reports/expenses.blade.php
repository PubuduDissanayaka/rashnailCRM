@extends('layouts.vertical', ['title' => 'Expenses Report'])

@section('scripts')
<script>
window.currencySymbol = @json($currencySymbol ?? '$');
window.__reportData = {
    trendMonths:     @json($trendMonths),
    trendAmounts:    @json($trendAmounts),
    catLabels:       @json($byCategory->map(fn($r) => $r->category?->name ?? 'Uncategorised')->values()->all()),
    catTotals:       @json($byCategory->pluck('total')->values()->all()),
    statusLabels:    @json($statusBreakdown->pluck('status')->map(fn($s) => ucfirst($s))->values()->all()),
    statusCounts:    @json($statusBreakdown->pluck('count')->values()->all()),
    statusTotals:    @json($statusBreakdown->pluck('total')->values()->all()),
    payLabels:       @json(array_keys($paymentMethodDist)),
    payTotals:       @json(array_values($paymentMethodDist)),
};
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('export-btn').addEventListener('click', function() {
        const start = document.querySelector('input[name="start_date"]').value;
        const end = document.querySelector('input[name="end_date"]').value;
        window.location.href = '{{ route("reports.export", ["type" => "expenses"]) }}?start_date=' + start + '&end_date=' + end;
    });
});
</script>
@vite(['resources/js/pages/reports-expenses.js'])
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Expenses Report', 'subtitle' => 'Reports'])

{{-- Filter --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.expenses') }}">
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
                        @foreach(['draft','pending','approved','rejected','paid'] as $s)
                            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-medium">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label fw-medium">Payment Method</label>
                    <select name="payment_method" class="form-select">
                        <option value="">All Methods</option>
                        @foreach(['cash','card','check','bank_transfer','online'] as $pm)
                            <option value="{{ $pm }}" {{ request('payment_method') === $pm ? 'selected' : '' }}>{{ ucwords(str_replace('_',' ',$pm)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Apply</button>
                    <a href="{{ route('reports.expenses') }}" class="btn btn-outline-secondary"><i class="ti ti-refresh me-1"></i>Reset</a>
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
        ['label' => 'Total Expenses',    'value' => $currencySymbol . number_format($totalExpenses, 2),   'icon' => 'ti-receipt',       'color' => 'primary'],
        ['label' => 'Paid',              'value' => $currencySymbol . number_format($paidExpenses, 2),    'icon' => 'ti-circle-check',  'color' => 'success'],
        ['label' => 'Pending Approval',  'value' => $currencySymbol . number_format($pendingApproval, 2), 'icon' => 'ti-clock-hour-3',  'color' => 'warning'],
        ['label' => 'This Month',        'value' => $currencySymbol . number_format($thisMonth, 2),       'icon' => 'ti-calendar-month', 'color' => 'info'],
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
            <div class="card-header border-light"><h5 class="card-title mb-0">Monthly Expense Trend</h5></div>
            <div class="card-body pb-0"><div id="exp-monthly-trend-chart"></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">By Category</h5></div>
            <div class="card-body"><div id="exp-category-chart"></div></div>
        </div>
    </div>
</div>

{{-- Charts Row 2 --}}
<div class="row mb-4">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Status Breakdown</h5></div>
            <div class="card-body pb-0"><div id="exp-status-chart"></div></div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Payment Methods</h5></div>
            <div class="card-body"><div id="exp-payment-chart"></div></div>
        </div>
    </div>
</div>

{{-- Tables --}}
<div class="row">
    <div class="col-xl-4">
        <div class="card">
            <div class="card-header border-light"><h5 class="card-title mb-0">Expenses by Category</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-centered table-sm table-nowrap table-hover mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs"><th class="ps-3">Category</th><th class="text-center">Count</th><th class="text-end pe-3">Total</th></tr>
                        </thead>
                        <tbody>
                            @forelse($byCategory as $row)
                            <tr>
                                <td class="ps-3">{{ $row->category?->name ?? 'Uncategorised' }}</td>
                                <td class="text-center"><span class="badge bg-info-subtle text-info">{{ $row->count }}</span></td>
                                <td class="text-end pe-3 fw-semibold">{{ $currencySymbol }}{{ number_format($row->total, 2) }}</td>
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
    <div class="col-xl-8">
        <div class="card">
            <div class="card-header border-light"><h5 class="card-title mb-0">Recent Expenses</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-centered table-sm table-nowrap table-hover mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3">Expense #</th><th>Date</th><th>Title</th>
                                <th>Category</th><th class="text-end">Amount</th><th class="text-center pe-3">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentExpenses as $exp)
                            @php $ec = ['paid'=>'success','approved'=>'info','pending'=>'warning','rejected'=>'danger','draft'=>'secondary'][$exp->status] ?? 'light'; @endphp
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $exp->expense_number }}</td>
                                <td>{{ $exp->expense_date?->format('d M Y') }}</td>
                                <td>{{ \Str::limit($exp->title, 30) }}</td>
                                <td>{{ $exp->category?->name ?? '—' }}</td>
                                <td class="text-end">{{ $currencySymbol }}{{ number_format($exp->total_amount, 2) }}</td>
                                <td class="text-center pe-3"><span class="badge bg-{{ $ec }}-subtle text-{{ $ec }}">{{ ucfirst($exp->status) }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No expenses for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
