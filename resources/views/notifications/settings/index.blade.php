@extends('layouts.vertical', ['title' => 'Notification Settings'])

@section('css')
    <style>
        .settings-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            background: #fff;
        }
        .settings-header {
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .notification-type-badge {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 4px 8px;
            font-size: 12px;
            color: #6c757d;
        }
        .channel-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
            margin-right: 5px;
        }
        .channel-email {
            background: #d1ecf1;
            color: #0c5460;
        }
        .channel-in_app {
            background: #d4edda;
            color: #155724;
        }
        .channel-sms {
            background: #fff3cd;
            color: #856404;
        }
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 30px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 22px;
            width: 22px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        input:checked + .toggle-slider {
            background-color: #805dca;
        }
        input:checked + .toggle-slider:before {
            transform: translateX(30px);
        }
    </style>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Notification Settings'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="settings-header">
                        <h4 class="card-title mb-0">System-wide Notification Settings</h4>
                        <p class="text-muted mb-0">Configure default notification settings for all users</p>
                    </div>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="settings-card">
                                <h5 class="mb-3">Default Notification Settings</h5>
                                <p class="text-muted mb-4">These settings will be applied to all users unless they override them in their personal settings.</p>

                                <form method="POST" action="{{ route('notification-settings.update') }}" id="systemSettingsForm">
                                    @csrf
                                    @method('PUT')
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Notification Type</th>
                                                    <th class="text-center">Email</th>
                                                    <th class="text-center">In-App</th>
                                                    <th class="text-center">SMS</th>
                                                    <th>Default Preferences</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($notificationTypes as $type => $label)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $label }}</strong>
                                                            <div class="mt-1">
                                                                <span class="notification-type-badge">{{ $type }}</span>
                                                            </div>
                                                        </td>
                                                        @foreach(['email', 'in_app', 'sms'] as $channel)
                                                            <td class="text-center align-middle">
                                                                @php
                                                                    $systemSetting = $systemDefaults ? $systemDefaults->where('notification_type', $type)->firstWhere('channel', $channel) : null;
                                                                    $isEnabled = $systemSetting ? $systemSetting->is_enabled : true;
                                                                @endphp
                                                                <div class="toggle-switch">
                                                                    <input type="checkbox" 
                                                                           name="settings[{{ $type }}][{{ $channel }}][is_enabled]" 
                                                                           value="1" 
                                                                           {{ $isEnabled ? 'checked' : '' }}
                                                                           id="toggle_{{ $type }}_{{ $channel }}">
                                                                    <label class="toggle-slider" for="toggle_{{ $type }}_{{ $channel }}"></label>
                                                                </div>
                                                            </td>
                                                        @endforeach
                                                        <td>
                                                            @php
                                                                $defaultPrefs = $systemDefaults ? ($systemDefaults->where('notification_type', $type)->firstWhere('channel', 'email')?->preferences ?? []) : [];
                                                            @endphp
                                                            <input type="text" 
                                                                   class="form-control form-control-sm" 
                                                                   name="settings[{{ $type }}][preferences]" 
                                                                   value="{{ json_encode($defaultPrefs) }}"
                                                                   placeholder="JSON preferences">
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>

                                    <div class="mt-4">
                                        <button type="submit" class="btn btn-primary">Save System Settings</button>
                                        <button type="button" class="btn btn-secondary" onclick="resetToDefaults()">Reset to Recommended Defaults</button>
                                    </div>
                                </form>
                            </div>

                            <div class="settings-card">
                                <h5 class="mb-3">Rate Limiting & Throttling</h5>
                                <form method="POST" action="{{ route('notification-settings.bulk-update') }}">
                                    @csrf
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Maximum Notifications per Hour</label>
                                                <input type="number" class="form-control" name="rate_limit_hourly" value="1000" min="1" max="10000">
                                                <small class="text-muted">Maximum number of notifications that can be sent per hour</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Maximum Notifications per Day</label>
                                                <input type="number" class="form-control" name="rate_limit_daily" value="10000" min="1" max="100000">
                                                <small class="text-muted">Maximum number of notifications that can be sent per day</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">User Notification Limit (Daily)</label>
                                                <input type="number" class="form-control" name="user_limit_daily" value="50" min="1" max="1000">
                                                <small class="text-muted">Maximum notifications a single user can receive per day</small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Retry Attempts</label>
                                                <input type="number" class="form-control" name="retry_attempts" value="3" min="0" max="10">
                                                <small class="text-muted">Number of retry attempts for failed notifications</small>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Save Rate Limits</button>
                                </form>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="settings-card">
                                <h5 class="mb-3">Blacklist/Whitelist</h5>
                                <form method="POST" action="#">
                                    @csrf
                                    <div class="mb-3">
                                        <label class="form-label">Blacklisted Email Domains</label>
                                        <textarea class="form-control" name="blacklisted_domains" rows="4" placeholder="Enter domains (one per line)
