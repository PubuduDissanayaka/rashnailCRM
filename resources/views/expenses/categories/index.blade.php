@extends('layouts.vertical', ['title' => 'Expense Categories'])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Expenses', 'title' => 'Expense Categories'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Expense Categories</h4>
                        <p class="text-muted mb-0">Manage expense categories and budgets</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search categories..." type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        @can('expenses.manage')
                        <a href="{{ route('expenses.categories.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Add Category
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body border-top border-light">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['total'] ?? 0 }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Total Categories</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['active'] ?? 0 }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Active Categories</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['with_budget'] ?? 0 }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">With Budget</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['total_expenses'] ?? 0 }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Total Expenses</h6>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">#</th>
                                <th data-table-sort="sort-name">Name</th>
                                <th>Description</th>
                                <th data-table-sort="sort-budget">Budget</th>
                                <th data-table-sort="sort-expenses"># of Expenses</th>
                                <th data-table-sort="sort-status">Status</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categories as $category)
                            <tr>
                                <td class="ps-3">{{ $loop->iteration }}</td>
                                <td data-sort="sort-name">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs rounded-circle bg-soft-{{ $category->color ?? 'primary' }} me-2">
                                            <span class="avatar-title rounded-circle">
                                                <i class="ti ti-{{ $category->icon ?? 'category' }} fs-10"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h5 class="fs-base mb-0">{{ $category->name }}</h5>
                                            @if($category->parent)
                                            <small class="text-muted">Parent: {{ $category->parent->name }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ Str::limit($category->description, 50) }}</td>
                                <td data-sort="sort-budget">
                                    @if($category->budget_amount)
                                        <span class="fw-semibold">{{ $currency_symbol ?? '$' }}{{ number_format($category->budget_amount, 2) }}</span>
                                        <small class="d-block text-muted">{{ ucfirst($category->budget_period) }}</small>
                                    @else
                                        <span class="text-muted">No budget</span>
                                    @endif
                                </td>
                                <td data-sort="sort-expenses">
                                    <span class="fw-semibold">{{ $category->expenses_count ?? 0 }}</span>
                                    <small class="d-block text-muted">
                                        {{ $currency_symbol ?? '$' }}{{ number_format($category->total_expenses_amount ?? 0, 2) }}
                                    </small>
                                </td>
                                <td data-sort="sort-status">
                                    <span class="badge bg-{{ $category->is_active ? 'success' : 'secondary' }}-subtle text-{{ $category->is_active ? 'success' : 'secondary' }}">
                                        <i class="ti ti-circle-filled fs-xs"></i> {{ $category->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('expenses.categories.show', $category->id) }}" title="View Category">
                                            <i class="ti ti-eye fs-lg"></i>
                                        </a>
                                        @can('expenses.manage')
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('expenses.categories.edit', $category->id) }}" title="Edit Category">
                                            <i class="ti ti-edit fs-lg"></i>
                                        </a>
                                        @endcan
                                        @can('expenses.manage')
                                            @if($category->expenses_count == 0)
                                            <form id="delete-form-{{ $category->id }}" action="{{ route('expenses.categories.destroy', $category->id) }}" method="POST" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle"
                                                    onclick="confirmDelete('{{ $category->id }}', '{{ addslashes($category->name) }}')"
                                                    title="Delete Category">
                                                <i class="ti ti-trash fs-lg"></i>
                                            </button>
                                            @else
                                            <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle disabled" title="Cannot delete category with expenses">
                                                <i class="ti ti-trash fs-lg"></i>
                                            </button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="ti ti-category-off fs-24 mb-2 d-block"></i>
                                        No categories found. <a href="{{ route('expenses.categories.create') }}">Create the first category</a>.
                                    </div>
                                    </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="categories"></div>
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
        function confirmDelete(categoryId, categoryName) {
            Swal.fire({
                title: 'Confirm Deletion',
                text: `Are you sure you want to delete the category "${categoryName}"?`,
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
                    document.getElementById(`delete-form-${categoryId}`).submit();
                }
            });
        }
    </script>
@endsection