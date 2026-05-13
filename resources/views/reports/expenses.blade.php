@extends('layouts.vertical', ['title' => 'Expenses Report'])

@section('scripts')
<script>
window.currencySymbol = @json($currencySymbol ?? '$');
window.__reportData = {
    trendMonths:  @json($trendMonths),
    trendAmounts: @json($trendAmounts),
    catLabels:    @json($byCategory->pluck(fn($c) => $c->category?->name ?? 'Uncategorized')->values()->all()),
    catTotals:    @json($byCategory->pluck('total')->values()->all()),
    statusLabels: @json($statusBreakdown->pluck('status')->map(fn($s) => ucfirst($s))->values()->all()),
    statusCounts: @json($statusBreakdown->pluck('count')->values()->all()),
    payLabels:    @json(array_keys($paymentMethodDist)),
    payTotals:    @json(array_values($paymentMethodDist)),
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

{{-- Filter Card --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.expenses') }}">
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
                        @foreach(['pending','approved','paid','rejected','cancelled'] as $s)
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
                        @foreach(['cash','card','bank_transfer','online','check'] as $pm)
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
    $pendingCount = $statusBreakdown->firstWhere('status', 'pending');
    $paidCount    = $statusBreakdown->firstWhere('status', 'paid');
    $kpis = [
        ['label' => 'Total Expenses',    'value' => $currencySymbol . number_format($totalExpenses, 2),   'icon' => 'ti-report-money',  'color' => 'danger'],
        ['label' => 'Paid Expenses',     'value' => $currencySymbol . number_format($paidExpenses, 2),    'icon' => 'ti-check-circle',  'color' => 'success'],
        ['label' => 'Pending Approval',  'value' => $currencySymbol . number_format($pendingApproval, 2), 'icon' => 'ti-clock',         'color' => 'warning'],
        ['label' => 'This Month',        'value' => $currencySymbol . number_format($thisMonth, 2),       'icon' => 'ti-calendar',      'color' => 'info'],
        ['label' => 'Pending Count',     'value' => number_format($pendingCount->count ?? 0),              'icon' => 'ti-hourglass',     'color' => 'warning'],
        ['label' => 'Paid Count',        'value' => number_format($paidCount->count ?? 0),                 'icon' => 'ti-checks',        'color' => 'success'],
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
            <div class="card-header border-light"><h5 class="card-title mb-0">Monthly Expense Trend (6 Months)</h5></div>
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
            <div class="card-header border-light"><h5 class="card-title mb-0">Payment Method Distribution</h5></div>
            <div class="card-body"><div id="exp-payment-chart"></div></div>
        </div>
    </div>
</div>

{{-- Tables Row --}}
<div class="row mb-4">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Expenses by Category</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-sm table-nowrap table-hover mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3">Category</th><th class="text-center">Count</th><th class="text-end pe-3">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byCategory as $row)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $row->category?->name ?? 'Uncategorized' }}</td>
                                <td class="text-center"><span class="badge bg-primary-subtle text-primary">{{ $row->count }}</span></td>
                                <td class="text-end pe-3 fw-semibold text-danger">{{ $currencySymbol }}{{ number_format($row->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">No expense data for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Status Summary</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-sm table-nowrap table-hover mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3">Status</th><th class="text-center">Count</th><th class="text-end pe-3">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($statusBreakdown as $row)
                            @php $sc = ['paid'=>'success','pending'=>'warning','approved'=>'info','rejected'=>'danger','cancelled'=>'secondary'][$row->status] ?? 'light' @endphp
                            <tr>
                                <td class="ps-3"><span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($row->status) }}</span></td>
                                <td class="text-center">{{ $row->count }}</td>
                                <td class="text-end pe-3 fw-semibold">{{ $currencySymbol }}{{ number_format($row->total, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-4 text-muted">No expense data for this period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Expenses --}}
<div class="card">
    <div class="card-header border-light">
        <div class="d-flex align-items-center">
            <h5 class="card-title mb-0 flex-grow-1">Recent Expenses</h5>
            <a href="{{ route('reports.expenses') }}" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-centered table-sm table-nowrap table-hover mb-0">
                <thead class="bg-light bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th class="ps-3">Expense #</th><th>Date</th><th>Title</th><th>Category</th>
                        <th>Vendor</th><th class="text-end">Amount</th><th class="text-center pe-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentExpenses as $exp)
                    @php $sc = ['paid'=>'success','pending'=>'warning','approved'=>'info','rejected'=>'danger','cancelled'=>'secondary'][$exp->status] ?? 'light' @endphp
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $exp->expense_number }}</td>
                        <td>{{ $exp->expense_date?->format('d M Y') }}</td>
                        <td>{{ Str::limit($exp->title, 30) }}</td>
                        <td>{{ $exp->category?->name ?? '—' }}</td>
                        <td>{{ $exp->vendor_name ?? '—' }}</td>
                        <td class="text-end fw-semibold text-danger">{{ $currencySymbol }}{{ number_format($exp->total_amount, 2) }}</td>
                        <td class="text-center pe-3"><span class="badge bg-{{ $sc }}-subtle text-{{ $sc }}">{{ ucfirst($exp->status) }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-5 text-muted"><i class="ti ti-receipt-off fs-2 d-block mb-2"></i>No expenses found for this period.</td></tr>
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
