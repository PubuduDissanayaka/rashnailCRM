@extends('layouts.vertical', ['title' => 'Inventory Report'])

@section('scripts')
<script>
window.currencySymbol = @json($currencySymbol ?? '$');
window.__reportData = {
    catNames:      @json($stockByCategory->map(fn($r) => $r->category?->name ?? 'Uncategorised')->values()->all()),
    catStock:      @json($stockByCategory->pluck('total_stock')->values()->all()),
    valCatNames:   @json($stockValueByCategory->map(fn($r) => $r->category?->name ?? 'Uncategorised')->values()->all()),
    valCatValues:  @json($stockValueByCategory->pluck('value')->values()->all()),
    usedNames:     @json($topUsed->map(fn($r) => $r->supply?->name ?? 'Unknown')->values()->all()),
    usedQty:       @json($topUsed->pluck('total_used')->values()->all()),
};
</script>
@vite(['resources/js/pages/reports-inventory.js'])
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Inventory Report', 'subtitle' => 'Reports'])

{{-- Filter --}}
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.inventory') }}">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label fw-medium">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-medium">Stock Status</label>
                    <select name="stock_status" class="form-select">
                        <option value="all" {{ $stockStatus === 'all' ? 'selected' : '' }}>All Items</option>
                        <option value="low" {{ $stockStatus === 'low' ? 'selected' : '' }}>Low Stock Only</option>
                        <option value="out" {{ $stockStatus === 'out' ? 'selected' : '' }}>Out of Stock Only</option>
                    </select>
                </div>
                <div class="col-md-auto d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="ti ti-filter me-1"></i>Apply</button>
                    <a href="{{ route('reports.inventory') }}" class="btn btn-outline-secondary"><i class="ti ti-refresh me-1"></i>Reset</a>
                    <a href="{{ route('reports.export', ['type' => 'inventory']) }}" class="btn btn-success"><i class="ti ti-download me-1"></i>Export CSV</a>
                </div>
            </div>
        </form>
    </div>
</div>

{{-- KPI Cards --}}
<div class="row g-3 mb-4">
    @php
    $kpis = [
        ['label' => 'Total Supplies',    'value' => number_format($totalSupplies),                         'icon' => 'ti-package',       'color' => 'primary'],
        ['label' => 'Low Stock Items',   'value' => number_format($lowStockCount),                         'icon' => 'ti-alert-triangle', 'color' => 'warning'],
        ['label' => 'Out of Stock',      'value' => number_format($outOfStockCount),                       'icon' => 'ti-package-off',   'color' => 'danger'],
        ['label' => 'Total Stock Value', 'value' => $currencySymbol . number_format($totalStockValue, 2),  'icon' => 'ti-coin',          'color' => 'success'],
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

{{-- Charts --}}
<div class="row mb-4">
    <div class="col-xl-7">
        <div class="card h-100">
            <div class="card-header border-light"><h5 class="card-title mb-0">Stock Levels by Category</h5></div>
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

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-light">
                <h5 class="card-title mb-0">Top Used Supplies</h5>
                <p class="text-muted mb-0 fs-sm">Last 90 days</p>
            </div>
            <div class="card-body pb-0"><div id="inv-top-used-chart"></div></div>
        </div>
    </div>
</div>

{{-- Supplies Table --}}
<div class="card mb-4">
    <div class="card-header border-light">
        <div class="d-flex align-items-center">
            <h5 class="card-title mb-0 flex-grow-1">All Supplies</h5>
            @if($lowStockCount > 0)
            <span class="badge bg-warning-subtle text-warning me-2"><i class="ti ti-alert-triangle me-1"></i>{{ $lowStockCount }} low stock</span>
            @endif
            @if($outOfStockCount > 0)
            <span class="badge bg-danger-subtle text-danger"><i class="ti ti-package-off me-1"></i>{{ $outOfStockCount }} out of stock</span>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-centered table-sm table-nowrap table-hover mb-0">
                <thead class="bg-light bg-opacity-25 thead-sm">
                    <tr class="text-uppercase fs-xxs">
                        <th class="ps-3">Name</th><th>SKU</th><th>Category</th><th>Unit</th>
                        <th class="text-center">Current Stock</th><th class="text-center">Min Level</th>
                        <th class="text-end">Unit Cost</th><th class="text-end">Stock Value</th><th class="text-center pe-3">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($supplies as $supply)
                    @php
                        $isOut = $supply->isOutOfStock();
                        $isLow = !$isOut && $supply->isLowStock();
                        $statusColor = $isOut ? 'danger' : ($isLow ? 'warning' : 'success');
                        $statusLabel = $isOut ? 'Out of Stock' : ($isLow ? 'Low Stock' : 'OK');
                    @endphp
                    <tr>
                        <td class="ps-3 fw-semibold">{{ $supply->name }}</td>
                        <td class="text-muted">{{ $supply->sku }}</td>
                        <td>{{ $supply->category?->name ?? '—' }}</td>
                        <td>{{ $supply->unit_type }}</td>
                        <td class="text-center fw-semibold {{ $isOut ? 'text-danger' : ($isLow ? 'text-warning' : '') }}">{{ number_format($supply->current_stock, 1) }}</td>
                        <td class="text-center text-muted">{{ number_format($supply->min_stock_level, 1) }}</td>
                        <td class="text-end">{{ $currencySymbol }}{{ number_format($supply->unit_cost, 2) }}</td>
                        <td class="text-end fw-semibold">{{ $currencySymbol }}{{ number_format($supply->current_stock * $supply->unit_cost, 2) }}</td>
                        <td class="text-center pe-3">
                            <span class="badge bg-{{ $statusColor }}-subtle text-{{ $statusColor }}">{{ $statusLabel }}</span>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="9" class="text-center py-5 text-muted"><i class="ti ti-package-off fs-2 d-block mb-2"></i>No supplies found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Low Stock + Recent Usage --}}
