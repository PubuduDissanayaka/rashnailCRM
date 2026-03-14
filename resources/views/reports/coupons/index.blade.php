@extends('layouts.vertical', ['title' => 'Coupon Reports'])

@section('css')
    @vite(['node_modules/daterangepicker/daterangepicker.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
    <style>
        .summary-card {
            border-radius: 10px;
            border: none;
            transition: transform 0.2s;
        }
        
        .summary-card:hover {
            transform: translateY(-2px);
        }
        
        .filter-card {
            background: #f8f9fa;
            border-left: 4px solid #3b76e1;
        }
        
        .data-table th {
            background-color: #f8f9fa;
            font-weight: 600;
            border-top: none;
        }
        
        .export-btn-group .btn {
            border-radius: 5px;
        }
        
        .date-range-picker {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 8px 12px;
            cursor: pointer;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        
        .badge-coupon-active {
            background-color: #e7f7ef;
            color: #28a745;
        }
        
        .badge-coupon-expired {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        
        .badge-coupon-redeemed {
            background-color: #e7f1ff;
            color: #3b76e1;
        }
    </style>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Coupon Reports', 'subtitle' => 'Comprehensive coupon analytics and performance metrics'])

    <div class="row">
        <div class="col-12">
            <!-- Filter Card -->
            <div class="card filter-card mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-3">Report Filters</h5>
                    <form id="report-filters-form" method="GET" action="{{ route('reports.coupons.index') }}">
                        <div class="row g-3">
                            <!-- Date Range -->
                            <div class="col-md-3">
                                <label class="form-label">Date Range</label>
                                <div class="date-range-picker" id="date-range-picker">
                                    <i class="ti ti-calendar me-2"></i>
                                    <span id="date-range-text">
                                        {{ $filters->startDate->format('M d, Y') }} - {{ $filters->endDate->format('M d, Y') }}
                                    </span>
                                    <input type="hidden" name="start_date" id="start-date" value="{{ $filters->startDate->format('Y-m-d') }}">
                                    <input type="hidden" name="end_date" id="end-date" value="{{ $filters->endDate->format('Y-m-d') }}">
                                </div>
                            </div>
                            
                            <!-- Export Buttons -->
                            <div class="col-md-9 d-flex align-items-end justify-content-end export-btn-group">
                                <div class="btn-group">
                                    <button type="button" class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="ti ti-download me-2"></i> Export Report
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('reports.coupons.export', ['type' => 'redemptions']) }}?start_date={{ $filters->startDate->format('Y-m-d') }}&end_date={{ $filters->endDate->format('Y-m-d') }}">Redemption Analytics (CSV)</a></li>
                                        <li><a class="dropdown-item" href="{{ route('reports.coupons.export', ['type' => 'performance']) }}?start_date={{ $filters->startDate->format('Y-m-d') }}&end_date={{ $filters->endDate->format('Y-m-d') }}">Performance by Type (CSV)</a></li>
                                        <li><a class="dropdown-item" href="{{ route('reports.coupons.export', ['type' => 'usage']) }}?start_date={{ $filters->startDate->format('Y-m-d') }}&end_date={{ $filters->endDate->format('Y-m-d') }}">Usage by Period (CSV)</a></li>
                                        <li><a class="dropdown-item" href="{{ route('reports.coupons.export', ['type' => 'top-coupons']) }}?start_date={{ $filters->startDate->format('Y-m-d') }}&end_date={{ $filters->endDate->format('Y-m-d') }}">Top Coupons (CSV)</a></li>
                                        <li><a class="dropdown-item" href="{{ route('reports.coupons.export', ['type' => 'locations']) }}?start_date={{ $filters->startDate->format('Y-m-d') }}&end_date={{ $filters->endDate->format('Y-m-d') }}">Redemption by Location (CSV)</a></li>
                                        <li><a class="dropdown-item" href="{{ route('reports.coupons.export', ['type' => 'customer-groups']) }}?start_date={{ $filters->startDate->format('Y-m-d') }}&end_date={{ $filters->endDate->format('Y-m-d') }}">Redemption by Customer Group (CSV)</a></li>
                                    </ul>
                                </div>
                                <button type="submit" class="btn btn-primary ms-2">
                                    <i class="ti ti-refresh me-2"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Statistics -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card summary-card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Coupons</h6>
                            <h3 class="mb-0">{{ $summary['total_coupons'] }}</h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-primary rounded-circle">
                                <i class="ti ti-ticket fs-4"></i>
                            </span>
                        </div>
                    </div>
                    <p class="text-white-75 mt-3 mb-0">
                        <span class="badge bg-light text-primary me-1">{{ $summary['active_coupons'] }} active</span>
                        <span class="badge bg-light text-primary">{{ $summary['redeemed_coupons'] }} redeemed</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card summary-card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Total Discount</h6>
                            <h3 class="mb-0">{{ $currencySymbol }}{{ number_format($summary['total_discount'], 2) }}</h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-success rounded-circle">
                                <i class="ti ti-discount fs-4"></i>
                            </span>
                        </div>
                    </div>
                    <p class="text-white-75 mt-3 mb-0">
                        <span class="badge bg-light text-success me-1">Avg {{ $currencySymbol }}{{ number_format($summary['avg_discount_per_redemption'], 2) }}</span>
                        <span class="badge bg-light text-success">{{ $summary['total_sales_with_coupons'] }} sales</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card summary-card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Redemption Rate</h6>
                            <h3 class="mb-0">{{ number_format($summary['redemption_rate'], 1) }}%</h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-info rounded-circle">
                                <i class="ti ti-chart-pie fs-4"></i>
                            </span>
                        </div>
                    </div>
                    <p class="text-white-75 mt-3 mb-0">
                        <span class="badge bg-light text-info me-1">{{ $summary['redeemed_coupons'] }} redemptions</span>
                        <span class="badge bg-light text-info">{{ $summary['total_coupons'] }} total</span>
                    </p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card summary-card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50 mb-1">Revenue Impact</h6>
                            <h3 class="mb-0">{{ $currencySymbol }}{{ number_format($summary['total_revenue_impact'], 2) }}</h3>
                        </div>
                        <div class="avatar-sm">
                            <span class="avatar-title bg-light text-warning rounded-circle">
                                <i class="ti ti-currency-dollar fs-4"></i>
                            </span>
                        </div>
                    </div>
                    <p class="text-white-75 mt-3 mb-0">
                        <span class="badge bg-light text-warning me-1">Total discount applied</span>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row mb-4">
        <!-- Redemption Analytics Chart -->
        <div class="col-xl-8">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Redemption Analytics</h5>
                    <p class="text-muted">Daily coupon redemptions and discount amount</p>
                    <div class="chart-container">
                        <canvas id="redemptionChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <!-- Performance by Type Chart -->
        <div class="col-xl-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Performance by Coupon Type</h5>
                    <p class="text-muted">Redemptions and discount by coupon type</p>
                    <div class="chart-container">
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Performing Coupons -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Top Performing Coupons</h5>
                    <p class="text-muted">Coupons with highest redemptions</p>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th class="text-end">Redemptions</th>
                                    <th class="text-end">Total Discount</th>
                                    <th class="text-end">Avg Discount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topCoupons as $coupon)
                                    <tr>
                                        <td><strong>{{ $coupon->code }}</strong></td>
                                        <td>{{ $coupon->name }}</td>
                                        <td><span class="badge bg-light text-dark">{{ ucfirst($coupon->type) }}</span></td>
                                        <td>
                                            @if($coupon->status == 'active')
                                                <span class="badge badge-coupon-active">Active</span>
                                            @elseif($coupon->status == 'expired')
                                                <span class="badge badge-coupon-expired">Expired</span>
                                            @elseif($coupon->status == 'redeemed')
                                                <span class="badge badge-coupon-redeemed">Redeemed</span>
                                            @else
                                                <span class="badge bg-secondary">{{ $coupon->status }}</span>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ $coupon->redemption_count ?? 0 }}</td>
                                        <td class="text-end">{{ $currencySymbol }}{{ number_format($coupon->total_discount_given ?? 0, 2) }}</td>
                                        <td class="text-end">{{ $currencySymbol }}{{ number_format($coupon->avg_discount_per_redemption ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">No coupon redemptions in selected period.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Breakdown Tables -->
    <div class="row">
        <!-- Redemption by Location -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Redemption by Location</h5>
                    <p class="text-muted">Coupon usage across locations</p>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Location</th>
                                    <th class="text-end">Redemptions</th>
                                    <th class="text-end">Total Discount</th>
                                    <th class="text-end">Unique Coupons</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($redemptionByLocation as $location)
                                    <tr>
                                        <td>{{ $location->name ?? 'Unknown' }}</td>
                                        <td class="text-end">{{ $location->redemptions }}</td>
                                        <td class="text-end">{{ $currencySymbol }}{{ number_format($location->total_discount, 2) }}</td>
                                        <td class="text-end">{{ $location->unique_coupons }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No location data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!-- Redemption by Customer Group -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Redemption by Customer Group</h5>
                    <p class="text-muted">Coupon usage across customer groups</p>
                    <div class="table-responsive">
                        <table class="table table-hover data-table">
                            <thead>
                                <tr>
                                    <th>Customer Group</th>
                                    <th class="text-end">Redemptions</th>
                                    <th class="text-end">Total Discount</th>
                                    <th class="text-end">Unique Customers</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($redemptionByCustomerGroup as $group)
                                    <tr>
                                        <td>{{ $group->name ?? 'Unknown' }}</td>
                                        <td class="text-end">{{ $group->redemptions }}</td>
                                        <td class="text-end">{{ $currencySymbol }}{{ number_format($group->total_discount, 2) }}</td>
                                        <td class="text-end">{{ $group->unique_customers }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No customer group data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['node_modules/chart.js/dist/chart.umd.js', 'node_modules/daterangepicker/daterangepicker.js', 'node_modules/sweetalert2/dist/sweetalert2.all.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Date range picker
            const dateRangePicker = document.getElementById('date-range-picker');
            if (dateRangePicker) {
                $(dateRangePicker).daterangepicker({
                    opens: 'left',
                    startDate: moment('{{ $filters->startDate->format('Y-m-d') }}'),
                    endDate: moment('{{ $filters->endDate->format('Y-m-d') }}'),
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                    }
                }, function(start, end) {
                    $('#date-range-text').html(start.format('MMM D, YYYY') + ' - ' + end.format('MMM D, YYYY'));
                    $('#start-date').val(start.format('YYYY-MM-DD'));
                    $('#end-date').val(end.format('YYYY-MM-DD'));
                });
            }

            // Redemption Chart
            const redemptionCtx = document.getElementById('redemptionChart').getContext('2d');
            const redemptionData = @json($redemptionAnalytics);
            const dates = redemptionData.map(item => item.date);
            const redemptions = redemptionData.map(item => item.redemptions);
            const discounts = redemptionData.map(item => item.total_discount);

            new Chart(redemptionCtx, {
                type: 'line',
                data: {
                    labels: dates,
                    datasets: [
                        {
                            label: 'Redemptions',
                            data: redemptions,
                            borderColor: '#3b76e1',
                            backgroundColor: 'rgba(59, 118, 225, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Discount Amount',
                            data: discounts,
                            borderColor: '#10c469',
                            backgroundColor: 'rgba(16, 196, 105, 0.1)',
                            borderWidth: 2,
                            fill: true,
                            tension: 0.4,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Redemptions'
                            },
                            grid: {
                                drawBorder: false
                            }
                        },
                        y1: {
                            type: 'linear',
                            display: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Discount Amount'
                            },
                            grid: {
                                drawBorder: false
                            },
                            ticks: {
                                callback: function(value) {
                                    return '{{ $currencySymbol }}' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label.includes('Discount')) {
                                        return label + ': {{ $currencySymbol }}' + context.parsed.y.toLocaleString();
                                    }
                                    return label + ': ' + context.parsed.y;
                                }
                            }
                        }
                    }
                }
            });

            // Type Chart
            const typeCtx = document.getElementById('typeChart').getContext('2d');
            const typeData = @json($performanceByType);
            const typeLabels = typeData.map(item => item.type);
            const typeRedemptions = typeData.map(item => item.total_redemptions);
            const typeDiscounts = typeData.map(item => item.total_discount);

            new Chart(typeCtx, {
                type: 'bar',
                data: {
                    labels: typeLabels,
                    datasets: [
                        {
                            label: 'Redemptions',
                            data: typeRedemptions,
                            backgroundColor: '#3b76e1',
                            borderColor: '#3b76e1',
                            borderWidth: 1,
                            yAxisID: 'y'
                        },
                        {
                            label: 'Total Discount',
                            data: typeDiscounts,
                            backgroundColor: '#10c469',
                            borderColor: '#10c469',
                            borderWidth: 1,
                            yAxisID: 'y1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            position: 'left',
                            title: {
                                display: true,
                                text: 'Redemptions'
                            },
                            grid: {
                                drawBorder: false
                            }
                        },
                        y1: {
                            beginAtZero: true,
                            position: 'right',
                            title: {
                                display: true,
                                text: 'Discount Amount'
                            },
                            grid: {
                                drawBorder: false
                            },
                            ticks: {
                                callback: function(value) {
                                    return '{{ $currencySymbol }}' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label.includes('Discount')) {
                                        return label + ': {{ $currencySymbol }}' + context.parsed.y.toLocaleString();
                                    }
                                    return label + ': ' + context.parsed.y;
                                }
                            }
                        }
                    }
                }
            });
        });
    </script>
@endsection