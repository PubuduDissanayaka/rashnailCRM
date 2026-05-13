@extends('layouts.vertical', ['title' => 'Roles & Permissions'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Roles & Permissions'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title">Roles</h4>
                        <p class="text-muted mb-0">Create and manage staff roles with custom permissions</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createRoleModal">
                        <i class="ti ti-plus me-1"></i> Create Role
                    </button>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-centered">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Users</th>
                                    <th>Permissions</th>
                                    <th>Created</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $role)
                                <tr>
                                    <td>
                                        <span class="fw-semibold">{{ ucfirst($role->name) }}</span>
                                        @if($role->name === 'administrator')
                                            <span class="badge bg-danger ms-1">System</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $role->users->count() }} user(s)</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary">{{ $role->permissions->count() }} permission(s)</span>
                                    </td>
                                    <td>{{ $role->created_at->format('M d, Y') }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('users.role-details', $role->name) }}" class="btn btn-sm btn-outline-primary">
                                            <i class="ti ti-settings me-1"></i> Manage
                                        </a>
                                        @if($role->name !== 'administrator')
                                        <form action="{{ route('users.roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete role \'{{ $role->name }}\'? This cannot be undone.')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
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
