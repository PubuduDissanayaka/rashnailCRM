@extends('layouts.vertical', ['title' => 'Service Management'])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['subtitle' => 'Services', 'title' => 'Service Management'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Service List</h4>
                        <p class="text-muted mb-0">Manage all services offered by the salon</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search services..." type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        @can('create services')
                        <a href="{{ route('services.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Add Service
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body border-top border-light">
                    <div class="row text-center">
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['total'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Total Services</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['active'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Active Services</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $stats['inactive'] }}</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Inactive Services</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="p-3">
                                <h5 class="text-muted fw-normal mt-0 text-truncate">{{ $services->where('is_active', true)->avg('duration') ?? 0 }} min</h5>
                                <h6 class="text-uppercase fw-bold mb-0">Avg. Duration</h6>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">ID</th>
                                <th data-table-sort="sort-name">Service Name</th>
                                <th>Description</th>
                                <th data-table-sort="sort-price">Price</th>
                                <th data-table-sort="sort-duration">Duration (min)</th>
                                <th data-table-sort="sort-status">Status</th>
                                <th data-table-sort="sort-created">Created</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($services as $service)
                            <tr>
                                <td class="ps-3">{{ $service->id }}</td>
                                <td data-sort="sort-name">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-xs rounded-circle bg-soft-primary me-2">
                                            <span class="avatar-title rounded-circle">
                                                <i class="ti ti-scissors fs-10"></i>
                                            </span>
                                        </div>
                                        <div>
                                            <h5 class="fs-base mb-0">{{ $service->name }}</h5>
                                        </div>
                                    </div>
                                </td>
                                <td data-sort="sort-description">{{ Str::limit($service->description, 50) }}</td>
                                <td data-sort="sort-price">{{ $currencySymbol }}{{ number_format($service->price, 2) }}</td>
                                <td data-sort="sort-duration">{{ $service->duration }} min</td>
                                <td data-sort="sort-status">
                                    <span class="badge bg-{{ $service->is_active ? 'success' : 'secondary' }}-subtle text-{{ $service->is_active ? 'success' : 'secondary' }}">
                                        <i class="ti ti-circle-filled fs-xs"></i> {{ $service->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td data-sort="sort-created">{{ $service->created_at->format('d M, Y') }}</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('services.show', $service->slug) }}" title="View Service">
                                            <i class="ti ti-eye fs-lg"></i>
                                        </a>
                                        @can('edit services')
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('services.edit', $service->slug) }}" title="Edit Service">
                                            <i class="ti ti-edit fs-lg"></i>
                                        </a>
                                        @endcan
                                        @can('delete services')
                                        <form id="delete-form-{{ $service->slug }}" action="{{ route('services.destroy', $service->slug) }}" method="POST" style="display: none;">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle"
                                                onclick="confirmDelete('{{ $service->id }}', '{{ addslashes($service->name) }}', '{{ $service->slug }}')"
                                                title="Delete Service">
                                            <i class="ti ti-trash fs-lg"></i>
                                        </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="ti ti-files fs-24 mb-2 d-block"></i>
                                        No services found. <a href="{{ route('services.create') }}">Create the first service</a>.
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="services"></div>
                        <div data-table-pagination=""></div>
                    </div>
                </div>
            </div>
        </div> <!-- end col -->
    </div> <!-- end row -->
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js'])
    <script>
        function confirmDelete(serviceId, serviceName, serviceSlug) {
            Swal.fire({
                title: 'Confirm Deletion',
                text: `Are you sure you want to delete the service "${serviceName}"?`,
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
                    document.getElementById(`delete-form-${serviceSlug}`).submit();
                }
            });
        }
    </script>
@endsection