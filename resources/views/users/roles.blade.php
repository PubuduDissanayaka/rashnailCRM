@extends('layouts.vertical', ['title' => 'Roles & Permissions'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Roles & Permissions'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search roles..." type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="me-2 fw-semibold">Filter By:</span>
                        <div class="app-search">
                            <select class="form-select form-control my-1 my-md-0" data-table-filter="type">
                                <option value="All">Type</option>
                                <option value="System">System</option>
                                <option value="Custom">Custom</option>
                            </select>
                            <i class="app-search-icon text-muted" data-lucide="shield"></i>
                        </div>
                        <div>
                            <select class="form-select form-control my-1 my-md-0" data-table-set-rows-per-page="">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                            </select>
                        </div>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                            <i class="ti ti-plus me-1"></i> Create Role
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th data-table-sort="sort-name">Role</th>
                                <th data-table-sort="sort-type">Type</th>
                                <th data-table-sort="sort-users">Users</th>
                                <th data-table-sort="sort-permissions">Permissions</th>
                                <th data-table-sort="sort-created">Created</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($roles as $role)
                            @php
                                $isSystem = $role->name === 'administrator';
                                $userCount = $role->users->count();
                                $permCount = $role->permissions->count();
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm rounded-circle bg-soft-{{ $isSystem ? 'danger' : 'primary' }} me-2">
                                            <span class="avatar-title rounded-circle text-uppercase">
                                                {{ substr($role->name, 0, 1) }}
                                            </span>
                                        </div>
                                        <div>
                                            <h5 class="fs-base mb-0">
                                                <a class="link-reset" href="{{ route('users.role-details', $role->name) }}">
                                                    {{ ucfirst($role->name) }}
                                                </a>
                                            </h5>
                                        </div>
                                    </div>
                                </td>
                                <td data-sort="{{ $isSystem ? 'System' : 'Custom' }}">
                                    @if($isSystem)
                                        <span class="badge bg-danger-subtle text-danger">
                                            <i class="ti ti-lock fs-xs me-1"></i> System
                                        </span>
                                    @else
                                        <span class="badge bg-primary-subtle text-primary">
                                            <i class="ti ti-users fs-xs me-1"></i> Custom
                                        </span>
                                    @endif
                                </td>
                                <td data-sort="{{ $userCount }}">
                                    <span class="badge bg-info-subtle text-info">
                                        <i class="ti ti-user fs-xs me-1"></i> {{ $userCount }} user(s)
                                    </span>
                                </td>
                                <td data-sort="{{ $permCount }}">
                                    <span class="badge bg-{{ $permCount > 40 ? 'success' : ($permCount > 15 ? 'warning' : 'secondary') }}-subtle text-{{ $permCount > 40 ? 'success' : ($permCount > 15 ? 'warning' : 'secondary') }}">
                                        <i class="ti ti-key fs-xs me-1"></i> {{ $permCount }} permission(s)
                                    </span>
                                </td>
                                <td data-sort="{{ $role->created_at->format('Y-m-d') }}">
                                    <span class="text-muted">{{ $role->created_at->format('M d, Y') }}</span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('users.role-details', $role->name) }}" title="Manage Permissions">
                                            <i class="ti ti-settings fs-lg"></i>
                                        </a>
                                        @if(!$isSystem)
                                        <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle"
                                            onclick="confirmDelete('{{ $role->name }}')" title="Delete Role">
                                            <i class="ti ti-trash fs-lg"></i>
                                        </button>
                                        <form id="delete-form-{{ $role->name }}" action="{{ route('users.roles.destroy', $role) }}" method="POST" class="d-none">
                                            @csrf
                                            @method('DELETE')
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="roles"></div>
                        <div data-table-pagination=""></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Role Modal -->
    <div class="modal fade" id="createRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('users.roles.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Create New Role</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Role Name</label>
                            <input type="text" name="name" class="form-control" required
                                placeholder="e.g. Manager, Staff L1, Reception"
                                pattern="[a-zA-Z0-9\s\-]+"
                                title="Letters, numbers, spaces, and hyphens only">
                            <small class="text-muted">Use descriptive names like "Manager", "Staff L1", "Reception"</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Copy Permissions From (Optional)</label>
                            <select name="copy_from" class="form-select">
                                <option value="">-- Start with no permissions --</option>
                                @foreach($roles as $r)
                                    <option value="{{ $r->name }}">{{ ucfirst($r->name) }} ({{ $r->permissions->count() }} permissions)</option>
                                @endforeach
                            </select>
                            <small class="text-muted">You can customize permissions after creating the role</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])

    <script>
        function confirmDelete(roleName) {
            Swal.fire({
                title: 'Delete Role?',
                text: `Are you sure you want to permanently delete the role "${roleName}"? This will affect all users assigned to this role.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-danger me-2',
                    cancelButton: 'btn btn-secondary'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + roleName).submit();
                }
            });
        }
    </script>
@endsection
