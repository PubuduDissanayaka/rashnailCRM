@extends('layouts.vertical', ['title' => 'Expense Management'])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Expenses', 'title' => 'Expense Management'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10" data-table-search="" data-table-filter="">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Expense List</h4>
                        <p class="text-muted mb-0">Manage all expenses and approval workflow</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search expenses..." type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        @can('expenses.create')
                        <a href="{{ route('expenses.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Add Expense
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body border-top border-light">
                    <div class="row text-center">
                        <div class="col-md-2">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['total'] ?? 0 }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Total</h6>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['pending'] ?? 0 }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Pending</h6>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['approved'] ?? 0 }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Approved</h6>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['paid'] ?? 0 }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Paid</h6>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $currencySymbol }}{{ number_format($stats['total_amount'] ?? 0, 2) }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Total Amount</h6>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Filters -->
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" data-table-filter="status">
                                <option value="">All Status</option>
                                <option value="draft">Draft</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select class="form-select" data-table-filter="category">
                                <option value="">All Categories</option>
                                @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date Range</label>
                            <input type="text" class="form-control date-range-picker" data-table-filter="date_range" placeholder="Select date range">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="button" class="btn btn-light w-100" data-table-filter-reset>
                                <i class="ti ti-refresh me-1"></i> Reset Filters
                            </button>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table table-custom table-centered table-select table-hover w-100 mb-0">
                            <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th class="ps-3" style="width: 1%;">#</th>
                                    <th data-table-sort="sort-expense-number">Expense #</th>
                                    <th data-table-sort="sort-title">Title</th>
                                    <th data-table-sort="sort-category">Category</th>
                                    <th data-table-sort="sort-amount">Amount</th>
                                    <th data-table-sort="sort-date">Date</th>
                                    <th data-table-sort="sort-status">Status</th>
                                    <th class="text-center" style="width: 1%;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenses as $expense)
                                <tr>
                                    <td class="ps-3">{{ $loop->iteration }}</td>
                                    <td data-sort="sort-expense-number">
                                        <span class="fw-semibold">{{ $expense->expense_number }}</span>
                                    </td>
                                    <td data-sort="sort-title">
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
                                    <td data-sort="sort-category">
                                        <span class="badge bg-{{ $expense->category->color ?? 'secondary' }}-subtle text-{{ $expense->category->color ?? 'secondary' }}">
                                            <i class="ti ti-{{ $expense->category->icon ?? 'category' }} fs-xs"></i> {{ $expense->category->name ?? 'Uncategorized' }}
                                        </span>
                                    </td>
                                    <td data-sort="sort-amount">
                                        <span class="fw-semibold">{{ $currencySymbol }}{{ number_format($expense->total_amount, 2) }}</span>
                                        <small class="d-block text-muted">Amount: {{ $currencySymbol }}{{ number_format($expense->amount, 2) }}</small>
                                    </td>
                                    <td data-sort="sort-date">
                                        {{ $expense->expense_date->format('d M, Y') }}
                                        <small class="d-block text-muted">Due: {{ $expense->due_date->format('d M, Y') }}</small>
                                    </td>
                                    <td data-sort="sort-status">
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
                                            @can('expenses.manage')
                                                @if($expense->status != 'paid')
                                                <form id="delete-form-{{ $expense->id }}" action="{{ route('expenses.destroy', $expense->id) }}" method="POST" style="display: none;">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                                <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle"
                                                        onclick="confirmDelete('{{ $expense->id }}', '{{ addslashes($expense->title) }}', '{{ $expense->expense_number }}')"
                                                        title="Delete Expense">
                                                    <i class="ti ti-trash fs-lg"></i>
                                                </button>
                                                @endif
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
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
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="expenses"></div>
                        <div data-table-pagination=""></div>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        function confirmDelete(expenseId, expenseTitle, expenseNumber) {
            Swal.fire({
                title: 'Confirm Deletion',
                text: `Are you sure you want to delete expense "${expenseNumber} - ${expenseTitle}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById(`delete-form-${expenseId}`).submit();
                }
            });
        }

        // Initialize date range picker
        document.addEventListener('DOMContentLoaded', function() {
            const dateRangePicker = document.querySelector('.date-range-picker');
            if (dateRangePicker) {
                flatpickr(dateRangePicker, {
                    mode: "range",
                    dateFormat: "Y-m-d",
                    onChange: function(selectedDates, dateStr, instance) {
                        if (dateStr) {
                            const table = document.querySelector('[data-table]');
                            if (table) {
                                const event = new CustomEvent('table-filter', {
                                    detail: { filter: 'date_range', value: dateStr }
                                });
                                table.dispatchEvent(event);
                            }
                        }
                    }
                });
            }
        });
    </script>
@endsection