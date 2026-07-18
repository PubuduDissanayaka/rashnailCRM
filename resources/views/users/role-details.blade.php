@extends('layouts.vertical', ['title' => 'Manage Role: ' . ucfirst($role->name)])

@section('css')
    
    <style>
        .perm-card { transition: box-shadow .15s ease; }
        .perm-card:hover { box-shadow: 0 0.125rem 0.5rem rgba(0,0,0,.05); }
        .perm-crud-group { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .perm-crud-btn { font-size: 0.7rem; padding: 0.2rem 0.5rem; border-radius: 4px; cursor: pointer; transition: all .15s ease; }
        .perm-crud-btn:hover { opacity: 0.85; }
        .perm-stat-badge { min-width: 1.5rem; text-align: center; font-size: 0.7rem; }
        #perm-search:focus { border-color: var(--bs-primary); box-shadow: 0 0 0 0.2rem rgba(var(--bs-primary-rgb), .15); }
        .category-header { cursor: pointer; user-select: none; }
        .category-header:hover { opacity: 0.8; }
        .perm-item { transition: background .1s ease; border-radius: 4px; padding: 0.2rem 0.4rem; }
        .perm-item:hover { background: rgba(var(--bs-primary-rgb), .03); }
        .perm-checkbox:checked + label { color: var(--bs-primary); font-weight: 500; }
    </style>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Manage Role: ' . ucfirst($role->name)])

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div>
                        <h4 class="card-title mb-1">
                            <i class="ti ti-shield-lock me-1"></i> Permissions
                        </h4>
                        <p class="text-muted mb-0 small" id="perm-summary">Loading...</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap align-items-center">
                        <div class="app-search" style="max-width: 220px;">
                            <input class="form-control form-control-sm" id="perm-search" type="search" placeholder="Search permissions...">
                            <i class="app-search-icon ti ti-search text-muted"></i>
                        </div>
                        <select class="form-select form-select-sm" id="perm-status-filter" style="width: auto;">
                            <option value="all">All</option>
                            <option value="granted">Granted</option>
                            <option value="denied">Denied</option>
                        </select>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="ti ti-adjustments me-1"></i> Bulk
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#" onclick="setAllCategories(true)"><i class="ti ti-check-all text-success me-2"></i>Allow All</a></li>
                                <li><a class="dropdown-item" href="#" onclick="setAllCategories(false)"><i class="ti ti-x text-danger me-2"></i>Disallow All</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#" onclick="expandAll()"><i class="ti ti-arrows-maximize me-2"></i>Expand All</a></li>
                                <li><a class="dropdown-item" href="#" onclick="collapseAll()"><i class="ti ti-arrows-minimize me-2"></i>Collapse All</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show py-2" role="alert">
                            <i class="ti ti-check me-1"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
                            <i class="ti ti-alert-circle me-1"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <form action="{{ route('users.roles.update', $role) }}" method="POST" id="permissions-form">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="name" value="{{ $role->name }}">
                        <input type="hidden" id="permissions-json" name="permissions" value="">

                        @php
                            $rolePermissions = $role->permissions->pluck('name')->toArray();
                            $allPermNames = $allPermissions->pluck('name')->toArray();

                            // Define CRUD permission categories with icons and colors
                            $categories = [
                                'Customers' => [
                                    'icon' => 'ti ti-users',
                                    'color' => '#4f46e5',
                                    'crud' => [
                                        'View' => ['view customers'],
                                        'Create' => ['create customers'],
                                        'Edit' => ['edit customers'],
                                        'Delete' => ['delete customers'],
                                    ],
                                ],
                                'Appointments' => [
                                    'icon' => 'ti ti-calendar-clock',
                                    'color' => '#0891b2',
                                    'crud' => [
                                        'View' => ['view appointments'],
                                        'Create' => ['create appointments'],
                                        'Edit' => ['edit appointments'],
                                        'Delete' => ['delete appointments', 'manage all appointments'],
                                    ],
                                ],
                                'Services' => [
                                    'icon' => 'ti ti-scissors',
                                    'color' => '#7c3aed',
                                    'crud' => [
                                        'View' => ['view services', 'view service packages'],
                                        'Create' => ['create services', 'create service packages'],
                                        'Edit' => ['edit services', 'edit service packages'],
                                        'Delete' => ['delete services', 'delete service packages'],
                                    ],
                                ],
                                'POS & Transactions' => [
                                    'icon' => 'ti ti-credit-card',
                                    'color' => '#059669',
                                    'crud' => [
                                        'View' => ['view pos', 'view transactions'],
                                        'Create' => ['create pos transactions'],
                                        'Manage' => ['manage pos', 'process transactions', 'delete transactions'],
                                    ],
                                ],
                                'Users & Roles' => [
                                    'icon' => 'ti ti-user-cog',
                                    'color' => '#dc2626',
                                    'crud' => [
                                        'View' => ['view users'],
                                        'Create' => ['create users'],
                                        'Edit' => ['edit users'],
                                        'Delete' => ['delete users'],
                                        'System' => ['manage system'],
                                    ],
                                ],
                                'Attendance' => [
                                    'icon' => 'ti ti-calendar-check',
                                    'color' => '#d97706',
                                    'crud' => [
                                        'View' => ['view attendances'],
                                        'Edit' => ['edit attendances'],
                                        'Manage' => ['manage attendances'],
                                    ],
                                ],
                                'Work Schedules' => [
                                    'icon' => 'ti ti-calendar-range',
                                    'color' => '#0d9488',
                                    'crud' => [
                                        'View' => ['view work schedules'],
                                        'Manage' => ['manage work schedules'],
                                    ],
                                ],
                                'Work Hour Reports' => [
                                    'icon' => 'ti ti-report',
                                    'color' => '#6366f1',
                                    'crud' => [
                                        'View' => ['view work hour reports'],
                                        'Export' => ['export work hour reports'],
                                    ],
                                ],
                                'Leaves' => [
                                    'icon' => 'ti ti-calendar-off',
                                    'color' => '#ea580c',
                                    'crud' => [
                                        'View' => ['view leave requests', 'view leave balances'],
                                        'Create' => ['create leave requests'],
                                        'Approve' => ['approve leave requests'],
                                        'Manage' => ['manage leave balances'],
                                    ],
                                ],
                                'Coupons' => [
                                    'icon' => 'ti ti-ticket',
                                    'color' => '#9333ea',
                                    'crud' => [
                                        'View' => ['view coupons'],
                                        'Create' => ['create coupons'],
                                        'Edit' => ['edit coupons'],
                                        'Delete' => ['delete coupons'],
                                        'Batches' => ['manage coupon batches'],
                                    ],
                                ],
                                'Inventory' => [
                                    'icon' => 'ti ti-package-open',
                                    'color' => '#2563eb',
                                    'crud' => [
                                        'View' => ['inventory.view', 'inventory.reports.view'],
                                        'Create' => ['inventory.supplies.create', 'inventory.usage.create', 'inventory.purchase.create'],
                                        'Edit' => ['inventory.supplies.edit'],
                                        'Delete' => ['inventory.supplies.delete'],
                                        'Manage' => ['inventory.manage', 'inventory.supplies.adjust', 'inventory.purchase.approve', 'inventory.purchase.receive', 'inventory.alerts.manage'],
                                    ],
                                ],
                                'Expenses' => [
                                    'icon' => 'ti ti-wallet',
                                    'color' => '#ca8a04',
                                    'crud' => [
                                        'View' => ['expenses.view'],
                                        'Create' => ['expenses.create'],
                                        'Edit' => ['expenses.edit'],
                                        'Delete' => ['expenses.delete'],
                                        'Approve' => ['expenses.approve'],
                                        'Manage' => ['expenses.manage'],
                                    ],
                                ],
                                'Reports' => [
                                    'icon' => 'ti ti-bar-chart-2',
                                    'color' => '#0891b2',
                                    'crud' => [
                                        'View' => ['view reports'],
                                        'Export' => ['export reports'],
                                    ],
                                ],
                            ];
                        @endphp

                        <div id="permissions-container">
                            @foreach($categories as $category => $config)
                                @php
                                    // Flatten and check which perms exist in the system
                                    $allCatPerms = collect($config['crud'])->flatten()->toArray();
                                    $existingPerms = array_intersect($allCatPerms, $allPermNames);
                                    $grantedPerms = array_intersect($existingPerms, $rolePermissions);
                                    $grantedCount = count($grantedPerms);
                                    $totalCount = count($existingPerms);
                                    $allGranted = $grantedCount === $totalCount && $totalCount > 0;
                                    $noneGranted = $grantedCount === 0;
                                @endphp

                                @if($totalCount > 0)
                                <div class="card perm-card mb-3 border" data-category="{{ $category }}" x-data="{ collapsed: false }">
                                    <div class="card-header py-2 px-3 category-header d-flex align-items-center justify-content-between flex-wrap gap-2"
                                         onclick="toggleCategory(this)"
                                         style="border-left: 3px solid {{ $config['color'] }};">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="{{ $config['icon'] }}" style="color: {{ $config['color'] }};"></i>
                                            <h6 class="mb-0 fs-sm fw-semibold">{{ $category }}</h6>
                                            <span class="badge perm-stat-badge bg-{{ $allGranted ? 'success' : ($noneGranted ? 'secondary' : 'primary') }} rounded-pill">
                                                {{ $grantedCount }}/{{ $totalCount }}
                                            </span>
                                            <i class="ti ti-chevron-down collapse-indicator fs-xs text-muted"></i>
                                        </div>
                                        <div class="d-flex gap-1" onclick="event.stopPropagation()">
                                            @foreach($config['crud'] as $action => $perms)
                                                @php
                                                    $actionPerms = array_intersect($perms, $allPermNames);
                                                    $actionGranted = count(array_intersect($actionPerms, $rolePermissions)) === count($actionPerms) && count($actionPerms) > 0;
                                                @endphp
                                                @if(count($actionPerms) > 0)
                                                <span class="perm-crud-btn badge bg-{{ $actionGranted ? 'primary' : 'light' }} text-{{ $actionGranted ? 'white' : 'secondary' }} border"
                                                      data-action="{{ $category }}:{{ $action }}"
                                                      onclick="toggleCrudGroup('{{ $category }}', '{{ $action }}', this)"
                                                      title="Toggle {{ $action }} permissions">
                                                    {{ $action }}
                                                </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="card-body py-2 perm-body">
                                        <div class="row">
                                            @foreach($existingPerms as $perm)
                                                @php
                                                    $permSlug = Str::slug($perm);
                                                    $isChecked = in_array($perm, $rolePermissions);
                                                    $displayName = ucwords(str_replace(['.', '_'], ' ', preg_replace('/^(view|create|edit|delete|manage|approve|export|process)\s+/', '$1 ', $perm)));
                                                @endphp
                                                <div class="col-md-6 col-lg-4 perm-item">
                                                    <div class="form-check mb-1">
                                                        <input class="form-check-input perm-checkbox" type="checkbox"
                                                               name="permissions[]" value="{{ $perm }}"
                                                               id="perm_{{ $permSlug }}"
                                                               data-category="{{ $category }}"
                                                               data-action="{{ $perm }}"
                                                               {{ $isChecked ? 'checked' : '' }}
                                                               onchange="updateCounts()">
                                                        <label class="form-check-label small" for="perm_{{ $permSlug }}">
                                                            {{ $displayName }}
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @endif
                            @endforeach
                        </div>

                        <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-2">
                            <div>
                                <span class="text-muted small" id="footer-perm-count">0 permissions selected</span>
                            </div>
                            <div>
                                <a href="{{ route('users.roles') }}" class="btn btn-light me-2">
                                    <i class="ti ti-arrow-left me-1"></i> Back
                                </a>
                                <button type="submit" class="btn btn-primary" id="save-btn">
                                    <i class="ti ti-device-floppy me-1"></i> Save Permissions
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title mb-1">
                        <i class="ti ti-users me-1"></i> Assigned Users
                    </h5>
                    <p class="text-muted mb-0 small">{{ $role->users->count() }} user(s) with this role</p>
                </div>
                <div class="card-body">
                    @if($role->users->count() > 0)
                        <div class="mb-3">
                            @foreach($role->users as $user)
                                <div class="d-flex align-items-center mb-2 p-2 border rounded">
                                    <div class="avatar-xs rounded-circle bg-soft-primary me-2 d-flex align-items-center justify-content-center" style="width:32px;height:32px;">
                                        <span class="fw-semibold small text-primary">{{ substr($user->name, 0, 1) }}</span>
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <h6 class="mb-0 small text-truncate">{{ $user->name }}</h6>
                                        <small class="text-muted text-truncate d-block">{{ $user->email }}</small>
                                    </div>
                                    @if($role->name !== 'administrator')
                                    <form action="{{ route('users.roles.update', $role) }}" method="POST" class="d-inline ms-1">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="action" value="remove_user">
                                        <input type="hidden" name="remove_user_id" value="{{ $user->id }}">
                                        <button class="btn btn-sm btn-outline-danger border-0" title="Remove from role">
                                            <i class="ti ti-x"></i>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-3 text-muted">
                            <i class="ti ti-users fs-28 mb-1 d-block"></i>
                            <small>No users assigned yet.</small>
                        </div>
                    @endif

                    <hr class="my-3">

                    <h6 class="fw-semibold small mb-2">
                        <i class="ti ti-user-plus me-1"></i> Assign Users
                    </h6>
                    <form action="{{ route('users.roles.update', $role) }}" method="POST">
                        @csrf @method('PUT')
                        <input type="hidden" name="action" value="assign_users">
                        <div class="mb-2">
                            <select name="assign_users[]" class="form-select form-select-sm" multiple size="5">
                                @foreach($allUsers as $user)
                                    @if(!$user->hasRole($role->name))
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="ti ti-user-plus me-1"></i> Assign
                        </button>
                    </form>
                </div>
            </div>

            @if($role->name !== 'administrator')
            <div class="card border-danger mt-3">
                <div class="card-header border-danger bg-danger bg-opacity-10 py-2">
                    <h6 class="card-title text-danger mb-0 small fw-semibold">
                        <i class="ti ti-alert-triangle me-1"></i> Danger Zone
                    </h6>
                </div>
                <div class="card-body py-3">
                    <p class="text-muted small mb-2">Delete this role permanently ({{ $role->users->count() }} user(s) affected).</p>
                    <form action="{{ route('users.roles.destroy', $role) }}" method="POST"
                        onsubmit="return confirm('PERMANENTLY delete the role \'{{ $role->name }}\'? This cannot be undone.')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm w-100">
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
    function updateCounts() {
        const total = document.querySelectorAll('.perm-checkbox').length;
        const checked = document.querySelectorAll('.perm-checkbox:checked').length;
        document.getElementById('perm-summary').textContent = `${checked} of ${total} permissions granted`;
        document.getElementById('footer-perm-count').textContent = `${checked} of ${total} permissions selected`;

        // Update category badges
        document.querySelectorAll('[data-category]').forEach(card => {
            const cat = card.dataset.category;
            const checkboxes = card.querySelectorAll('.perm-checkbox');
            const checkedBoxes = card.querySelectorAll('.perm-checkbox:checked');
            const badge = card.querySelector('.perm-stat-badge');
            if (badge) {
                const count = checkedBoxes.length;
                const totalCount = checkboxes.length;
                badge.textContent = `${count}/${totalCount}`;
                badge.className = `badge perm-stat-badge rounded-pill bg-${count === totalCount && totalCount > 0 ? 'success' : (count === 0 ? 'secondary' : 'primary')}`;
            }

            // Update CRUD action badges
            card.querySelectorAll('[data-action]').forEach(btn => {
                const action = btn.dataset.action;
                const [catName, actionName] = action.split(':');
                // Find all perms belonging to this action within this category
                const perms = @json($categories);
                const actionPerms = perms[catName]?.crud[actionName] || [];
                const checkboxesForAction = actionPerms.map(p => card.querySelector(`#perm_${p.replace(/[._]/g, '-')}`)).filter(Boolean);
                const allChecked = checkboxesForAction.length > 0 && checkboxesForAction.every(cb => cb.checked);
                btn.className = `perm-crud-btn badge bg-${allChecked ? 'primary' : 'light'} text-${allChecked ? 'white' : 'secondary'} border`;
            });
        });
    }

    function toggleCrudGroup(category, action, btn) {
        const perms = @json($categories);
        const actionPerms = perms[category]?.crud[action] || [];
        const card = btn.closest('[data-category]');
        const checkboxes = actionPerms
            .map(p => card.querySelector(`#perm_${p.replace(/[._]/g, '-')}`))
            .filter(Boolean);
        const allChecked = checkboxes.every(cb => cb.checked);
        checkboxes.forEach(cb => cb.checked = !allChecked);
        updateCounts();
    }

    function setAllCategories(granted) {
        document.querySelectorAll('.perm-checkbox').forEach(cb => cb.checked = granted);
        updateCounts();
    }

    function toggleCategory(header) {
        const body = header.closest('.card').querySelector('.perm-body');
        const indicator = header.querySelector('.collapse-indicator');
        if (body.style.display === 'none') {
            body.style.display = '';
            indicator.style.transform = 'rotate(0deg)';
        } else {
            body.style.display = 'none';
            indicator.style.transform = 'rotate(-90deg)';
        }
    }

    function expandAll() {
        document.querySelectorAll('.perm-body').forEach(b => b.style.display = '');
        document.querySelectorAll('.collapse-indicator').forEach(i => i.style.transform = 'rotate(0deg)');
    }

    function collapseAll() {
        document.querySelectorAll('.perm-body').forEach(b => b.style.display = 'none');
        document.querySelectorAll('.collapse-indicator').forEach(i => i.style.transform = 'rotate(-90deg)');
    }

    // Search/filter
    document.getElementById('perm-search')?.addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.perm-item').forEach(item => {
            const label = item.querySelector('label')?.textContent.toLowerCase() || '';
            item.style.display = label.includes(q) ? '' : 'none';
        });
        // Hide empty categories
        document.querySelectorAll('[data-category]').forEach(card => {
            const visible = card.querySelectorAll('.perm-item[style*=\"display: none\"]').length < card.querySelectorAll('.perm-item').length;
            card.style.display = visible ? '' : 'none';
        });
    });

    // Status filter
    document.getElementById('perm-status-filter')?.addEventListener('change', function() {
        const val = this.value;
        document.querySelectorAll('.perm-item').forEach(item => {
            const cb = item.querySelector('.perm-checkbox');
            if (val === 'all') item.style.display = '';
            else if (val === 'granted') item.style.display = cb.checked ? '' : 'none';
            else if (val === 'denied') item.style.display = !cb.checked ? '' : 'none';
        });
        document.querySelectorAll('[data-category]').forEach(card => {
            const visible = card.querySelectorAll('.perm-item[style*=\"display: none\"]').length < card.querySelectorAll('.perm-item').length;
            card.style.display = visible ? '' : 'none';
        });
    });

    // Serialize all permissions to hidden input on form submit
    document.getElementById('permissions-form')?.addEventListener('submit', function() {
        const checked = [];
        document.querySelectorAll('.perm-checkbox:checked').forEach(cb => checked.push(cb.value));
        document.getElementById('permissions-json').value = JSON.stringify(checked);
    });

    // Initialize
    document.addEventListener('DOMContentLoaded', updateCounts);
</script>
@endpush
