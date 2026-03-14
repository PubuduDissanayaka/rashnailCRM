@extends('layouts.vertical', ['title' => 'Role Details - ' . $role->name])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-sm-center flex-sm-row flex-column my-3">
                <div class="flex-grow-1">
                    <h4 class="fs-xl mb-1">Role Details</h4>
                    <p class="text-muted mb-0">Manage role permissions and assigned users for {{ $role->name }}.</p>
                </div>
                <div class="text-end mt-3 mt-sm-0">
                    <a class="btn btn-secondary" href="{{ route('users.roles') }}">
                        <i class="ti ti-arrow-left me-1"></i> Back to Roles
                    </a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 col-lg-3">
                    <div class="card">
                        <div class="position-absolute top-0 end-0" style="width: 180px;">
                            <svg fill="none" height="560" style="opacity: 0.075; width: 100%; height: auto;"
                                viewbox="0 0 600 560" width="600" xmlns="http://www.w3.org/2000/svg">
                                <g clip-path="url(#clip0_948_1464)">
                                    <mask height="1200" id="mask0_948_1464" maskunits="userSpaceOnUse"
                                        style="mask-type:luminance" width="600" x="0" y="0">
                                        <path d="M0 0L0 1200H600L600 0H0Z" fill="white"></path>
                                    </mask>
                                    <g mask="url(#mask0_948_1464)">
                                        <path d="M537.448 166.697L569.994 170.892L550.644 189.578L537.448 166.697Z"
                                            fill="#FF4C3E"></path>
                                    </g>
                                    <g mask="url(#mask1_948_1464)">
                                        <path
                                            d="M403.998 311.555L372.211 343.342C361.79 353.763 344.894 353.763 334.473 343.342L302.686 311.555C292.265 301.134 292.265 284.238 302.686 273.817L334.473 242.03C344.894 231.609 361.79 231.609 372.211 242.03L403.998 273.817C414.419 284.238 414.419 301.134 403.998 311.555Z"
                                            fill="#089df1"></path>
                                        <path
                                            d="M714.621 64.24L541.575 237.286C525.986 252.875 500.711 252.875 485.122 237.286L312.076 64.24C296.487 48.651 296.487 23.376 312.076 7.787L485.122 -165.259C500.711 -180.848 525.986 -180.848 541.575 -165.259L714.621 7.787C730.21 23.377 730.21 48.651 714.621 64.24Z"
                                            fill="#f9bf59"></path>
                                    </g>
                                </g>
                            </svg>
                        </div>
                        <div class="card-body d-flex flex-column justify-content-between">
                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0">
                                    <div
                                        class="avatar-xl rounded {{ $role->name === 'administrator' ? 'bg-danger-subtle' : 'bg-primary-subtle' }} d-flex align-items-center justify-content-center">
                                        <i class="ti ti-shield-{{ $role->name === 'administrator' ? 'star' : 'check' }} fs-24 {{ $role->name === 'administrator' ? 'text-danger' : 'text-primary' }}"></i>
                                    </div>
                                </div>
                                <div class="ms-3">
                                    <h5 class="mb-1 text-capitalize">{{ $role->name }}</h5>
                                    <p class="text-muted mb-0 fs-base">
                                        @if($role->name === 'administrator')
                                            Full system access and management
                                        @elseif($role->name === 'staff')
                                            Limited access for staff operations
                                        @else
                                            Custom role with specific permissions
                                        @endif
                                    </p>
                                </div>
                                <div class="ms-auto">
                                    <div class="dropdown">
                                        <a class="text-muted fs-xl" data-bs-toggle="dropdown" href="#">
                                            <i class="ti ti-dots-vertical"></i>
                                        </a>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="#" data-bs-target="#editRoleModal" data-bs-toggle="modal">
                                                    <i class="ti ti-edit me-2"></i>Edit Role
                                                </a>
                                            </li>
                                            @if(!in_array($role->name, ['administrator', 'staff']))
                                            <form id="delete-role-form-{{ $role->id }}" action="{{ route('users.roles.destroy', $role) }}" method="POST" style="display: none;">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" onclick="confirmDeleteRole({{ $role->id }}, '{{ $role->name }}')">
                                                    <i class="ti ti-trash me-2"></i>Delete Role
                                                </button>
                                            </li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <h6 class="mb-2">Permissions ({{ $role->permissions->count() }})</h6>
                            <ul class="list-unstyled mb-3" style="max-height: 200px; overflow-y: auto;">
                                @forelse($role->permissions->take(8) as $permission)
                                <li class="d-flex align-items-center mb-2">
                                    <span class="ti ti-check fs-lg text-success me-2"></span>
                                    <span class="text-capitalize">{{ str_replace('_', ' ', $permission->name) }}</span>
                                </li>
                                @empty
                                <li class="text-muted">No permissions assigned</li>
                                @endforelse
                                @if($role->permissions->count() > 8)
                                <li class="text-muted">+ {{ $role->permissions->count() - 8 }} more...</li>
                                @endif
                            </ul>

                            <p class="mb-2 text-muted">Total {{ $role->users->count() }} {{ Str::plural('user', $role->users->count()) }}</p>
                            <div class="avatar-group avatar-group-sm mb-3">
                                @foreach($role->users->take(4) as $user)
                                <div class="avatar" data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $user->name }}">
                                    <img alt="{{ $user->name }}" class="rounded-circle avatar-sm"
                                        src="{{ $user->avatar ? asset('storage/avatars/' . $user->avatar) : '/images/users/user-3.jpg' }}" />
                                </div>
                                @endforeach
                                @if($role->users->count() > 4)
                                <div class="avatar avatar-sm" data-bs-placement="top" data-bs-toggle="tooltip"
                                    title="{{ $role->users->count() - 4 }} More">
                                    <span class="avatar-title text-bg-primary rounded-circle fw-bold">
                                        +{{ $role->users->count() - 4 }}
                                    </span>
                                </div>
                                @endif
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted fs-xs"><i class="ti ti-clock me-1"></i>Updated {{ $role->updated_at->diffForHumans() }}</span>
                                <a class="btn btn-sm btn-outline-primary rounded-pill" data-bs-target="#editRoleModal"
                                    data-bs-toggle="modal" href="#">Edit Role</a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-8 col-lg-9">
                    <div class="card">
                        <div class="card-header border-light justify-content-between">
                            <h5 class="card-title mb-0">Users with {{ $role->name }} role</h5>
                            <button class="btn btn-secondary" data-bs-target="#assignUserModal" data-bs-toggle="modal"
                                type="button">
                                <i class="ti ti-user-plus me-1"></i> Assign User
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-hover w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Joined Date</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($role->users as $roleUser)
                                    <tr>
                                        <td>
                                            <h5 class="m-0">#{{ str_pad($roleUser->id, 5, '0', STR_PAD_LEFT) }}</h5>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="avatar avatar-sm">
                                                    @if($roleUser->avatar)
                                                    <img alt="{{ $roleUser->name }}" class="img-fluid rounded-circle"
                                                        src="{{ asset('storage/avatars/' . $roleUser->avatar) }}" />
                                                    @else
                                                    <span class="avatar-title rounded-circle bg-primary text-white">
                                                        {{ strtoupper(substr($roleUser->name, 0, 1)) }}
                                                    </span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <h5 class="fs-base mb-0">
                                                        <a class="link-reset" href="{{ route('users.show', $roleUser->slug) }}">
                                                            {{ $roleUser->name }}
                                                        </a>
                                                    </h5>
                                                </div>
                                            </div>
                                        </td>
                                        <td>{{ $roleUser->email }}</td>
                                        <td>{{ $roleUser->phone ?? 'N/A' }}</td>
                                        <td>{{ $roleUser->created_at->format('d M, Y') }}</td>
                                        <td class="text-center">
                                            <div class="d-flex justify-content-center gap-1">
                                                <a class="btn btn-default btn-icon btn-sm" href="{{ route('users.show', $roleUser->slug) }}" title="View Profile">
                                                    <i class="ti ti-eye fs-lg"></i>
                                                </a>
                                                @if(auth()->user()->can('edit users'))
                                                <a class="btn btn-default btn-icon btn-sm" href="{{ route('users.edit', $roleUser->slug) }}" title="Edit User">
                                                    <i class="ti ti-edit fs-lg"></i>
                                                </a>
                                                @endif
                                                @if(auth()->user()->can('delete users') && $roleUser->id !== auth()->id())
                                                <form action="{{ route('users.destroy', $roleUser->name) }}" method="POST" class="d-inline"
                                                    onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-default btn-icon btn-sm" title="Delete User">
                                                        <i class="ti ti-trash fs-lg"></i>
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="text-muted">
                                                <i class="ti ti-users fs-24 mb-2 d-block"></i>
                                                No users assigned to this role yet.
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div> <!-- end row-->
        </div> <!-- end col-->
    </div> <!-- end row-->

    <!-- Edit Role Modal -->
    <div aria-hidden="true" aria-labelledby="editRoleModalLabel" class="modal fade" id="editRoleModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editRoleModalLabel">Edit Role: {{ $role->name }}</h5>
                    <button aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"></button>
                </div>
                <form action="{{ route('users.roles.update', $role) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="editRoleName">Role Name</label>
                                <input class="form-control" id="editRoleName" name="name" required type="text"
                                    value="{{ $role->name }}" {{ in_array($role->name, ['administrator', 'staff']) ? 'readonly' : '' }} />
                                @if(in_array($role->name, ['administrator', 'staff']))
                                <small class="text-muted">Default roles cannot be renamed</small>
                                @endif
                            </div>
                            <div class="col-12">
                                <label class="form-label">Permissions</label>
                                <div class="row">
                                    @foreach($allPermissions->chunk(ceil($allPermissions->count() / 3)) as $permissionChunk)
                                    <div class="col-md-4">
                                        @foreach($permissionChunk as $permission)
                                        <div class="form-check mb-2">
                                            <input class="form-check-input" type="checkbox" name="permissions[]"
                                                value="{{ $permission->id }}" id="perm_{{ $permission->id }}"
                                                {{ $role->permissions->contains($permission->id) ? 'checked' : '' }}>
                                            <label class="form-check-label text-capitalize" for="perm_{{ $permission->id }}">
                                                {{ str_replace('_', ' ', $permission->name) }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-light" data-bs-dismiss="modal" type="button">Cancel</button>
                        <button class="btn btn-primary" type="submit">
                            <i class="ti ti-device-floppy me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign User Modal -->
    <div aria-hidden="true" aria-labelledby="assignUserModalLabel" class="modal fade" id="assignUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="assignUserModalLabel">Assign User to {{ $role->name }}</h5>
                    <button aria-label="Close" class="btn-close" data-bs-dismiss="modal" type="button"></button>
                </div>
                <form action="{{ route('users.roles.update', $role) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="action" value="assign_users">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Users</label>
                            @php
                                $availableUsers = $allUsers->reject(function($u) use ($role) {
                                    return $role->users->contains($u->id);
                                });
                            @endphp

                            @if($availableUsers->count() > 0)
                            <div style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px;">
                                @foreach($availableUsers as $availableUser)
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="checkbox" name="assign_users[]"
                                        value="{{ $availableUser->id }}" id="user_{{ $availableUser->id }}">
                                    <label class="form-check-label d-flex align-items-center" for="user_{{ $availableUser->id }}">
                                        <span class="ms-2">{{ $availableUser->name }} ({{ $availableUser->email }})</span>
                                    </label>
                                </div>
                                @endforeach
                            </div>
                            @else
                            <p class="text-muted mb-0">All users are already assigned to this role.</p>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-light" data-bs-dismiss="modal" type="button">Cancel</button>
                        @if($availableUsers->count() > 0)
                        <button class="btn btn-primary" type="submit">
                            <i class="ti ti-check me-1"></i> Assign Users
                        </button>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Success/Error Messages --}}
    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            alert('{{ session('success') }}');
        });
    </script>
    @endif

    @if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            alert('{{ session('error') }}');
        });
    </script>
    @endif
