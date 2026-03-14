<!-- Topbar Start -->
<header class="app-topbar">
    <div class="container-fluid topbar-menu">
        <div class="d-flex align-items-center gap-2">
            <!-- Topbar Brand Logo -->
            <div class="logo-topbar">
                <!-- Logo light -->
                <a class="logo-light" href="{{ route('second', ['dashboard', 'index']) }}">
                    <span class="logo-lg">
                        <img alt="logo" src="{{ $appLogoUrl }}" />
                    </span>
                    <span class="logo-sm">
                        <img alt="small logo" src="{{ $appLogoSmUrl }}" />
                    </span>
                </a>
                <!-- Logo Dark -->
                <a class="logo-dark" href="{{ route('second', ['dashboard', 'index']) }}">
                    <span class="logo-lg">
                        <img alt="dark logo" src="{{ $appLogoDarkUrl }}" />
                    </span>
                    <span class="logo-sm">
                        <img alt="small logo" src="{{ $appLogoSmUrl }}" />
                    </span>
                </a>
            </div>
            <!-- Sidebar Menu Toggle Button -->
            <button class="sidenav-toggle-button btn btn-default btn-icon">
                <i class="ti ti-menu-4 fs-22"></i>
            </button>
            <!-- Horizontal Menu Toggle Button -->
            <button class="topnav-toggle-button px-2" data-bs-target="#topnav-menu-content" data-bs-toggle="collapse">
                <i class="ti ti-menu-4 fs-22"></i>
            </button>
            <!-- Mega Menu Dropdown -->
            <div class="topbar-item d-none d-md-flex">
                <div class="dropdown">
                    <button class="topbar-link btn fw-medium btn-link dropdown-toggle drop-arrow-none" data-lang="mega-menu" data-bs-toggle="dropdown" data-bs-offset="0,17" type="button" aria-haspopup="false" aria-expanded="false">
                        Mega Menu <i class="ti ti-chevron-down ms-1 fs-16"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-xxl p-0">
                        <div class="h-100" data-simplebar="" style="max-height: 380px;">
                            <div class="row g-0">
                                <!-- Dashboard & Analytics -->
                                <div class="col-md-4">
                                    <div class="p-2">
                                        <h5 class="mb-1 fw-semibold fs-sm dropdown-header">Dashboard &amp; Analytics
                                        </h5>
                                        <ul class="list-unstyled megamenu-list">
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-chart-line align-middle me-2 fs-16"></i> Sales
                                                    Dashboard</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-bulb align-middle me-2 fs-16"></i> Marketing
                                                    Dashboard</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-currency-dollar align-middle me-2 fs-16"></i>
                                                    Finance Overview</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-users align-middle me-2 fs-16"></i> User
                                                    Analytics</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-activity align-middle me-2 fs-16"></i> Traffic
                                                    Insights</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-gauge align-middle me-2 fs-16"></i> Performance
                                                    Metrics</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-zoom-check align-middle me-2 fs-16"></i> Conversion
                                                    Tracking</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <!-- Project Management -->
                                <div class="col-md-4">
                                    <div class="p-2">
                                        <h5 class="mb-1 fw-semibold fs-sm dropdown-header">Project Management</h5>
                                        <ul class="list-unstyled megamenu-list">
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-layout-kanban align-middle me-2 fs-16"></i> Kanban
                                                    Workflow</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-calendar-stats align-middle me-2 fs-16"></i>
                                                    Project Timeline</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-list-check align-middle me-2 fs-16"></i> Task
                                                    Management</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-users-group align-middle me-2 fs-16"></i> Team
                                                    Members</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-clipboard-list align-middle me-2 fs-16"></i>
                                                    Assignments</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-chart-pie align-middle me-2 fs-16"></i> Resource
                                                    Allocation</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-file-invoice align-middle me-2 fs-16"></i> Project
                                                    Reports</a></li>
                                        </ul>
                                    </div>
                                </div>
                                <!-- User Management -->
                                <div class="col-md-4">
                                    <div class="p-2">
                                        <h5 class="mb-1 fw-semibold fs-sm dropdown-header">User Management</h5>
                                        <ul class="list-unstyled megamenu-list">
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-user-circle align-middle me-2 fs-16"></i> User
                                                    Profiles</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-lock me-2 align-middle fs-16"></i> Access
                                                    Control</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-shield-lock align-middle me-2 fs-16"></i> Role
                                                    Permissions</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-notes align-middle me-2 fs-16"></i> Activity
                                                    Logs</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-settings align-middle me-2 fs-16"></i> Security
                                                    Settings</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-users align-middle me-2 fs-16"></i> User
                                                    Groups</a></li>
                                            <li><a class="dropdown-item" href="javascript:void(0);"><i
                                                        class="ti ti-key align-middle me-2 fs-16"></i> Authentication
                                                </a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> <!-- .d-flex-->
        <div class="d-flex align-items-center gap-2">
            <!-- Search -->
            <div class="app-search d-none d-xl-flex me-2">
                <input class="form-control topbar-search rounded-pill" name="search" placeholder="Quick Search..."
                    type="search" />
                <i class="app-search-icon text-muted" data-lucide="search"></i>
            </div>
            <!-- POS Terminal Shortcut -->
            @can('manage system')
            <div class="topbar-item d-none d-sm-flex">
                <a href="{{ route('pos.index') }}" class="topbar-link btn btn-primary btn-sm px-3 fw-semibold" title="Open POS Terminal">
                    <i class="ti ti-device-desktop me-1 fs-16 align-middle"></i>
                    <span class="d-none d-md-inline">POS</span>
                </a>
            </div>
            <div class="topbar-item d-none d-sm-flex">
                <a href="{{ route('coupons.create') }}" class="topbar-link btn btn-success btn-sm px-3 fw-semibold" title="Create Coupon">
                    <i class="ti ti-ticket me-1 fs-16 align-middle"></i>
                    <span class="d-none d-md-inline">Coupon</span>
                </a>
            </div>
            @endcan
            {{-- Language Dropdown --}}
            {{-- <div class="topbar-item">
                <div class="dropdown">
                    <button aria-expanded="false" aria-haspopup="false" class="topbar-link fw-bold"
                        data-bs-offset="0,24" data-bs-toggle="dropdown" type="button">
                        <img alt="user-image" class="rounded" height="20" id="selected-language-image"
                            src="/images/flags/us.svg" />
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" data-translator-lang="en" href="javascript:void(0);"
                            title="English">
                            <img alt="English" class="me-1 rounded" data-translator-image="" height="18"
                                src="/images/flags/us.svg" />
                            <span class="align-middle">English</span>
                        </a>
                        <a class="dropdown-item" data-translator-lang="de" href="javascript:void(0);"
                            title="German">
                            <img alt="German" class="me-1 rounded" data-translator-image="" height="18"
                                src="/images/flags/de.svg" />
                            <span class="align-middle">Deutsch</span>
                        </a>
                        <a class="dropdown-item" data-translator-lang="it" href="javascript:void(0);"
                            title="Italian">
                            <img alt="Italian" class="me-1 rounded" data-translator-image="" height="18"
                                src="/images/flags/it.svg" />
                            <span class="align-middle">Italiano</span>
                        </a>
                        <a class="dropdown-item" data-translator-lang="es" href="javascript:void(0);"
                            title="Spanish">
                            <img alt="Spanish" class="me-1 rounded" data-translator-image="" height="18"
                                src="/images/flags/es.svg" />
                            <span class="align-middle">Español</span>
                        </a>
                        <a class="dropdown-item" data-translator-lang="ru" href="javascript:void(0);"
                            title="Russian">
                            <img alt="Russian" class="me-1 rounded" data-translator-image="" height="18"
                                src="/images/flags/ru.svg" />
                            <span class="align-middle">Русский</span>
                        </a>
                        <a class="dropdown-item" data-translator-lang="hi" href="javascript:void(0);"
                            title="Hindi">
                            <img alt="Hindi" class="me-1 rounded" data-translator-image="" height="18"
                                src="/images/flags/in.svg" />
                            <span class="align-middle">हिन्दी</span>
                        </a>
                    </div> <!-- end dropdown-menu-->
                </div> <!-- end dropdown-->
            </div> --}} <!-- end topbar item-->
            <!-- Notification Dropdown -->
            @php
                use App\Models\Notification as UserNotification;
                $topbarNotifs = auth()->check()
                    ? UserNotification::where('notifiable_type', 'App\Models\User')
                        ->where('notifiable_id', auth()->id())
                        ->orderByDesc('created_at')
                        ->limit(10)
                        ->get()
                    : collect();
                $topbarUnread = $topbarNotifs->whereNull('read_at')->count();

                // Helper: resolve icon + color from notification type
                if (!function_exists('notiIconConfig')) {
                    function notiIconConfig(string $type): array {
                        return match(true) {
                            str_contains($type, 'check_in')        => ['icon' => 'ti-login',         'color' => 'success'],
                            str_contains($type, 'check_out')       => ['icon' => 'ti-logout',        'color' => 'info'],
                            str_contains($type, 'late')            => ['icon' => 'ti-clock',         'color' => 'warning'],
                            str_contains($type, 'early_departure') => ['icon' => 'ti-clock-off',     'color' => 'warning'],
                            str_contains($type, 'overtime')        => ['icon' => 'ti-clock-hour-4',  'color' => 'primary'],
                            str_contains($type, 'report')          => ['icon' => 'ti-file-report',   'color' => 'info'],
                            str_contains($type, 'expense')         => ['icon' => 'ti-wallet',        'color' => 'warning'],
                            str_contains($type, 'appointment')     => ['icon' => 'ti-calendar-check','color' => 'primary'],
                            str_contains($type, 'leave')           => ['icon' => 'ti-beach',         'color' => 'secondary'],
                            str_contains($type, 'stock')           => ['icon' => 'ti-alert-triangle','color' => 'danger'],
                            str_contains($type, 'inventory')       => ['icon' => 'ti-package',       'color' => 'danger'],
                            default                                => ['icon' => 'ti-bell',           'color' => 'secondary'],
                        };
                    }
                }

                // Helper: build readable title from notification data
                if (!function_exists('notiTitle')) {
                    function notiTitle(string $type, array $data): string {
                        return match(true) {
                            str_contains($type, 'check_in')        => ($data['user_name'] ?? 'Staff') . ' checked in',
                            str_contains($type, 'check_out')       => ($data['user_name'] ?? 'Staff') . ' checked out',
                            str_contains($type, 'late')            => ($data['user_name'] ?? 'Staff') . ' arrived late (' . ($data['late_minutes'] ?? '?') . ' min)',
                            str_contains($type, 'early_departure') => ($data['user_name'] ?? 'Staff') . ' left early',
                            str_contains($type, 'overtime')        => ($data['user_name'] ?? 'Staff') . ' worked overtime',
                            str_contains($type, 'report')          => $data['report_name'] ?? 'Report generated',
                            str_contains($type, 'expense')         => $data['title'] ?? 'Expense update',
                            str_contains($type, 'appointment')     => $data['title'] ?? 'Appointment update',
                            str_contains($type, 'leave')           => $data['title'] ?? 'Leave request update',
                            default                                => $data['title'] ?? $data['message'] ?? 'New notification',
                        };
                    }
                }
            @endphp
            <div class="topbar-item">
                <div class="dropdown">
                    <button aria-expanded="false" aria-haspopup="false"
                        class="topbar-link dropdown-toggle drop-arrow-none" data-bs-auto-close="outside"
                        data-bs-offset="0,24" data-bs-toggle="dropdown" type="button" id="notifDropdownBtn">
                        <i class="fs-xxl" data-lucide="bell"></i>
                        @if($topbarUnread > 0)
                        <span class="badge text-bg-danger badge-circle topbar-badge" id="notif-badge">{{ $topbarUnread > 99 ? '99+' : $topbarUnread }}</span>
                        @else
                        <span class="badge text-bg-danger badge-circle topbar-badge d-none" id="notif-badge">0</span>
                        @endif
                    </button>
                    <div class="dropdown-menu p-0 dropdown-menu-end dropdown-menu-lg">
                        <!-- Header -->
                        <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between">
                            <h6 class="m-0 fs-md fw-semibold">Notifications</h6>
                            <div class="d-flex align-items-center gap-2">
                                @if($topbarUnread > 0)
                                <span class="badge bg-danger-subtle text-danger" id="notif-unread-label">{{ $topbarUnread }} unread</span>
                                @else
                                <span class="badge bg-success-subtle text-success" id="notif-unread-label">All read</span>
                                @endif
                                <button class="btn btn-link btn-sm p-0 text-muted" id="markAllReadBtn" title="Mark all as read">
                                    <i class="ti ti-checks fs-16"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Notification list -->
                        <div data-simplebar="" style="max-height: 320px;" id="notif-list">
                            @forelse($topbarNotifs as $notif)
                                @php
                                    $nData  = is_array($notif->data) ? $notif->data : (json_decode($notif->data, true) ?? []);
                                    $cfg    = notiIconConfig($notif->type ?? '');
                                    $title  = notiTitle($notif->type ?? '', $nData);
                                    $isRead = !is_null($notif->read_at);
                                @endphp
                                <div class="dropdown-item notification-item py-2 text-wrap {{ $isRead ? 'opacity-75' : '' }}"
                                     data-notif-id="{{ $notif->uuid }}"
                                     data-mark-read-url="{{ route('notifications.mark-read', $notif->uuid) }}">
                                    <span class="d-flex align-items-center gap-3">
                                        <span class="flex-shrink-0 position-relative">
                                            <span class="avatar-md rounded-circle bg-{{ $cfg['color'] }}-subtle d-flex align-items-center justify-content-center">
                                                <i class="ti {{ $cfg['icon'] }} fs-18 text-{{ $cfg['color'] }}"></i>
                                            </span>
                                            @if(!$isRead)
                                            <span class="position-absolute top-0 end-0 p-1 bg-{{ $cfg['color'] }} border border-white rounded-circle" style="width:10px;height:10px;"></span>
                                            @endif
                                        </span>
                                        <span class="flex-grow-1 overflow-hidden">
                                            <span class="fw-{{ $isRead ? 'normal' : 'semibold' }} text-body d-block text-truncate" style="max-width:220px;">{{ $title }}</span>
                                            @if(!empty($nData['location']))
                                            <span class="text-muted fs-xs">{{ $nData['location'] }}</span><br>
                                            @endif
                                            <span class="fs-xs text-muted">{{ $notif->created_at->diffForHumans() }}</span>
                                        </span>
                                    </span>
                                </div>
                            @empty
                                <div class="text-center py-4 px-3">
                                    <i class="ti ti-bell-off fs-36 text-muted d-block mb-2"></i>
                                    <span class="text-muted fs-sm">No notifications yet</span>
                                </div>
                            @endforelse
                        </div>

                        <!-- Footer -->
                        <div class="border-top px-3 py-2 d-flex justify-content-between align-items-center">
                            <a class="fs-xs text-muted" href="{{ route('notifications.index') }}">View all</a>
                            <span class="fs-xs text-muted">{{ $topbarNotifs->count() }} shown</span>
                        </div>
                    </div> <!-- End dropdown-menu -->
                </div> <!-- end dropdown-->
            </div> <!-- end topbar item-->

            <script>
            (function () {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                // Mark individual notification as read on click
                document.addEventListener('click', function (e) {
                    const item = e.target.closest('[data-notif-id]');
                    if (!item) return;
                    const uuid = item.dataset.notifId;
                    const url  = item.dataset.markReadUrl;
                    if (!url || item.classList.contains('opacity-75')) return; // already read
                    fetch(url, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            item.classList.add('opacity-75');
                            const dot = item.querySelector('.rounded-circle.bg-{{ "primary" }},.rounded-circle.bg-success,.rounded-circle.bg-info,.rounded-circle.bg-warning,.rounded-circle.bg-danger,.rounded-circle.bg-secondary');
                            if (dot && dot.classList.contains('position-absolute')) dot.remove();
                            updateBadge(data.unread_count);
                        }
                    }).catch(() => {});
                });

                // Mark all as read
                const markAllBtn = document.getElementById('markAllReadBtn');
                if (markAllBtn) {
                    markAllBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        fetch('{{ route("notifications.mark-all-read") }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.message !== undefined) {
                                // Dim all unread items
                                document.querySelectorAll('#notif-list [data-notif-id]').forEach(el => {
                                    el.classList.add('opacity-75');
                                    const dot = el.querySelector('.position-absolute.rounded-circle');
                                    if (dot) dot.remove();
                                });
                                updateBadge(0);
                            }
                        }).catch(() => {});
                    });
                }

                function updateBadge(count) {
                    const badge = document.getElementById('notif-badge');
                    const label = document.getElementById('notif-unread-label');
                    if (badge) {
                        if (count > 0) {
                            badge.textContent = count > 99 ? '99+' : count;
                            badge.classList.remove('d-none');
                        } else {
                            badge.classList.add('d-none');
                        }
                    }
                    if (label) {
                        if (count > 0) {
                            label.textContent = count + ' unread';
                            label.className = 'badge bg-danger-subtle text-danger';
                        } else {
                            label.textContent = 'All read';
                            label.className = 'badge bg-success-subtle text-success';
                        }
                    }
                }

                // Poll for new notifications every 60 seconds
                setInterval(function () {
                    fetch('{{ route("notifications.check-new") }}', {
                        headers: { 'Accept': 'application/json' },
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) updateBadge(data.unread_count);
                    }).catch(() => {});
                }, 60000);
            })();
            </script>
            <!-- Theme Mode Dropdown -->
            <div class="topbar-item">
                <div class="dropdown">
                    <button aria-expanded="false" aria-haspopup="false" class="topbar-link" data-bs-offset="0,24"
                        data-bs-toggle="dropdown" type="button">
                        <i class="fs-xxl" data-lucide="layout-grid"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-lg p-2 dropdown-menu-end">
                        <div class="row align-items-center g-1">
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2"
                                    href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title text-bg-light rounded-circle">
                                            <img alt="Google Logo" height="18" src="/images/logos/google.svg" />
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Google</span>
                                </a>
                            </div>
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2"
                                    href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title text-bg-light rounded-circle">
                                            <img alt="Figma Logo" height="18" src="/images/logos/figma.svg" />
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Figma</span>
                                </a>
                            </div>
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2"
                                    href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title text-bg-light rounded-circle">
                                            <img alt="Slack Logo" height="18" src="/images/logos/slack.svg" />
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Slack</span>
                                </a>
                            </div>
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2"
                                    href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title text-bg-light rounded-circle">
                                            <img alt="Dropbox Logo" height="18" src="/images/logos/dropbox.svg" />
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Dropbox</span>
                                </a>
                            </div>
                            <div class="col-4 text-center">
                                <a class="btn btn-sm rounded-circle btn-icon btn-danger" href="javascript:void(0);">
                                    <i class="fs-18" data-lucide="circle-plus"></i>
                                </a>
                            </div>
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2"
                                    href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                            <i class="ti ti-calendar fs-18"></i>
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Calendar</span>
                                </a>
                            </div>
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2"
                                    href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                            <i class="ti ti-message-circle fs-18"></i>
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Chat</span>
                                </a>
                            </div>
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2"
                                    href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                            <i class="ti ti-folder fs-18"></i>
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Files</span>
                                </a>
                            </div>
                            <div class="col-4">
                                <a class="dropdown-item border border-dashed rounded text-center py-2"
                                    href="javascript:void(0);">
                                    <span class="avatar-sm d-block mx-auto mb-1">
                                        <span class="avatar-title bg-primary-subtle text-primary rounded-circle">
                                            <i class="ti ti-users fs-18"></i>
                                        </span>
                                    </span>
                                    <span class="align-middle fw-medium">Team</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div> <!-- end dropdown-->
            </div> <!-- end topbar item-->
            <!-- Theme Mode Dropdown -->
            <div class="topbar-item">
                <div class="dropdown">
                    <button aria-expanded="false" aria-haspopup="false" class="topbar-link" data-bs-offset="0,24"
                        data-bs-toggle="dropdown" type="button">
                        <i class="fs-xxl" data-lucide="sun"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end thememode-dropdown">
                        <li>
                            <label class="dropdown-item">
                                <i class="align-middle me-1 fs-16" data-lucide="sun"></i>
                                <span class="align-middle">Light</span>
                                <input class="form-check-input" name="data-bs-theme" type="radio"
                                    value="light" />
                            </label>
                        </li>
                        <li>
                            <label class="dropdown-item">
                                <i class="align-middle me-1 fs-16" data-lucide="moon"></i>
                                <span class="align-middle">Dark</span>
                                <input class="form-check-input" name="data-bs-theme" type="radio"
                                    value="dark" />
                            </label>
                        </li>
                        <li>
                            <label class="dropdown-item">
                                <i class="align-middle me-1 fs-16" data-lucide="monitor-cog"></i>
                                <span class="align-middle">System</span>
                                <input class="form-check-input" name="data-bs-theme" type="radio"
                                    value="system" />
                            </label>
                        </li>
                    </ul> <!-- end dropdown-menu-->
                </div> <!-- end dropdown-->
            </div> <!-- end topbar item-->
            <!-- FullScreen -->
            <div class="topbar-item d-none d-sm-flex">
                <button class="topbar-link" data-toggle="fullscreen" type="button">
                    <i class="fs-xxl fullscreen-off" data-lucide="maximize"></i>
                    <i class="fs-xxl fullscreen-on" data-lucide="minimize"></i>
                </button>
            </div>
            <!-- Light/Dark Mode Button -->
            <div class="topbar-item d-none">
                <button class="topbar-link" id="light-dark-mode" type="button">
                    <i class="fs-xxl mode-light-moon" data-lucide="moon"></i>
                </button>
            </div>
            <!-- Monocrome Mode Button -->
            <div class="topbar-item d-none d-sm-flex">
                <button class="topbar-link" id="monochrome-mode" type="button">
                    <i class="fs-xxl" data-lucide="palette"></i>
                </button>
            </div>
            <!-- User Dropdown -->
            <div class="topbar-item nav-user">
                <div class="dropdown">
                    <a aria-expanded="false" aria-haspopup="false"
                        class="topbar-link dropdown-toggle drop-arrow-none px-2" data-bs-offset="0,19"
                        data-bs-toggle="dropdown" href="#!">
                        <img alt="user-image" class="rounded-circle me-lg-2 d-flex"
                             src="{{ auth()->user()->avatar ? asset('storage/avatars/' . auth()->user()->avatar) : '/images/users/user-3.jpg' }}"
                            width="32" />
                        <div class="d-lg-flex align-items-center gap-1 d-none">
                            <h5 class="my-0">{{ strlen(auth()->user()->name) > 12 ? substr(auth()->user()->name, 0, 12) . '...' : auth()->user()->name }}</h5>
                            <i class="ti ti-chevron-down align-middle"></i>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end">
                        <!-- Header -->
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">Welcome {{ auth()->user()->name }} 👋!</h6>
                        </div>
                        <!-- My Profile -->
                        <a class="dropdown-item" href="{{ route('profile.show') }}">
                            <i class="ti ti-user-circle me-1 fs-17 align-middle"></i>
                            <span class="align-middle">Profile</span>
                        </a>
                        <!-- Edit Profile -->
                        <a class="dropdown-item" href="{{ route('profile.edit') }}">
                            <i class="ti ti-settings-2 me-1 fs-17 align-middle"></i>
                            <span class="align-middle">Account Settings</span>
                        </a>
                        <!-- Divider -->
                        <div class="dropdown-divider"></div>
                        <!-- Lock -->
                        <a class="dropdown-item" href="{{ route('second', ['auth', 'lock-screen']) }}">
                            <i class="ti ti-lock me-1 fs-17 align-middle"></i>
                            <span class="align-middle">Lock Screen</span>
                        </a>
                        <!-- Logout -->
                        <a class="dropdown-item fw-semibold" href="{{ route('logout') }}"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="ti ti-logout-2 me-1 fs-17 align-middle"></i>
                            <span class="align-middle">Log Out</span>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
            <!-- Button Trigger Customizer Offcanvas -->
            <div class="topbar-item d-none d-sm-flex">
                <button class="topbar-link" data-bs-target="#theme-settings-offcanvas" data-bs-toggle="offcanvas"
                    type="button">
                    <i class="ti ti-settings icon-spin fs-24"></i>
                </button>
            </div>
        </div>
    </div>
</header>
<!-- Topbar End -->
