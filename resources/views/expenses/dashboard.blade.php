@extends('layouts.vertical', ['title' => 'Expense Dashboard'])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
    @vite(['resources/js/pages/expenses-dashboard.js'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Expenses', 'title' => 'Expense Dashboard'])

    <div class="row">
        <!-- Stats Cards -->
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="fw-medium text-muted mb-0">Paid This Month</p>
                            <h4 class="mt-2 mb-0">{{ $stats['paid_this_month'] ?? 0 }}</h4>
                            <p class="text-muted mb-0">
                                <span class="text-success fw-medium">{{ $stats['paid_month_amount'] ?? '$0.00' }}</span>
                            </p>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-success-subtle rounded-circle fs-2">
                                <i class="ti ti-wallet text-success"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="fw-medium text-muted mb-0">Total Pending</p>
                            <h4 class="mt-2 mb-0">{{ $stats['total_pending'] ?? 0 }}</h4>
                            <p class="text-muted mb-0">
                                <span class="text-warning fw-medium">{{ $stats['pending_amount'] ?? '$0.00' }}</span>
                            </p>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-warning-subtle rounded-circle fs-2">
                                <i class="ti ti-clock text-warning"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="fw-medium text-muted mb-0">Total Approved</p>
                            <h4 class="mt-2 mb-0">{{ $stats['total_approved'] ?? 0 }}</h4>
                            <p class="text-muted mb-0">
                                <span class="text-info fw-medium">{{ $stats['approved_amount'] ?? '$0.00' }}</span>
                            </p>
                        </div>
                        <div class="avatar-sm flex-shrink-0">
                            <span class="avatar-title bg-info-subtle rounded-circle fs-2">
                                <i class="ti ti-check text-info"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="fw-medium text-muted mb-0">Total Paid</p>
                            <h4 class="mt-2 mb-0">{{ $stats['total_paid'] ?? 0 }}</h4>
                            <p class="text-muted mb-0">
                                <span class="text-success fw-medium">{{ $stats['paid_amount'] ?? '$0.00' }}</span>
                            </p>
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
    </div>

    <div class="row">
        <!-- Monthly Trend Chart -->
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Monthly Expense Trend</h4>
                </div>
                <div class="card-body">
                    <div id="monthly-trend-chart" style="min-height: 350px;"></div>
                </div>
            </div>
        </div>

        <!-- Category Breakdown Chart -->
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Category Breakdown</h4>
                </div>
                <div class="card-body">
                    <div id="category-breakdown-chart" style="min-height: 350px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Budget Utilization Chart -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Budget Utilization</h4>
                </div>
                <div class="card-body">
                    <div id="budget-utilization-chart" style="min-height: 350px;"></div>
                </div>
            </div>
        </div>

        <!-- Overdue Expenses -->
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title mb-0">Overdue Expenses</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-custom table-centered table-hover w-100 mb-0">
                            <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th class="ps-3">Expense #</th>
                                    <th>Title</th>
                                    <th>Due Date</th>
                                    <th>Amount</th>
                                    <th class="text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($overdue_expenses as $expense)
                                <tr>
                                    <td class="ps-3">{{ $expense->expense_number }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs rounded-circle bg-soft-primary me-2">
                                                <span class="avatar-title rounded-circle">
                                                    <i class="ti ti-file-invoice fs-10"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <h5 class="fs-base mb-0">{{ Str::limit($expense->title, 30) }}</h5>
                                                <small class="text-muted">{{ $expense->vendor_name }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-danger">{{ $expense->due_date->format('d M, Y') }}</span>
                                        <small class="d-block text-muted">{{ $expense->due_date->diffForHumans() }}</small>
                                    </td>
                                    <td>${{ number_format($expense->total_amount, 2) }}</td>
                                    <td class="text-center">
                                        @include('expenses.partials.status-badge', ['status' => $expense->status])
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="ti ti-checkbox fs-24 mb-2 d-block"></i>
                                            No overdue expenses found.
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Expenses -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Recent Expenses</h4>
                        <p class="text-muted mb-0">Latest expense submissions</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <a href="{{ route('expenses.index') }}" class="btn btn-light">
                            <i class="ti ti-list me-1"></i> View All
                        </a>
                        @can('expenses.create')
                        <a href="{{ route('expenses.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Add Expense
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-custom table-centered table-hover w-100 mb-0">
                            <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th class="ps-3">Expense #</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recent_expenses as $expense)
                                <tr>
                                    <td class="ps-3">{{ $expense->expense_number }}</td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-xs rounded-circle bg-soft-primary me-2">
                                                <span class="avatar-title rounded-circle">
                                                    <i class="ti ti-file-invoice fs-10"></i>
                                                </span>
                                            </div>
                                            <div>
                                                <h5 class="fs-base mb-0">{{ Str::limit($expense->title, 30) }}</h5>
                                                <small class="text-muted">{{ $expense->vendor_name }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $expense->category->color ?? 'secondary' }}-subtle text-{{ $expense->category->color ?? 'secondary' }}">
                                            <i class="ti ti-{{ $expense->category->icon ?? 'category' }} fs-xs"></i> {{ $expense->category->name ?? 'Uncategorized' }}
                                        </span>
                                    </td>
                                    <td>${{ number_format($expense->total_amount, 2) }}</td>
                                    <td>{{ $expense->expense_date->format('d M, Y') }}</td>
                                    <td>
                                        @include('expenses.partials.status-badge', ['status' => $expense->status])
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('expenses.show', $expense->id) }}" title="View Expense">
                                                <i class="ti ti-eye fs-lg"></i>
                                            </a>
                                            @can('expenses.manage')
                                                @if(!in_array($expense->status, ['paid', 'rejected']))
                                                <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('expenses.edit', $expense->id) }}" title="Edit Expense">
                                                    <i class="ti ti-edit fs-lg"></i>
                                                </a>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="ti ti-file-off fs-24 mb-2 d-block"></i>
                                            No expenses found. <a href="{{ route('expenses.create') }}">Create the first expense</a>.
                                        </div>
                                    </td>
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
    @vite(['resources/js/pages/custom-table.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        // Charts will be initialized by expenses-dashboard.js
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize any dashboard-specific functionality
        });
    </script>
@endsection