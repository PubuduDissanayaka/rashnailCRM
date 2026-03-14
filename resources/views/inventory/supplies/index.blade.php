@extends('layouts.vertical', ['title' => 'Supply Management'])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Inventory', 'title' => 'Supply Management'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Supply List</h4>
                        <p class="text-muted mb-0">Manage all supplies and stock levels</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search supplies..." type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        @can('inventory.manage')
                        <a href="{{ route('inventory.supplies.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Add Supply
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body border-top border-light">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['total'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Total Supplies</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['active'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Active Supplies</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['low_stock'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Low Stock</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['out_of_stock'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Out of Stock</h6>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">ID</th>
                                <th data-table-sort="sort-name">Supply Name</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th data-table-sort="sort-stock">Stock Level</th>
                                <th data-table-sort="sort-cost">Unit Cost</th>
                                <th data-table-sort="sort-status">Status</th>
                                <th data-table-sort="sort-created">Created</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($supplies as $supply)
                            <tr>
                                <td class="ps-3">{{ $supply->id }}</td>
                                <td data-sort="sort-name">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs rounded-circle bg-soft-primary me-2">
                                            <span class="avatar-title rounded-circle">
                                                <i class="ti ti-package fs-10"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h5 class="fs-base mb-0">{{ $supply->name }}</h5>
                                            <small class="text-muted">{{ $supply->brand ?: 'No brand' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $supply->sku }}</td>
                                <td>{{ $supply->category->name ?? 'Uncategorized' }}</td>
                                <td data-sort="sort-stock">
                                    <div class="d-flex align-items-center">
                                        <div class="me-2">
                                            @include('inventory.supplies.partials.stock-badge', ['supply' => $supply])
                                        </div>
                                    </div>
                                </td>
                                <td data-sort="sort-cost">{{ $currencySymbol }}{{ number_format($supply->unit_cost, 2) }}</td>
                                <td data-sort="sort-status">
                                    <span class="badge bg-{{ $supply->is_active ? 'success' : 'secondary' }}-subtle text-{{ $supply->is_active ? 'success' : 'secondary' }}">
                                        <i class="ti ti-circle-filled fs-xs"></i> {{ $supply->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td data-sort="sort-created">{{ $supply->created_at->format('d M, Y') }}</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('inventory.supplies.show', $supply->id) }}" title="View Supply">
                                            <i class="ti ti-eye fs-lg"></i>
                                        </a>
                                        @can('inventory.manage')
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('inventory.supplies.edit', $supply->id) }}" title="Edit Supply">
                                            <i class="ti ti-edit fs-lg"></i>
                                        </a>
                                        @endcan
                                        @can('inventory.supplies.adjust')
                                        <button type="button" class="btn btn-warning btn-icon btn-sm rounded-circle" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#adjustStockModal{{ $supply->id }}"
                                                title="Adjust Stock">
                                            <i class="ti ti-adjustments fs-lg"></i>
                                        </button>
                                        @endcan
                                        @can('inventory.manage')
                                        <form id="delete-form-{{ $supply->id }}" action="{{ route('inventory.supplies.destroy', $supply->id) }}" method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle"
                                                onclick="confirmDelete('{{ $supply->id }}', '{{ addslashes($supply->name) }}', '{{ $supply->id }}')"
                                                title="Delete Supply">
                                            <i class="ti ti-trash fs-lg"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="ti ti-package-off fs-24 mb-2 d-block"></i>
                                        No supplies found. <a href="{{ route('inventory.supplies.create') }}">Create the first supply</a>.
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="supplies"></div>
                        <div data-table-pagination=""></div>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->

    <!-- Adjust Stock Modals -->
    @foreach($supplies as $supply)
        @include('inventory.supplies.partials.adjust-stock-modal', ['supply' => $supply])
    @endforeach
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        function confirmDelete(supplyId, supplyName) {
            Swal.fire({
                title: 'Confirm Deletion',
                text: `Are you sure you want to delete the supply "${supplyName}"?`,
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
                    document.getElementById(`delete-form-${supplyId}`).submit();
                }
            });
        }
    </script>
@endsection