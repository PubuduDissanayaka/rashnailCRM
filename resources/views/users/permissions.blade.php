@extends('layouts.vertical', ['title' => 'All Permissions'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'All Permissions'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">System Permissions</h4>
                    <p class="text-muted mb-0">View which roles have each permission</p>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-centered">
                            <thead>
                                <tr>
                                    <th>Permission</th>
                                    <th>Assigned To Roles</th>
                                    <th>Users with Access</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($permissions as $permission)
                                <tr>
                                    <td>
                                        <code>{{ $permission->name }}</code>
                                    </td>
                                    <td>
                                        @if($permission->roles->count() > 0)
                                            @foreach($permission->roles as $role)
                                                <span class="badge bg-primary me-1">{{ $role->name }}</span>
                                            @endforeach
                                        @else
                                            <span class="badge bg-warning">Unassigned</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ $permission->users->count() }} user(s)</span>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="alert alert-info mt-3">
                        <i class="ti ti-info-circle me-1"></i>
                        Permissions are managed through <a href="{{ route('users.roles') }}">Roles</a>.
                        Assign permissions to a role, then assign users to that role.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
