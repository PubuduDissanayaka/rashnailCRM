@extends('layouts.vertical', ['title' => 'Customer Groups Management'])

@section('css')
    @vite(['node_modules/datatables.net-bs5/css/dataTables.bootstrap5.min.css', 'node_modules/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css'])
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        function confirmDelete(groupId, groupName) {
            Swal.fire({
                title: 'Confirm Deletion',
                text: `Are you sure you want to delete the customer group "${groupName}"?`,
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
                    document.getElementById(`delete-form-${groupId}`).submit();
                }
            });
        }
    </script>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Customer Groups Management'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">Customer Groups</h4>
                        <p class="text-muted mb-0">Manage customer groups for coupon eligibility</p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search groups..."
                                type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        @can('manage system')
                        <a href="{{ route('customer-groups.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Create Group
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-custom table-centered table-select table-hover w-100 mb-0">
                            <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th class="ps-3" style="width: 1%;">ID</th>
                                    <th data-table-sort="sort-name">Name</th>
                                    <th data-table-sort="sort-description">Description</th>
                                    <th data-table-sort="sort-customers">Customers</th>
                                    <th data-table-sort="sort-status">Status</th>
                                    <th class="text-end pe-3" style="width: 150px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($groups as $group)
                                    <tr>
                                        <td class="ps-3">{{ $group->id }}</td>
                                        <td>
                                            <div class="fw-semibold">{{ $group->name }}</div>
                                        </td>
                                        <td>{{ Str::limit($group->description, 50) }}</td>
                                        <td>{{ $group->customers_count ?? 0 }}</td>
                                        <td>
                                            @if ($group->is_active)
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td class="text-end pe-3">
                                            <div class="d-flex gap-2 justify-content-end">
                                                <a href="{{ route('customer-groups.edit', $group) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="ti ti-edit"></i> Edit
                                                </a>
                                                @can('manage system')
                                                <form id="delete-form-{{ $group->id }}" action="{{ route('customer-groups.destroy', $group) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete({{ $group->id }}, '{{ $group->name }}')">
                                                        <i class="ti ti-trash"></i> Delete
                                                    </button>
                                                </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted">
                                            <i class="ti ti-users-off me-1"></i> No customer groups found.
                                            @can('manage system')
                                            <a href="{{ route('customer-groups.create') }}" class="ms-1">Create one</a>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="groups"></div>
                        <div data-table-pagination=""></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection