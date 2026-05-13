@extends('layouts.vertical', ['title' => 'Inventory Report'])

@section('scripts')
<script>
window.currencySymbol = @json($currencySymbol ?? '$');
window.__reportData = {
    catNames:     @json($stockByCategory->pluck(fn($c) => $c->category?->name ?? 'Uncategorized')->values()->all()),
    catStock:     @json($stockByCategory->pluck('total_stock')->values()->all()),
    valCatNames:  @json($stockValueByCategory->pluck(fn($c) => $c->category?->name ?? 'Uncategorized')->values()->all()),
    valCatValues: @json($stockValueByCategory->pluck('value')->values()->all()),
    usedNames:    @json($topUsed->pluck(fn($u) => $u->supply?->name ?? 'Unknown')->values()->all()),
    usedQty:      @json($topUsed->pluck('total_used')->values()->all()),
};
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('export-btn').addEventListener('click', function() {
        window.location.href = '{{ route("reports.export", ["type" => "inventory"]) }}?start_date=&end_date=';
    });
});
</script>
@vite(['resources/js/pages/reports-inventory.js'])
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Inventory Report', 'subtitle' => 'Reports'])

{{-- Filter Card --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.inventory') }}">
            <div class="row g-3 align-items-end">
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
                    <label class="form-label fw-medium">Stock Status</label>
                    <select name="stock_status" class="form-select">
                        <option value="all" {{ $stockStatus === 'all' ? 'selected' : '' }}>All Items</option>
                        <option value="low" {{ $stockStatus === 'low' ? 'selected' : '' }}>Low Stock</option>
                        <option value="out" {{ $stockStatus === 'out' ? 'selected' : '' }}>Out of Stock</option>
                    </select>
                </div>
                <div class="col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Apply</button>
                    <a href="{{ route('reports.inventory') }}" class="btn btn-outline-secondary"><i class="ti ti-refresh me-1"></i>Reset</a>
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
        ['label' => 'Total Supplies',    'value' => number_format($totalSupplies),                         'icon' => 'ti-packages',          'color' => 'primary'],
        ['label' => 'Low Stock',         'value' => number_format($lowStockCount),                          'icon' => 'ti-alert-triangle',    'color' => 'warning'],
        ['label' => 'Out of Stock',      'value' => number_format($outOfStockCount),                        'icon' => 'ti-x-circle',          'color' => 'danger'],
        ['label' => 'Stock Value',       'value' => $currencySymbol . number_format($totalStockValue, 2),   'icon' => 'ti-coin',             'color' => 'success'],
        ['label' => 'Healthy Stock',     'value' => number_format($totalSupplies - $lowStockCount - $outOfStockCount), 'icon' => 'ti-check-circle', 'color' => 'info'],
        ['label' => 'Categories',        'value' => number_format($categories->count()),                    'icon' => 'ti-category',          'color' => 'secondary'],
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
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Stock Units by Category</h5></div>
            <div class="card-body pb-0"><div id="inv-stock-category-chart"></div></div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Stock Value Distribution</h5></div>
            <div class="card-body"><div id="inv-value-donut-chart"></div></div>
        </div>
    </div>
</div>

{{-- Charts Row 2: Top Used Supplies --}}
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-light"><h5 class="card-title mb-0">Top Used Supplies (Last 90 Days)</h5></div>
            <div class="card-body pb-0"><div id="inv-top-used-chart"></div></div>
        </div>
    </div>
</div>

{{-- Alerts: Low Stock Items --}}
@if($lowStockItems->isNotEmpty())
<div class="card mb-4 border-danger">
    <div class="card-header border-danger bg-danger-subtle">
        <div class="d-flex align-items-center">
            <h5 class="card-title mb-0 flex-grow-1 text-danger"><i class="ti ti-alert-triangle me-1"></i>Low Stock Alerts</h5>
            <span class="badge bg-danger">{{ $lowStockItems->count() }} items</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-centered table-sm table-nowrap table-hover mb-0">
                <thead class="bg-light bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th class="ps-3">Item</th><th>SKU</th><th>Category</th>
                        <th class="text-center">Current Stock</th><th class="text-center">Min Level</th>
                        <th class="text-end">Unit Cost</th><th class="text-center pe-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lowStockItems as $item)
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $item->name }}</td>
                        <td class="text-muted fs-sm">{{ $item->sku ?? '—' }}</td>
                        <td>{{ $item->category?->name ?? '—' }}</td>
                        <td class="text-center fw-semibold text-danger">{{ $item->current_stock }}</td>
                        <td class="text-center">{{ $item->min_stock_level }}</td>
                        <td class="text-end">{{ $currencySymbol }}{{ number_format($item->unit_cost, 2) }}</td>
                        <td class="text-center pe-3">
                            @if($item->isOutOfStock())
                                <span class="badge bg-danger-subtle text-danger">Out of Stock</span>
                            @else
                                <span class="badge bg-warning-subtle text-warning">Low Stock</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- Full Inventory Table --}}