@endsection

@section('scripts')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        // Check if there are success messages to display
        @if(session('success'))
            Swal.fire({
                title: 'Success!',
                text: '{{ session('success') }}',
                icon: 'success',
                confirmButtonClass: 'btn btn-primary'
            });
        @endif

        // Check if there are error messages to display
        @if(session('error'))
            Swal.fire({
                title: 'Error!',
                text: '{{ session('error') }}',
                icon: 'error',
                confirmButtonClass: 'btn btn-primary'
            });
        @endif

        // Confirm delete role function
        function confirmDeleteRole(roleId, roleName) {
            Swal.fire({
                title: 'Confirm Deletion',
                text: `Are you sure you want to delete the role "${roleName}"?`,
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
                    // Submit the form for the specified role
                    const formId = 'delete-role-form-' + roleId;
                    if (document.getElementById(formId)) {
                        document.getElementById(formId).submit();
                    } else {
                        // Create form dynamically if it doesn't exist
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = `/users/roles/${roleId}`;

                        const csrfToken = document.createElement('input');
                        csrfToken.type = 'hidden';
                        csrfToken.name = '_token';
                        csrfToken.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content') || '{{ csrf_token() }}';

                        const methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = 'DELETE';

                        form.appendChild(csrfToken);
                        form.appendChild(methodInput);
                        document.body.appendChild(form);
                        form.submit();
                    }
                }
            });
        }
    </script>
@endsection
