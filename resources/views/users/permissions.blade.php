@extends('layouts.vertical', ['title' => 'User Permissions'])

@section('css')
    @vite(['node_modules/flatpickr/dist/flatpickr.min.css'])
@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => 'Users', 'title' => 'Permissions'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="8">
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search permissions..."
                                type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        <button class="btn btn-danger d-none" data-table-delete-selected="">Delete</button>
                    </div>
                    <!-- Records Per Page -->
                    <div>
                        <select class="form-select form-control my-1 my-md-0" data-table-set-rows-per-page="">
                            <option value="5">5</option>
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="20">20</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th data-table-sort="">Name</th>
                                <th>Assign To</th>
                                <th data-table-sort="">Created Date</th>
                                <th data-table-sort="">Users</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($permissions as $permission)
                            <tr>
                                <td>{{ Str::title(str_replace('_', ' ', $permission->name)) }}</td>
                                <td>
                                    @forelse($permission->roles as $role)
                                    <span class="badge bg-primary-subtle text-primary badge-label fs-xxs fw-semibold">{{ Str::title($role->name) }}</span>
                                    @empty
                                    <span class="badge bg-light-subtle text-muted badge-label fs-xxs fw-semibold">None</span>
                                    @endforelse
                                </td>
                                <td>{{ $permission->created_at->format('d M Y') }}, <span class="text-muted">{{ $permission->created_at->format('g:i a') }}</span></td>
                                <td>{{ $permission->roles->sum(function($role) { return $role->users->count(); }) }}</td>
                                <td class="text-center">
                                    <a class="btn btn-default btn-icon btn-sm" data-bs-target="#editPermissionModal"
                                        data-bs-toggle="modal" href="#"
                                        onclick="loadPermissionData({{ $permission->id }}, '{{ addslashes($permission->name) }}', '{{ $permission->created_at->format('Y-m-d\TH:i') }}', {{ $permission->roles->sum(function($role) { return $role->users->count(); }) }})">
                                        <i class="ti ti-eye fs-lg"></i>
                                    </a>
                                    <a class="btn btn-default btn-icon btn-sm" data-table-delete-row="" href="#"><i
                                            class="ti ti-trash fs-lg"></i></a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">No permissions found</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="permissions"></div>
                        <div data-table-pagination=""></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- end row-->
    <!-- Edit Permission Modal -->
    <div aria-hidden="true" aria-labelledby="editPermissionModalLabel" class="modal fade" id="editPermissionModal"
        tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editPermissionModalLabel">Edit Permission</h5>
                    <button aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"></button>
                </div>
                <form id="editPermissionForm" method="post">
                    @csrf
                    @method('put')
                    <input type="hidden" id="permissionId" name="permissionId" value="">
                    <div class="modal-body">
                        <div class="row g-3">
                            <!-- Permission Name -->
                            <div class="col-md-6">
                                <label class="form-label" for="editPermissionName">Permission Name</label>
                                <input class="form-control" id="editPermissionName" required="" type="text"
                                    name="name" value="" />
                            </div>
                            <!-- Assigned Roles -->
                            <div class="col-md-6">
                                <label class="form-label" for="editAssignedRoles">Assigned To</label>
                                <select class="form-select" id="editAssignedRoles" name="roles[]" multiple="" required="">
                                    @foreach($allRoles as $role)
                                    <option value="{{ $role->name }}">{{ Str::title($role->name) }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple roles</small>
                            </div>
                            <!-- Created Date -->
                            <div class="col-md-6">
                                <label class="form-label" for="editCreatedDate">Created Date</label>
                                <input class="form-control" data-date-format="d M, Y" data-enable-time=""
                                    data-provider="flatpickr" id="editCreatedDate" required="" type="text"
                                    value="" />
                            </div>
                            <!-- Number of Users -->
                            <div class="col-md-6">
                                <label class="form-label" for="editUserCount">Users</label>
                                <input class="form-control" disabled="" id="editUserCount" readonly=""
                                    type="number" value="" />
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-light" data-bs-dismiss="modal" type="button">Cancel</button>
                        <button class="btn btn-primary" type="submit">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js'])
    <script>
        function loadPermissionData(permissionId, permissionName, createdDate, userCount) {
            // Set the form action to the specific permission update route
            document.getElementById('editPermissionForm').action = `/users/permissions/${permissionId}`;

            // Populate the form fields
            document.getElementById('permissionId').value = permissionId;
            document.getElementById('editPermissionName').value = permissionName;
            document.getElementById('editCreatedDate').value = createdDate;
            document.getElementById('editUserCount').value = userCount;

            // You would make an AJAX request to get the roles assigned to this permission
            // and populate the multi-select with those roles
            // For now, we'll just clear any existing selections
            const roleSelect = document.getElementById('editAssignedRoles');
            // Reset selections - would be updated with actual assigned roles in a full implementation
        }
    </script>
@endsection