<div class="card mb-4">
    <div class="card-header border-light">
        <div class="d-flex align-items-center">
            <h5 class="card-title mb-0 flex-grow-1">Inventory Items</h5>
            <span class="badge bg-primary-subtle text-primary">{{ $supplies->count() }} items</span>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-centered table-sm table-nowrap table-hover mb-0">
                <thead class="bg-light bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th class="ps-3">Item</th><th>SKU</th><th>Category</th>
                        <th class="text-center">Current Stock</th><th class="text-center">Min</th><th class="text-center">Max</th>
                        <th class="text-end">Unit Cost</th><th class="text-end">Stock Value</th><th class="text-center pe-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supplies as $item)
                    @php
                        $stockValue = $item->current_stock * $item->unit_cost;
                        if ($item->isOutOfStock()) {
                            $statusBadge = 'bg-danger-subtle text-danger';
                            $statusText  = 'Out of Stock';
                        } elseif ($item->isLowStock()) {
                            $statusBadge = 'bg-warning-subtle text-warning';
                            $statusText  = 'Low Stock';
                        } else {
                            $statusBadge = 'bg-success-subtle text-success';
                            $statusText  = 'In Stock';
                        }
                    @endphp
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $item->name }}</td>
                        <td class="text-muted fs-sm">{{ $item->sku ?? '—' }}</td>
                        <td>{{ $item->category?->name ?? '—' }}</td>
                        <td class="text-center fw-semibold {{ $item->isOutOfStock() || $item->isLowStock() ? 'text-danger' : '' }}">{{ $item->current_stock }}</td>
                        <td class="text-center">{{ $item->min_stock_level }}</td>
                        <td class="text-center">{{ $item->max_stock_level }}</td>
                        <td class="text-end">{{ $currencySymbol }}{{ number_format($item->unit_cost, 2) }}</td>
                        <td class="text-end fw-semibold">{{ $currencySymbol }}{{ number_format($stockValue, 2) }}</td>
                        <td class="text-center pe-3"><span class="badge {{ $statusBadge }}">{{ $statusText }}</span></td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-5 text-muted"><i class="ti ti-package-off fs-2 d-block mb-2"></i>No inventory items found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Recent Usage Log --}}
<div class="card">
    <div class="card-header border-light">
        <div class="d-flex align-items-center">
            <h5 class="card-title mb-0 flex-grow-1">Recent Supply Usage</h5>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-custom table-centered table-sm table-nowrap table-hover mb-0">
                <thead class="bg-light bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th class="ps-3">Date</th><th>Supply</th><th>User</th>
                        <th class="text-center">Qty Used</th><th class="text-end">Cost</th><th class="text-center pe-3">Type</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentUsage as $log)
                    <tr>
                        <td class="ps-3">{{ $log->used_at?->format('d M Y, H:i') }}</td>
                        <td class="fw-semibold">{{ $log->supply?->name ?? 'Unknown' }}</td>
                        <td>{{ $log->user?->name ?? 'System' }}</td>
                        <td class="text-center">{{ $log->quantity_used }} {{ $log->supply?->unit_type ?? '' }}</td>
                        <td class="text-end fw-semibold">{{ $currencySymbol }}{{ number_format($log->total_cost ?? 0, 2) }}</td>
                        <td class="text-center pe-3">
                            @php
                                $usageType = $log->usage_type ?? 'manual';
                                $utColor = ['service'=>'info', 'adjustment'=>'warning', 'manual'=>'secondary', 'order'=>'primary'][$usageType] ?? 'secondary';
                            @endphp
                            <span class="badge bg-{{ $utColor }}-subtle text-{{ $utColor }}">{{ ucfirst($usageType) }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">No usage history found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