<div class="row">
    <div class="col-xl-5">
        <div class="card">
            <div class="card-header border-light border-warning-subtle">
                <h5 class="card-title mb-0 text-warning"><i class="ti ti-alert-triangle me-1"></i>Low Stock Alerts</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-centered table-sm table-nowrap table-hover mb-0">
                        <thead class="bg-warning bg-opacity-10 thead-sm">
                            <tr class="text-uppercase fs-xxs"><th class="ps-3">Supply</th><th class="text-center">Current</th><th class="text-center pe-3">Min Level</th></tr>
                        </thead>
                        <tbody>
                            @forelse($lowStockItems as $item)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold">{{ $item->name }}</div>
                                    <small class="text-muted">{{ $item->category?->name }} · {{ $item->unit_type }}</small>
                                </td>
                                <td class="text-center fw-semibold text-{{ $item->isOutOfStock() ? 'danger' : 'warning' }}">{{ number_format($item->current_stock, 1) }}</td>
                                <td class="text-center pe-3 text-muted">{{ number_format($item->min_stock_level, 1) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center py-4 text-success"><i class="ti ti-circle-check me-1"></i>All stock levels are healthy!</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header border-light"><h5 class="card-title mb-0">Recent Supply Usage</h5></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-centered table-sm table-nowrap table-hover mb-0">
                        <thead class="bg-light bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3">Supply</th><th>Used By</th><th class="text-center">Qty Used</th><th class="text-end pe-3">Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentUsage as $log)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-semibold">{{ $log->supply?->name ?? '—' }}</div>
                                    <small class="text-muted">{{ $log->used_at?->format('d M Y, H:i') }}</small>
                                </td>
                                <td>{{ $log->user?->name ?? '—' }}</td>
                                <td class="text-center"><span class="badge bg-info-subtle text-info">{{ number_format($log->quantity_used, 2) }}</span></td>
                                <td class="text-end pe-3">{{ $currencySymbol }}{{ number_format($log->total_cost, 2) }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">No usage recorded recently.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
