<!-- Sidenav Menu Start -->
<div class="sidenav-menu">
    <!-- Brand Logo -->
    <a class="logo" href="{{ route('second', ['dashboard', 'index']) }}">
        <span class="logo logo-light">
            <span class="logo-lg"><img alt="logo" src="{{ $appLogoUrl }}" /></span>
            <span class="logo-sm"><img alt="small logo" src="{{ $appLogoSmUrl }}" /></span>
        </span>
        <span class="logo logo-dark">
            <span class="logo-lg"><img alt="dark logo" src="{{ $appLogoDarkUrl }}" /></span>
            <span class="logo-sm"><img alt="small logo" src="{{ $appLogoSmUrl }}" /></span>
        </span>
    </a>
    <!-- Sidebar Hover Menu Toggle Button -->
    <button class="button-on-hover">
        <i class="ti ti-menu-4 fs-22 align-middle"></i>
    </button>
    <!-- Full Sidebar Menu Close Button -->
    <button class="button-close-offcanvas">
        <i class="ti ti-x align-middle"></i>
    </button>
    <div class="scrollbar" data-simplebar="">
        <!-- User -->
        <div class="sidenav-user">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <a class="link-reset" href="{{ route('profile.show') }}">
                        @if(auth()->user()?->avatar)
                            <img alt="user-image" class="rounded-circle mb-2 avatar-md" src="{{ Storage::url(auth()->user()->avatar) }}" />
                        @else
                            <img alt="user-image" class="rounded-circle mb-2 avatar-md" src="/images/users/user-3.jpg" />
                        @endif
                        <span class="sidenav-user-name fw-bold">{{ auth()->user()?->name ?? 'User' }}</span>
                        <span class="fs-12 fw-semibold">{{ ucfirst(auth()->user()?->role ?? 'Staff') }}</span>
                    </a>
                </div>
                <div>
                    <a aria-expanded="false" aria-haspopup="false"
                        class="dropdown-toggle drop-arrow-none link-reset sidenav-user-set-icon" data-bs-offset="0,12"
                        data-bs-toggle="dropdown" href="#!">
                        <i class="ti ti-settings fs-24 align-middle ms-1"></i>
                    </a>
                    <div class="dropdown-menu">
                        <div class="dropdown-header noti-title">
                            <h6 class="text-overflow m-0">Welcome back!</h6>
                        </div>
                        <a class="dropdown-item" href="{{ route('profile.show') }}">
                            <i class="ti ti-user-circle me-2 fs-17 align-middle"></i>
                            <span class="align-middle">Profile</span>
                        </a>
                        @can('manage system')
                        <a class="dropdown-item" href="{{ route('settings.index') }}">
                            <i class="ti ti-settings-2 me-2 fs-17 align-middle"></i>
                            <span class="align-middle">Settings</span>
                        </a>
                        @endcan
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ route('second', ['auth', 'lock-screen']) }}">
                            <i class="ti ti-lock me-2 fs-17 align-middle"></i>
                            <span class="align-middle">Lock Screen</span>
                        </a>
                        <a class="dropdown-item fw-semibold" href="{{ route('logout') }}"
                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="ti ti-logout-2 me-2 fs-17 align-middle"></i>
                            <span class="align-middle">Log Out</span>
                        </a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!--- Sidenav Menu -->
        <ul class="side-nav">

            {{-- ===== NAVIGATION ===== --}}
            <li class="side-nav-title mt-2">Navigation</li>

            <li class="side-nav-item">
                <a class="side-nav-link" href="{{ route('second', ['dashboard', 'index']) }}">
                    <span class="menu-icon"><i data-lucide="circle-gauge"></i></span>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>

            {{-- ===== SALON MANAGEMENT ===== --}}
            <li class="side-nav-title">Salon Management</li>

            {{-- Appointments --}}
            @can('view appointments')
            <li class="side-nav-item">
                <a aria-controls="sidebarAppointments" aria-expanded="false" class="side-nav-link"
                    data-bs-toggle="collapse" href="#sidebarAppointments">
                    <span class="menu-icon"><i data-lucide="calendar-clock"></i></span>
                    <span class="menu-text">Appointments</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarAppointments">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('appointments.index') }}">
                                <span class="menu-text">List View</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('appointments.calendar') }}">
                                <span class="menu-text">Calendar View</span>
                            </a>
                        </li>
                        @can('create appointments')
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('appointments.create') }}">
                                <span class="menu-text">Book Appointment</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </div>
            </li>
            @endcan

            {{-- Customers --}}
            <li class="side-nav-item">
                <a class="side-nav-link" href="{{ route('customers.index') }}">
                    <span class="menu-icon"><i data-lucide="users"></i></span>
                    <span class="menu-text">Customers</span>
                </a>
            </li>

            {{-- Services --}}
            <li class="side-nav-item">
                <a class="side-nav-link" href="{{ route('services.index') }}">
                    <span class="menu-icon"><i data-lucide="scissors"></i></span>
                    <span class="menu-text">Services</span>
                </a>
            </li>

            {{-- Service Packages --}}
            <li class="side-nav-item">
                <a class="side-nav-link" href="{{ route('service-packages.index') }}">
                    <span class="menu-icon"><i data-lucide="package"></i></span>
                    <span class="menu-text">Service Packages</span>
                </a>
            </li>

            {{-- ===== SALES & FINANCE ===== --}}
            <li class="side-nav-title">Sales & Finance</li>

            {{-- Point of Sale --}}
            @can('view pos')
            <li class="side-nav-item">
                <a aria-controls="sidebarPOS" aria-expanded="false" class="side-nav-link"
                    data-bs-toggle="collapse" href="#sidebarPOS">
                    <span class="menu-icon"><i data-lucide="credit-card"></i></span>
                    <span class="menu-text">Point of Sale</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarPOS">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('pos.index') }}">
                                <span class="menu-text">POS Terminal</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('pos.transactions') }}">
                                <span class="menu-text">Transactions</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endcan

            {{-- Expenses --}}
            @can('expenses.view')
            <li class="side-nav-item">
                <a aria-controls="sidebarExpenses" aria-expanded="false" class="side-nav-link"
                    data-bs-toggle="collapse" href="#sidebarExpenses">
                    <span class="menu-icon"><i data-lucide="wallet"></i></span>
                    <span class="menu-text">Expenses</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarExpenses">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('expenses.dashboard') }}">
                                <span class="menu-text">Dashboard</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('expenses.index') }}">
                                <span class="menu-text">All Expenses</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('expenses.create') }}">
                                <span class="menu-text">Add Expense</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('expenses.categories.index') }}">
                                <span class="menu-text">Categories</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endcan

            {{-- Coupons --}}
            @can('view coupons')
            <li class="side-nav-item">
                <a aria-controls="sidebarCoupons" aria-expanded="false" class="side-nav-link"
                    data-bs-toggle="collapse" href="#sidebarCoupons">
                    <span class="menu-icon"><i data-lucide="ticket"></i></span>
                    <span class="menu-text">Coupons</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarCoupons">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('coupons.index') }}">
                                <span class="menu-text">All Coupons</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('coupons.create') }}">
                                <span class="menu-text">Create Coupon</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('coupons.bulk.create') }}">
                                <span class="menu-text">Bulk Generation</span>
                            </a>
                        </li>

                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('customer-groups.index') }}">
                                <span class="menu-text">Customer Groups</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('reports.coupons.index') }}">
                                <span class="menu-text">Reports</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endcan

            {{-- ===== OPERATIONS ===== --}}
            <li class="side-nav-title">Operations</li>

            {{-- Attendance — visible to all (everyone clocks in) --}}
            <li class="side-nav-item">
                <a aria-controls="sidebarAttendance" aria-expanded="false" class="side-nav-link"
                    data-bs-toggle="collapse" href="#sidebarAttendance">
                    <span class="menu-icon"><i data-lucide="calendar-check"></i></span>
                    <span class="menu-text">Attendance</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarAttendance">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('attendance.index') }}">
                                <span class="menu-text">Daily Attendance</span>
                            </a>
                        </li>
                        @can('view attendances')
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('attendance.report') }}">
                                <span class="menu-text">Attendance Reports</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('attendance.staff') }}">
                                <span class="menu-text">Staff Attendance</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </div>
            </li>

            {{-- Inventory --}}
            @can('inventory.view')
            <li class="side-nav-item">
                <a aria-controls="sidebarInventory" aria-expanded="false" class="side-nav-link"
                    data-bs-toggle="collapse" href="#sidebarInventory">
                    <span class="menu-icon"><i data-lucide="package-open"></i></span>
                    <span class="menu-text">Inventory</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarInventory">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('inventory.supplies.index') }}">
                                <span class="menu-text">Supplies</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('inventory.categories.index') }}">
                                <span class="menu-text">Categories</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('inventory.purchase-orders.index') }}">
                                <span class="menu-text">Purchase Orders</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('inventory.alerts.index') }}">
                                <span class="menu-text">Stock Alerts</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endcan

            {{-- Reports --}}
            @can('view reports')
            <li class="side-nav-item">
                <a aria-controls="sidebarReports" aria-expanded="false" class="side-nav-link"
                    data-bs-toggle="collapse" href="#sidebarReports">
                    <span class="menu-icon"><i data-lucide="bar-chart-2"></i></span>
                    <span class="menu-text">Reports</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarReports">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('reports.index') }}">
                                <span class="menu-text">Reports Hub</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('reports.sales') }}">
                                <span class="menu-text">Sales & Revenue</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('reports.appointments') }}">
                                <span class="menu-text">Appointments</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('reports.customers') }}">
                                <span class="menu-text">Customers</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('reports.expenses') }}">
                                <span class="menu-text">Expenses</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('reports.inventory') }}">
                                <span class="menu-text">Inventory</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('reports.work-hours.index') }}">
                                <span class="menu-text">Work Hours</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('reports.coupons.index') }}">
                                <span class="menu-text">Coupons</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>
            @endcan

            {{-- Notifications --}}
            {{-- <li class="side-nav-item">
                <a aria-controls="sidebarNotifications" aria-expanded="false" class="side-nav-link"
                    data-bs-toggle="collapse" href="#sidebarNotifications">
                    <span class="menu-icon"><i data-lucide="bell"></i></span>
                    <span class="menu-text">Notifications</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarNotifications">
                    <ul class="sub-menu">
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('notification-settings.index') }}">
                                <span class="menu-text">Settings</span>
                            </a>
                        </li>
                        @can('manage system')
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('notifications.status.index') }}">
                                <span class="menu-text">Status Dashboard</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </div>
            </li> --}}

            {{-- ===== ADMINISTRATION ===== --}}
            <li class="side-nav-title">Administration</li>

            {{-- Users --}}
            <li class="side-nav-item">
                <a aria-controls="sidebarUsers" aria-expanded="false" class="side-nav-link"
                    data-bs-toggle="collapse" href="#sidebarUsers">
                    <span class="menu-icon"><i data-lucide="user-cog"></i></span>
                    <span class="menu-text">Users</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="sidebarUsers">
                    <ul class="sub-menu">
                        @can('view users')
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('users.index') }}">
                                <span class="menu-text">All Staff</span>
                            </a>
                        </li>
                        @endcan
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('profile.show') }}">
                                <span class="menu-text">My Profile</span>
                            </a>
                        </li>
                        @can('manage system')
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('users.roles') }}">
                                <span class="menu-text">Roles</span>
                            </a>
                        </li>
                        <li class="side-nav-item">
                            <a class="side-nav-link" href="{{ route('users.permissions') }}">
                                <span class="menu-text">Permissions</span>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </div>
            </li>

            {{-- Settings --}}
            @can('manage system')
            <li class="side-nav-item">
                <a class="side-nav-link" href="{{ route('settings.index') }}">
                    <span class="menu-icon"><i data-lucide="settings"></i></span>
                    <span class="menu-text">Settings</span>
                </a>
            </li>
            @endcan

        </ul>
        <!-- End Sidenav Menu -->
    </div>
</div>
<!-- End Sidenav Menu -->
