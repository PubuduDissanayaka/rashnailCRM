@extends('layouts.vertical', ['title' => 'Manage Role: ' . ucfirst($role->name)])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Manage Role: ' . ucfirst($role->name)])

    <div class="row">
        <!-- Permissions Panel -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title">Permissions</h4>
                        <p class="text-muted mb-0">Toggle what this role can do</p>
                    </div>
                    <div>
                        <button class="btn btn-sm btn-outline-primary me-1" onclick="checkAll()">Allow All</button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="uncheckAll()">Disallow All</button>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('users.roles.update', $role) }}" method="POST" id="permissions-form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="name" value="{{ $role->name }}">

                        @php
                            $rolePermissions = $role->permissions->pluck('name')->toArray();
                            $categories = [
                                'Customers' => ['view customers', 'create customers', 'edit customers', 'delete customers'],
                                'Appointments' => ['view appointments', 'create appointments', 'edit appointments', 'delete appointments', 'manage all appointments'],
                                'Services' => ['view services', 'create services', 'edit services', 'delete services'],
                                'Service Packages' => ['view service packages', 'create service packages', 'edit service packages', 'delete service packages'],
                                'POS & Transactions' => ['view pos', 'create pos transactions', 'manage pos', 'process transactions', 'view transactions', 'delete transactions'],
                                'Users & System' => ['view users', 'create users', 'edit users', 'delete users', 'manage system'],
                                'Attendance' => ['view attendances', 'edit attendances', 'manage attendances'],
                                'Work Schedules' => ['view work schedules', 'manage work schedules'],
                                'Work Hour Reports' => ['view work hour reports', 'export work hour reports'],
                                'Leaves' => ['view leave requests', 'create leave requests', 'approve leave requests', 'view leave balances', 'manage leave balances'],
                                'Coupons' => ['view coupons', 'create coupons', 'edit coupons', 'delete coupons', 'manage coupon batches'],
                                'Inventory' => ['inventory.view', 'inventory.manage', 'inventory.supplies.create', 'inventory.supplies.edit', 'inventory.supplies.delete', 'inventory.supplies.adjust', 'inventory.usage.create', 'inventory.purchase.create', 'inventory.purchase.approve', 'inventory.purchase.receive', 'inventory.reports.view', 'inventory.alerts.manage'],
                                'Expenses' => ['expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete', 'expenses.approve', 'expenses.manage'],
                                'Reports' => ['view reports', 'export reports'],
                            ];
                        @endphp

                        @foreach($categories as $category => $perms)
                            @php
                                $existingPerms = array_intersect($perms, $allPermissions->pluck('name')->toArray());
                            @endphp
                            @if(count($existingPerms) > 0)
                            <div class="mb-4">
                                <h6 class="fw-bold mb-2">{{ $category }}</h6>
                                <div class="row">
                                    @foreach($existingPerms as $perm)
                                    <div class="col-md-6 col-lg-4">
                                        <div class="form-check mb-2">
                                            <input class="form-check-input permission-checkbox" type="checkbox"
                                                name="permissions[]" value="{{ $perm }}"
                                                id="perm_{{ Str::slug($perm) }}"
                                                {{ in_array($perm, $rolePermissions) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="perm_{{ Str::slug($perm) }}">
                                                {{ ucwords(str_replace(['.', '_'], ' ', $perm)) }}
                                            </label>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif
                        @endforeach

                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i> Save Permissions
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Users Panel -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Users with this Role</h4>
                    <p class="text-muted mb-0">{{ $role->users->count() }} user(s) assigned</p>
                </div>
                <div class="card-body">
                    @if($role->users->count() > 0)
                        <div class="mb-3">
                            @foreach($role->users as $user)
                                <div class="d-flex align-items-center mb-2 p-2 border rounded">
                                    <div class="flex-grow-1">
                                        <span class="fw-semibold">{{ $user->name }}</span>
                                        <br><small class="text-muted">{{ $user->email }}</small>
                                    </div>
                                    @if($role->name !== 'administrator')
                                    <form action="{{ route('users.roles.update', $role) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="action" value="remove_user">
                                        <input type="hidden" name="remove_user_id" value="{{ $user->id }}">
                                        <button class="btn btn-sm btn-outline-danger" title="Remove from role">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted">No users assigned to this role yet.</p>
                    @endif

                    <hr>

                    <h6 class="fw-bold">Assign Users</h6>
                    <form action="{{ route('users.roles.update', $role) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="action" value="assign_users">

                        <div class="mb-3">
                            <select name="assign_users[]" class="form-select" multiple size="8">
                                @foreach($allUsers as $user)
                                    @if(!$user->hasRole($role->name))
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple users</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-user-plus me-1"></i> Assign Selected Users
                        </button>
                    </form>
                </div>
            </div>

            <!-- Danger Zone -->
            @if($role->name !== 'administrator')
            <div class="card border-danger mt-3">
                <div class="card-header bg-danger bg-opacity-10">
                    <h5 class="card-title text-danger mb-0">Danger Zone</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Deleting this role will remove it from all assigned users. They will lose all associated permissions.</p>
                    <form action="{{ route('users.roles.destroy', $role) }}" method="POST"
                        onsubmit="return confirm('PERMANENTLY delete the role \'{{ $role->name }}\'? This will affect {{ $role->users->count() }} user(s).')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="ti ti-trash me-1"></i> Delete This Role
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    function checkAll() {
        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = true);
    }
    function uncheckAll() {
        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
    }
</script>
@endpush
