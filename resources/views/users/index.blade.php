@extends('layouts.vertical', ['title' => 'User Management'])

@section('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'User Management'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header">
                    <h4 class="card-title">User List</h4>
                    <p class="text-muted mb-0">Manage all users in the system
                    </p>
                </div>
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search users..."
                                type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                        <button class="btn btn-danger d-none" data-table-delete-selected="" 
                            data-bulk-delete-url="{{ route('users.bulk-destroy') }}">Delete</button>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="me-2 fw-semibold">Filter By:</span>
                        <!-- Role Filter -->
                        <div class="app-search">
                            <select class="form-select form-control my-1 my-md-0" data-table-filter="role">
                                <option value="All">Role</option>
                                <option value="Administrator">Administrator</option>
                                <option value="Staff">Staff</option>
                            </select>
                            <i class="app-search-icon text-muted" data-lucide="user"></i>
                        </div>
                        <!-- Status Filter -->
                        <div class="app-search">
                            <select class="form-select form-control my-1 my-md-0" data-table-filter="status">
                                <option value="All">Status</option>
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                                <option value="Suspended">Suspended</option>
                            </select>
                            <i class="app-search-icon text-muted" data-lucide="user-check"></i>
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
                        <!-- Create User Button -->
                        @can('create users')
                        <div>
                            <a href="{{ route('users.create') }}" class="btn btn-primary">
                                <i class="ti ti-user-plus me-1"></i> Create User
                            </a>
                        </div>
                        @endcan
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-select table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th class="ps-3" style="width: 1%;">
                                    <input class="form-check-input form-check-input-light fs-14 mt-0"
                                        data-table-select-all="" type="checkbox" value="option" />
                                </th>
                                <th data-table-sort="sort-name">Name</th>
                                <th data-table-sort="sort-email">Email</th>
                                <th data-column="role" data-table-sort="sort-role">Role</th>
                                <th data-column="status" data-table-sort="sort-status">Status</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                            <tr>
                                <td class="ps-3">
                                    <input class="form-check-input form-check-input-light fs-14 product-item-check mt-0"
                                        type="checkbox" value="{{ $user->id }}" />
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm rounded-circle bg-soft-primary me-2">
                                            <span class="avatar-title rounded-circle text-uppercase">
                                                {{ substr($user->name, 0, 1) }}
                                            </span>
                                        </div>
                                        <div>
                                            <h5 class="fs-base mb-0"><span data-sort="sort-name">{{ $user->name }}</span></h5>
                                        </div>
                                    </div>
                                </td>
                                <td><span data-sort="sort-email">{{ $user->email }}</span></td>
                                <td>
                                    <span data-sort="{{ ucfirst($user->role) }}" class="badge bg-{{ $user->role === 'administrator' ? 'primary' : 'secondary' }}-subtle text-{{ $user->role === 'administrator' ? 'primary' : 'secondary' }}">
                                        {{ ucfirst($user->role) }}
                                    </span>
                                </td>
                                <td>
                                    <span data-sort="{{ ucfirst($user->status) }}" class="badge bg-{{ $user->status === 'active' ? 'success' : ($user->status === 'inactive' ? 'warning' : 'danger') }}-subtle text-{{ $user->status === 'active' ? 'success' : ($user->status === 'inactive' ? 'warning' : 'danger') }}">
                                        {{ ucfirst($user->status) }}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        @can('view users')
                                        <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('users.show', $user->slug) }}" title="View Profile">
                                            <i class="ti ti-eye fs-lg"></i>
                                        </a>
                                        @endcan

                                        @can('edit users')
                                            <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('users.edit', $user) }}" title="Edit User">
                                                <i class="ti ti-edit fs-lg"></i>
                                            </a>
                                        @else
                                            @if($user->id === auth()->id())
                                            <a class="btn btn-light btn-icon btn-sm rounded-circle" href="{{ route('profile.edit') }}" title="Edit Profile">
                                                <i class="ti ti-edit fs-lg"></i>
                                            </a>
                                            @endif
                                        @endcan

                                        @can('delete users')
                                            @if($user->id !== auth()->id())
                                            <button type="button" class="btn btn-danger btn-icon btn-sm rounded-circle" 
                                                data-table-delete-row="" data-delete-url="{{ route('users.destroy', $user->slug) }}" title="Delete User">
                                                <i class="ti ti-trash fs-lg"></i>
                                            </button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div data-table-pagination-info="users"></div>
                        <div data-table-pagination=""></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Check if there are success messages to display
            @if(session('success'))
                Swal.fire({
                    title: 'Success!',
                    text: '{{ session('success') }}',
                    icon: 'success',
                    timer: 3000,
                    showConfirmButton: false
                });
            @endif

            // Check if there are error messages to display
            @if(session('error'))
                Swal.fire({
                    title: 'Error!',
                    text: '{{ session('error') }}',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            @endif
        });
    </script>
@endsection