example.com
test.org">@foreach(['example.com', 'test.org'] as $domain){{ $domain }}
@endforeach</textarea>
                                        <small class="text-muted">Notifications will not be sent to these domains</small>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Whitelisted IP Addresses</label>
                                        <textarea class="form-control" name="whitelisted_ips" rows="4" placeholder="Enter IP addresses (one per line)
192.168.1.1
10.0.0.0/24">@foreach(['192.168.1.1', '10.0.0.0/24'] as $ip){{ $ip }}
@endforeach</textarea>
                                        <small class="text-muted">Only allow notifications from these IP addresses</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Save Blacklist/Whitelist</button>
                                </form>
                            </div>

                            <div class="settings-card">
                                <h5 class="mb-3">System Defaults</h5>
                                <div class="mb-3">
                                    <label class="form-label">Default Notification Channels</label>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="default_email" checked disabled>
                                        <label class="form-check-label" for="default_email">
                                            <span class="channel-badge channel-email">Email</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="default_in_app" checked disabled>
                                        <label class="form-check-label" for="default_in_app">
                                            <span class="channel-badge channel-in_app">In-App</span>
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="default_sms" disabled>
                                        <label class="form-check-label" for="default_sms">
                                            <span class="channel-badge channel-sms">SMS</span>
                                        </label>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">System-wide Do Not Disturb</label>
                                    <div class="input-group">
                                        <input type="time" class="form-control" value="22:00" disabled>
                                        <span class="input-group-text">to</span>
                                        <input type="time" class="form-control" value="07:00" disabled>
                                    </div>
                                    <small class="text-muted">System-wide quiet hours</small>
                                </div>
                                <button type="button" class="btn btn-outline-primary w-100" onclick="applyToAllUsers()">Apply to All Users</button>
                            </div>

                            <div class="settings-card">
                                <h5 class="mb-3">Statistics</h5>
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="p-3 bg-light rounded">
                                            <h3 class="mb-0">1,234</h3>
                                            <small class="text-muted">Today</small>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 bg-light rounded">
                                            <h3 class="mb-0">12,456</h3>
                                            <small class="text-muted">This Month</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>Email Success Rate</span>
                                        <span>98%</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-success" style="width: 98%"></div>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span>In-App Delivery Rate</span>
                                        <span>100%</span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-primary" style="width: 100%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function resetToDefaults() {
            if (confirm('Are you sure you want to reset all system settings to recommended defaults?')) {
                fetch('{{ route("notification-settings.reset-to-defaults") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message || 'Settings reset to defaults');
                    location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to reset settings');
                });
            }
        }

        function applyToAllUsers() {
            if (confirm('This will apply the current system settings to all users. Are you sure?')) {
                // Get current system settings
                const formData = new FormData(document.getElementById('systemSettingsForm'));
                
                fetch('{{ route("notification-settings.bulk-update") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message || 'Settings applied to all users');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to apply settings to all users');
                });
            }
        }

        // Auto-save functionality
        let saveTimeout;
        document.querySelectorAll('input[type="checkbox"], input[type="number"], textarea').forEach(element => {
            element.addEventListener('change', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    const form = this.closest('form');
                    if (form) {
                        const formData = new FormData(form);
                        fetch(form.action, {
                            method: form.method,
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            console.log('Auto-saved:', data);
                        })
                        .catch(error => {
                            console.error('Auto-save error:', error);
                        });
                    }
                }, 1000);
            });
        });
    </script>
@endsection