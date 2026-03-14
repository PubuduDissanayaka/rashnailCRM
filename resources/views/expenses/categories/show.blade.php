@extends('layouts.vertical', ['title' => 'View Expense Category'])

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Expenses', 'title' => 'Category Details'])

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-body text-center">
                    <div class="avatar-lg rounded-circle mx-auto mb-3" style="background-color: {{ $category->color ?? '#6366f1' }};">
                        <span class="avatar-title rounded-circle bg-transparent">
                            <i class="ti ti-{{ $category->icon ?? 'category' }} fs-32 text-white"></i>
                        </span>
                    </div>
                    <h4 class="mb-1">{{ $category->name }}</h4>
                    @if($category->parent)
                        <p class="text-muted mb-2">
                            <i class="ti ti-folder me-1"></i>
                            Parent: {{ $category->parent->name }}
                        </p>
                    @endif
                    <span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}-subtle text-{{ $category->is_active ? 'success' : 'secondary' }}">
                        <i class="ti ti-circle-filled fs-xs"></i> {{ $category->is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>
                <div class="card-footer border-top">
                    <div class="d-flex justify-content-center gap-2">
                        @can('expenses.manage')
                        <a href="{{ route('expenses.categories.edit', $category->id) }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-edit me-1"></i> Edit
                        </a>
                        @endcan
                        <a href="{{ route('expenses.categories.index') }}" class="btn btn-secondary btn-sm">
                            <i class="ti ti-arrow-left me-1"></i> Back
                        </a>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Category Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small">Description</label>
                        <p class="mb-0">{{ $category->description ?: 'No description provided' }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Sort Order</label>
                        <p class="mb-0">{{ $category->sort_order ?? 0 }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted small">Created At</label>
                        <p class="mb-0">{{ $category->created_at->format('M d, Y h:i A') }}</p>
                    </div>
                    <div>
                        <label class="text-muted small">Last Updated</label>
                        <p class="mb-0">{{ $category->updated_at->format('M d, Y h:i A') }}</p>
                    </div>
                </div>
            </div>

            @if($category->budget_amount)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Budget</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Budget Amount</span>
                        <span class="fw-semibold">{{ $currency_symbol ?? '$' }}{{ number_format($category->budget_amount, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Period</span>
                        <span class="fw-semibold">{{ ucfirst($category->budget_period) }}</span>
                    </div>
                    @php
                        $utilization = $category->getBudgetUtilization();
                        $spent = $category->getTotalExpenses($category->budget_period ?? 'month');
                        $progressColor = $utilization > 100 ? 'danger' : ($utilization > 75 ? 'warning' : 'success');
                    @endphp
                    <div class="d-flex justify-content-between mb-1">
                        <small>Spent: {{ $currency_symbol ?? '$' }}{{ number_format($spent, 2) }}</small>
                        <small>{{ number_format($utilization, 1) }}% used</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-{{ $progressColor }}" role="progressbar" 
                             style="width: {{ min($utilization, 100) }}%"></div>
                    </div>
                    @if($utilization > 100)
                    <div class="alert alert-danger mt-3 mb-0 py-2">
                        <i class="ti ti-alert-circle me-1"></i>
                        Budget exceeded by {{ $currency_symbol ?? '$' }}{{ number_format($spent - $category->budget_amount, 2) }}
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-4">
                            <div class="p-3">
                                <h3 class="text-primary mb-1">{{ $category->expenses->count() }}</h3>
                                <p class="text-muted mb-0">Total Expenses</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <h3 class="text-success mb-1">{{ $currency_symbol ?? '$' }}{{ number_format($category->expenses->sum('total_amount'), 2) }}</h3>
                                <p class="text-muted mb-0">Total Amount</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <h3 class="text-info mb-1">{{ $category->children->count() }}</h3>
                                <p class="text-muted mb-0">Sub-categories</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            @if($category->children->count() > 0)
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Sub-categories</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        @foreach($category->children as $child)
                        <div class="col-md-6 mb-3">
                            <div class="d-flex align-items-center p-3 border rounded">
                                <div class="avatar-sm rounded-circle me-3" style="background-color: {{ $child->color ?? '#6366f1' }};">
                                    <span class="avatar-title rounded-circle bg-transparent">
                                        <i class="ti ti-{{ $child->icon ?? 'category' }} text-white"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ $child->name }}</h6>
                                    <small class="text-muted">{{ $child->expenses_count ?? 0 }} expenses</small>
                                </div>
                                <a href="{{ route('expenses.categories.show', $child->id) }}" class="btn btn-light btn-sm">
                                    <i class="ti ti-eye"></i>
                                </a>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Recent Expenses</h5>
                    <a href="{{ route('expenses.index', ['category' => $category->id]) }}" class="btn btn-sm btn-primary">
                        View All
                    </a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($category->expenses as $expense)
                            <tr>
                                <td>
                                    <h6 class="mb-0">{{ $expense->title }}</h6>
                                    <small class="text-muted">{{ Str::limit($expense->description, 30) }}</small>
                                </td>
                                <td>{{ $expense->expense_date->format('M d, Y') }}</td>
                                <td class="fw-semibold">{{ $currency_symbol ?? '$' }}{{ number_format($expense->total_amount, 2) }}</td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'approved' => 'info',
                                            'paid' => 'success',
                                            'rejected' => 'danger',
                                        ];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$expense->status] ?? 'secondary' }}-subtle text-{{ $statusColors[$expense->status] ?? 'secondary' }}">
                                        {{ ucfirst($expense->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('expenses.show', $expense->id) }}" class="btn btn-light btn-icon btn-sm rounded-circle">
                                        <i class="ti ti-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    <i class="ti ti-receipt-off fs-24 d-block mb-2"></i>
                                    No expenses in this category yet.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
