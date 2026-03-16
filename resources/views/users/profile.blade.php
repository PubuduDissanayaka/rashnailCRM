@extends('layouts.vertical', ['title' => 'Profile'])

@section('css')
@endsection

@section('content')
    @include('layouts.partials/page-title', ['subtitle' => 'Users', 'title' => 'Profile'])

    <div class="row">
        <div class="col-12">
            <article class="card overflow-hidden mb-0">
                <div class="position-relative card-side-img overflow-hidden"
                    style="min-height: 300px; background-image: url(/images/profile-bg.jpg);">
                    <div
                        class="p-4 card-img-overlay rounded-start-0 auth-overlay d-flex align-items-center flex-column justify-content-center">
                        <h3 class="text-white mb-1 fst-italic">"Welcome to Rash Nail Lounge"</h3>
                        <p class="text-white mb-4">– Nail Studio Management System</p>
                    </div>
                </div>
            </article>
        </div> <!-- end col-->
    </div> <!-- end row-->
    <div class="px-3 mt-n4">
        <div class="row">
            <div class="col-xl-4">
                <div class="card card-top-sticky">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-4">
                            <div class="me-3 position-relative">
                                <img
                                    alt="avatar"
                                    class="rounded-circle"
                                    height="72"
                                    src="{{ $user->avatar ? asset('storage/avatars/' . $user->avatar) : '/images/users/user-3.jpg' }}"
                                    width="72" />
                            </div>
                            <div>
                                <h5 class="mb-0 d-flex align-items-center">
                                    <span class="link-reset">{{ $user->name }}</span>
                                </h5>
                                <p class="text-muted mb-2">
                                    {{ $user->role === 'administrator' ? 'Administrator' : 'Staff Member' }}
                                </p>
                                <span class="badge text-bg-light badge-label">{{ ucfirst($user->role) }}</span>
                            </div>
                            <div class="ms-auto">
                                <div class="dropdown">
                                    <a class="btn btn-icon btn-ghost-light text-muted" data-bs-toggle="dropdown"
                                        href="#">
                                        <i class="ti ti-dots-vertical fs-xl"></i>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li><a class="dropdown-item" href="{{ route('profile.edit') }}">Edit Profile</a></li>
                                        <li><a class="dropdown-item text-danger" href="{{ route('logout') }}">Log Out</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div
                                    class="avatar-sm text-bg-light bg-opacity-75 d-flex align-items-center justify-content-center rounded-circle">
                                    <i class="ti ti-briefcase fs-xl"></i>
                                </div>
                                <p class="mb-0 fs-sm">{{ $user->role === 'administrator' ? 'System Administrator' : 'Nail Technician' }}</p>
                            </div>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div
                                    class="avatar-sm text-bg-light bg-opacity-75 d-flex align-items-center justify-content-center rounded-circle">
                                    <i class="ti ti-mail fs-xl"></i>
                                </div>
                                <p class="mb-0 fs-sm">Email <a class="text-primary fw-semibold"
                                        href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                                </p>
                            </div>
                            @if($user->phone)
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <div
                                    class="avatar-sm text-bg-light bg-opacity-75 d-flex align-items-center justify-content-center rounded-circle">
                                    <i class="ti ti-phone fs-xl"></i>
                                </div>
                                <p class="mb-0 fs-sm">Phone <span class="text-dark fw-semibold">{{ $user->phone }}</span>
                                </p>
                            </div>
                            @endif
                            <div class="d-flex align-items-center gap-2">
                                <div
                                    class="avatar-sm text-bg-light bg-opacity-75 d-flex align-items-center justify-content-center rounded-circle">
                                    <i class="ti ti-calendar fs-xl"></i>
                                </div>
                                <p class="mb-0 fs-sm">Member Since <span class="text-dark fw-semibold">{{ $user->created_at->format('M Y') }}</span>
                                </p>
                            </div>
                        </div>
                    </div> <!-- end card-body-->
                </div> <!-- end card-->
            </div> <!-- end col-->
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header card-tabs d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="card-title">My Account</h4>
                        </div>
                        <ul class="nav nav-tabs card-header-tabs nav-bordered">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#about-me">
                                    <i class="ti ti-home d-md-none d-block"></i>
                                    <span class="d-none d-md-block fw-bold">About Me</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#timeline">
                                    <i class="ti ti-user-circle d-md-none d-block"></i>
                                    <span class="d-none d-md-block fw-bold">Timeline</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#settings">
                                    <i class="ti ti-settings d-md-none d-block"></i>
                                    <span class="d-none d-md-block fw-bold">Settings</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#notifications">
                                    <i class="ti ti-bell d-md-none d-block"></i>
                                    <span class="d-none d-md-block fw-bold">Notifications</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane active" id="about-me">
                                <h5 class="card-title mb-3">User Information</h5>
                                <p>Welcome to the Rash Nail Lounge Management System. You are logged in as a {{ $user->role }}.</p>
                                <p class="mb-0">This system is designed to help manage appointments, customers, services, and sales at Rash Nail Lounge.</p>
                                
                                <h5 class="card-title mb-3 mt-4">Permissions</h5>
                                <ul class="list-group">
                                    @if($user->role === 'administrator')
                                    <li class="list-group-item">Full system access</li>
                                    <li class="list-group-item">User management</li>
                                    <li class="list-group-item">Financial reports</li>
                                    <li class="list-group-item">Service management</li>
                                    <li class="list-group-item">Customer management</li>
                                    <li class="list-group-item">Appointment scheduling</li>
                                    @else
                                    <li class="list-group-item">Limited system access</li>
                                    <li class="list-group-item">Customer management</li>
                                    <li class="list-group-item">Appointment scheduling</li>
                                    <li class="list-group-item">POS operations</li>
                                    @endif
                                </ul>
                            </div>
                            <div class="tab-pane" id="timeline">
                                <h5 class="card-title mb-3">Recent Activity</h5>
                                <p>Here you can see your recent activity within the system.</p>
                                <div class="timeline">
                                    <!-- Activity 1 -->
                                    <div class="timeline-item d-flex align-items-stretch">
                                        <div class="timeline-time pe-3 text-muted">{{ now()->subDays(2)->format('M d') }}</div>
                                        <div class="timeline-dot bg-primary"></div>
                                        <div class="timeline-content ps-3 pb-4">
                                            <h5 class="mb-1">Updated Profile</h5>
                                            <p class="mb-1 text-muted">You updated your profile information</p>
                                        </div>
                                    </div>
                                    <!-- Activity 2 -->
                                    <div class="timeline-item d-flex align-items-stretch">
                                        <div class="timeline-time pe-3 text-muted">{{ now()->subDays(5)->format('M d') }}</div>
                                        <div class="timeline-dot bg-success"></div>
                                        <div class="timeline-content ps-3 pb-4">
                                            <h5 class="mb-1">Completed Appointment</h5>
                                            <p class="mb-1 text-muted">You completed an appointment with a customer</p>
                                        </div>
                                    </div>
                                    <!-- Activity 3 -->
                                    <div class="timeline-item d-flex align-items-stretch">
                                        <div class="timeline-time pe-3 text-muted">{{ now()->subWeek()->format('M d') }}</div>
                                        <div class="timeline-dot bg-warning"></div>
                                        <div class="timeline-content ps-3 pb-4">
                                            <h5 class="mb-1">Added New Customer</h5>
                                            <p class="mb-1 text-muted">You added a new customer to the system</p>
                                        </div>
                                    </div>
                                    <!-- Activity 4 -->
                                    <div class="timeline-item d-flex align-items-stretch">
                                        <div class="timeline-time pe-3 text-muted">{{ now()->subWeeks(2)->format('M d') }}</div>
                                        <div class="timeline-dot bg-info"></div>
                                        <div class="timeline-content ps-3">
                                            <h5 class="mb-1">First Login</h5>
                                            <p class="mb-1 text-muted">Your first login to the system</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="settings">
                                <h5 class="card-title mb-3">Account Settings</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="settingName" class="form-label">Full Name</label>
                                            <input class="form-control" type="text" id="settingName" value="{{ $user->name }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="settingEmail" class="form-label">Email</label>
                                            <input class="form-control" type="email" id="settingEmail" value="{{ $user->email }}" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="settingRole" class="form-label">Role</label>
                                            <input class="form-control" type="text" id="settingRole" value="{{ ucfirst($user->role) }}" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="settingStatus" class="form-label">Status</label>
                                            <input class="form-control" type="text" id="settingStatus" value="Active" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <a href="{{ route('profile.edit') }}" class="btn btn-primary">Edit Profile</a>
                                    <a href="{{ route('logout') }}" class="btn btn-link text-danger">Log Out</a>
                                </div>
                            </div>
                            <div class="tab-pane" id="notifications">
                                <h5 class="card-title mb-3">Notification Settings</h5>
                                <p class="text-muted mb-4">Configure how you receive notifications from the system.</p>
                                
                                <form method="POST" action="{{ route('profile.notifications.update') }}">
                                    @csrf
                                    
                                    <div class="mb-4">
                                        <h6 class="mb-3">Notification Channels</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="emailNotifications" name="channels[]" value="email" checked>
                                                    <label class="form-check-label" for="emailNotifications">
                                                        <i class="ti ti-mail me-2"></i> Email Notifications
                                                    </label>
                                                    <p class="text-muted small mb-0">Receive notifications via email</p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-check mb-2">
                                                    <input class="form-check-input" type="checkbox" id="inAppNotifications" name="channels[]" value="in_app" checked>
                                                    <label class="form-check-label" for="inAppNotifications">
                                                        <i class="ti ti-bell me-2"></i> In-App Notifications
                                                    </label>
                                                    <p class="text-muted small mb-0">Show notifications within the application</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h6 class="mb-3">Notification Types</h6>
                                        
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th>Notification Type</th>
                                                        <th class="text-center">Email</th>
                                                        <th class="text-center">In-App</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>
                                                            <strong>Attendance Check-In/Out</strong>
                                                            <p class="text-muted small mb-0">Notifications when you check in or out</p>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="checkbox" id="attendance_email" name="notification_types[attendance_check_in][email]" value="1" checked>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="checkbox" id="attendance_in_app" name="notification_types[attendance_check_in][in_app]" value="1" checked>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <strong>Late Arrival / Early Departure</strong>
                                                            <p class="text-muted small mb-0">Notifications for attendance exceptions</p>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="checkbox" id="attendance_exceptions_email" name="notification_types[attendance_exceptions][email]" value="1" checked>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="checkbox" id="attendance_exceptions_in_app" name="notification_types[attendance_exceptions][in_app]" value="1" checked>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <strong>Report Generation</strong>
                                                            <p class="text-muted small mb-0">Notifications when reports are generated</p>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="checkbox" id="report_generated_email" name="notification_types[report_generated][email]" value="1" checked>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="checkbox" id="report_generated_in_app" name="notification_types[report_generated][in_app]" value="1" checked>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <strong>Report Generation Failed</strong>
                                                            <p class="text-muted small mb-0">Notifications when report generation fails</p>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="checkbox" id="report_failed_email" name="notification_types[report_generation_failed][email]" value="1" checked>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="checkbox" id="report_failed_in_app" name="notification_types[report_generation_failed][in_app]" value="1" checked>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <strong>Scheduled Reports</strong>
                                                            <p class="text-muted small mb-0">Notifications for scheduled report delivery</p>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="checkbox" id="scheduled_reports_email" name="notification_types[scheduled_report_ready][email]" value="1" checked>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="checkbox" id="scheduled_reports_in_app" name="notification_types[scheduled_report_ready][in_app]" value="1">
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <td>
                                                            <strong>System Announcements</strong>
                                                            <p class="text-muted small mb-0">Important system updates and announcements</p>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="checkbox" id="system_announcements_email" name="notification_types[system_announcements][email]" value="1" checked>
                                                            </div>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="form-check form-check-inline">
                                                                <input class="form-check-input" type="checkbox" id="system_announcements_in_app" name="notification_types[system_announcements][in_app]" value="1" checked>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h6 class="mb-3">Notification Frequency</h6>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="notification_frequency" class="form-label">Email Frequency</label>
                                                    <select class="form-select" id="notification_frequency" name="notification_frequency">
                                                        <option value="immediate" selected>Immediate (as they happen)</option>
                                                        <option value="daily">Daily Digest</option>
                                                        <option value="weekly">Weekly Digest</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="quiet_hours_start" class="form-label">Quiet Hours (Do Not Disturb)</label>
                                                    <div class="input-group">
                                                        <input type="time" class="form-control" id="quiet_hours_start" name="quiet_hours_start" value="22:00">
                                                        <span class="input-group-text">to</span>
                                                        <input type="time" class="form-control" id="quiet_hours_end" name="quiet_hours_end" value="07:00">
                                                    </div>
                                                    <p class="text-muted small mb-0">Notifications will be delayed during these hours</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end">
                                        <button type="submit" class="btn btn-primary">Save Notification Settings</button>
                                        <button type="reset" class="btn btn-secondary">Reset to Defaults</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
@endsection