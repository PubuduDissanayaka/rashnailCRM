@extends('layouts.vertical', ['title' => 'Service Packages'])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css'])
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        function confirmDelete(packageId, packageName, packageSlug) {
            Swal.fire({
                title: 'Confirm Deletion',
                text: `Are you sure you want to delete the package "${packageName}"?`,
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
                    document.getElementById(`delete-form-${packageSlug}`).submit();
                }
            });
        }
    </script>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Service Packages'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Service Packages</h4>
                        <p class="text-muted mb-0">Manage service packages and bundles</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search packages..."
                                type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        @can('create service packages')
                        <a href="{{ route('service-packages.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Add Package
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['total'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Total Packages</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['active'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Active Packages</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['inactive'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Inactive Packages</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $currencySymbol }}{{ number_format($stats['total_savings'] ?? 0, 2) }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Total Savings</h6>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">ID</th>
                                <th data-table-sort="sort-name">Package Name</th>
                                <th>Description</th>
                                <th data-table-sort="sort-base-price">Base Price</th>
                                <th data-table-sort="sort-discounted-price">Discounted Price</th>
                                <th data-table-sort="sort-savings">Savings</th>
                                <th data-table-sort="sort-services">Service Count</th>
                                <th data-table-sort="sort-status">Status</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($packages as $package)
                            <tr>
                                <td class="ps-3">{{ $package->id }}</td>
                                <td data-sort="sort-name">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm rounded-circle bg-soft-primary me-2">
                                            <span class="avatar-title rounded-circle text-uppercase">
                                                {{ substr($package->name, 0, 1) }}
                                            </span>
                                        </div>
                                        <div>
                                            <h5 class="fs-base mb-0">{{ $package->name }}</h5>
                                        </div>
                                    </div>
                                </td>
                                <td data-sort="sort-description">{{ Str::limit($package->description, 50) }}</td>
                                <td data-sort="sort-base-price">{{ $currencySymbol }}{{ number_format($package->base_price, 2) }}</td>
                                <td data-sort="sort-discounted-price">{{ $currencySymbol }}{{ number_format($package->discounted_price, 2) }}</td>
                                <td data-sort="sort-savings">{{ $currencySymbol }}{{ number_format($package->base_price - $package->discounted_price, 2) }}</td>
                                <td data-sort="sort-services">{{ $package->services->count() }}</td>
                                <td data-sort="sort-status">
                                    <span class="badge bg-{{ $package->is_active ? 'success' : 'secondary' }}-subtle text-{{ $package->is_active ? 'success' : 'secondary' }}">
                                        <i class="ti ti-circle-filled fs-xs"></i> {{ $package->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('service-packages.show', $package->slug) }}" title="View Details">
                                            <i class="ti ti-eye fs-lg"></i>
                                        </a>
                                        @can('edit service packages')
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('service-packages.edit', $package->slug) }}" title="Edit Package">
                                            <i class="ti ti-edit fs-lg"></i>
                                        </a>
                                        @endcan
                                        @can('delete service packages')
                                        <form id="delete-form-{{ $package->slug }}" action="{{ route('service-packages.destroy', $package->slug) }}" method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle"
                                                onclick="confirmDelete('{{ $package->id }}', '{{ addslashes($package->name) }}', '{{ $package->slug }}')"
                                                title="Delete Package">
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
                                        <i class="ti ti-package fs-24 mb-2 d-block"></i>
                                        No service packages found. <a href="{{ route('service-packages.create') }}">Create the first package</a>.
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="service-packages"></div>
                        <div data-table-pagination=""></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection