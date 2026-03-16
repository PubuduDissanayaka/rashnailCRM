<div class="tab-pane" id="notification-tab" role="tabpanel">
    <form id="notification-form" action="{{ route('settings.update') }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="group" value="notification">
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3 form-check">
                    <input type="hidden" name="settings[notification.email_enabled]" value="0">
                    <input type="checkbox" class="form-check-input" id="email_enabled" name="settings[notification.email_enabled]" 
                           value="1" {{ (old('settings.notification.email_enabled', $notification['notification.email_enabled'] ?? '1') == '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="email_enabled">Enable Email Notifications</label>
                    <div class="form-text">Enable email notifications</div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="hidden" name="settings[notification.sms_enabled]" value="0">
                    <input type="checkbox" class="form-check-input" id="sms_enabled" name="settings[notification.sms_enabled]" 
                           value="1" {{ (old('settings.notification.sms_enabled', $notification['notification.sms_enabled'] ?? '0') == '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="sms_enabled">Enable SMS Notifications</label>
                    <div class="form-text">Enable SMS notifications</div>
                </div>
                
                <div class="mb-3">
                    <label for="email_address" class="form-label">Notification Email Address</label>
                    <input type="email" class="form-control" id="email_address" name="settings[notification.email_address]" 
                           value="{{ old('settings.notification.email_address', $notification['notification.email_address'] ?? 'notifications@rashnail.com') }}">
                    <div class="form-text">Email address for system notifications</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="reminder_hours" class="form-label">Appointment Reminder Time (hours before)</label>
                    <input type="number" class="form-control" id="reminder_hours" name="settings[notification.reminder_hours]" 
                           value="{{ old('settings.notification.reminder_hours', $notification['notification.reminder_hours'] ?? '24') }}">
                    <div class="form-text">Send appointment reminder (hours before)</div>
                </div>
                
                <div class="mb-3">
                    <label for="email_signature" class="form-label">Email Signature</label>
                    <textarea class="form-control" id="email_signature" name="settings[notification.email_signature]" rows="3">{{ old('settings.notification.email_signature', $notification['notification.email_signature'] ?? "Best Regards,\nRash Nail Studio Team") }}</textarea>
                    <div class="form-text">Email signature for outgoing emails</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3 form-check">
                    <input type="hidden" name="settings[notification.staff_new_appointment]" value="0">
                    <input type="checkbox" class="form-check-input" id="staff_new_appointment" name="settings[notification.staff_new_appointment]" 
                           value="1" {{ (old('settings.notification.staff_new_appointment', $notification['notification.staff_new_appointment'] ?? '1') == '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="staff_new_appointment">Notify Staff of New Appointments</label>
                    <div class="form-text">Notify staff of new appointments</div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="hidden" name="settings[notification.staff_cancellation]" value="0">
                    <input type="checkbox" class="form-check-input" id="staff_cancellation" name="settings[notification.staff_cancellation]" 
                           value="1" {{ (old('settings.notification.staff_cancellation', $notification['notification.staff_cancellation'] ?? '1') == '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="staff_cancellation">Notify Staff of Cancellations</label>
                    <div class="form-text">Notify staff of cancellations</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3 form-check">
                    <input type="hidden" name="settings[notification.customer_confirmation]" value="0">
                    <input type="checkbox" class="form-check-input" id="customer_confirmation" name="settings[notification.customer_confirmation]" 
                           value="1" {{ (old('settings.notification.customer_confirmation', $notification['notification.customer_confirmation'] ?? '1') == '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="customer_confirmation">Send Customer Confirmation</label>
                    <div class="form-text">Send customer booking confirmation</div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="hidden" name="settings[notification.customer_reminder]" value="0">
                    <input type="checkbox" class="form-check-input" id="customer_reminder" name="settings[notification.customer_reminder]" 
                           value="1" {{ (old('settings.notification.customer_reminder', $notification['notification.customer_reminder'] ?? '1') == '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="customer_reminder">Send Customer Reminder</label>
                    <div class="form-text">Send customer appointment reminder</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i> Save Notification Settings
                        </button>
                    </div>
                    <div>
                        <button type="button" class="btn btn-success" id="test-email-btn">
                            <i class="ti ti-send me-1"></i> Test Email Configuration
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Email Provider Configuration Section -->
    <div class="row mt-5">
        <div class="col-12">
            <h5 class="mb-3">
                <i class="ti ti-mail-cog me-2"></i>Email Provider Configuration
            </h5>

            <div class="alert alert-info mb-3">
                <i class="ti ti-info-circle me-2"></i>
                Configure email providers for sending notifications. You can set up multiple providers with priority ordering for failover support.
            </div>

            <!-- Existing Providers List -->
            <div id="email-providers-list" class="mb-3">
                @forelse($emailProviders ?? [] as $provider)
                    <div class="card mb-2 provider-card" data-provider-id="{{ $provider->id }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1">
                                        {{ $provider->name }}
                                        @if($provider->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </h6>
                                    <p class="text-muted mb-2 small">
                                        {{ ucfirst($provider->provider) }} | Priority: {{ $provider->priority }} |
                                        From: {{ $provider->getConfigValue('from_address') }}
                                    </p>
                                    @if($provider->last_test_at)
                                        <p class="mb-0 small">
                                            Last tested: {{ $provider->last_test_at->diffForHumans() }} -
                                            <span class="badge bg-{{ $provider->last_test_status === 'success' ? 'success' : 'danger' }}">
                                                {{ $provider->last_test_status }}
                                            </span>
                                        </p>
                                    @endif
                                </div>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-sm btn-outline-primary btn-action-edit"
                                            data-id="{{ $provider->id }}" 
                                            title="Edit">
                                        <i class="ti ti-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success btn-action-test"
                                            data-id="{{ $provider->id }}" 
                                            data-name="{{ $provider->name }}"
                                            title="Test">
                                        <i class="ti ti-send"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-action-toggle"
                                            data-id="{{ $provider->id }}"
                                            data-name="{{ $provider->name }}"
                                            data-active="{{ $provider->is_active ? '1' : '0' }}"
                                            title="{{ $provider->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="ti ti-{{ $provider->is_active ? 'eye-off' : 'eye' }}"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-action-delete"
                                            data-id="{{ $provider->id }}" 
                                            data-name="{{ $provider->name }}"
                                            title="Delete">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-2"></i>
                        No email providers configured. Add one below to start sending email notifications.
                    </div>
                @endforelse
            </div>

            <!-- Add New Provider Button -->
            <button type="button" class="btn btn-outline-primary mb-3" id="add-email-provider-btn">
                <i class="ti ti-plus me-1"></i> Add Email Provider
            </button>

            <!-- Provider Form (Hidden by Default) -->
            <div id="email-provider-form-container" class="card d-none mb-3">
                <div class="card-body">
                    <h6 class="mb-3" id="provider-form-title">Add Email Provider</h6>

                    <form id="email-provider-form">
                        <input type="hidden" id="provider-id" name="provider_id">
                        <input type="hidden" id="provider-form-method" name="_method" value="POST">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="provider-type" class="form-label">Provider Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="provider-type" name="provider" required>
                                        <option value="">Select Provider...</option>
                                        <option value="smtp">SMTP</option>
                                        <option value="mailgun">Mailgun</option>
                                        <option value="sendgrid">SendGrid</option>
                                        <option value="ses">Amazon SES</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="provider-name" class="form-label">Provider Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="provider-name" name="name"
                                           placeholder="e.g., Primary SMTP Server" required>
                                </div>
                            </div>
                        </div>

                        <!-- SMTP Configuration -->
                        <div id="smtp-config" class="provider-config d-none">
                            <h6 class="mb-2">Outgoing Mail (SMTP) Settings</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp-host" class="form-label">SMTP Host <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="smtp-host" name="config[host]"
                                               placeholder="smtp.gmail.com">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="smtp-port" class="form-label">Port <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="smtp-port" name="config[port]"
                                               placeholder="587">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="smtp-encryption" class="form-label">Encryption <span class="text-danger">*</span></label>
                                        <select class="form-select" id="smtp-encryption" name="config[encryption]">
                                            <option value="tls">TLS</option>
                                            <option value="ssl">SSL</option>
                                            <option value="none">None</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp-username" class="form-label">Username <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="smtp-username" name="config[username]">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp-password" class="form-label">Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="smtp-password" name="config[password]">
                                        <small class="form-text text-muted">Leave blank to keep existing password when editing</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp-timeout" class="form-label">
                                            Connection Timeout (seconds)
                                            <span class="text-muted small">(optional, default: 30)</span>
                                        </label>
                                        <input type="number" class="form-control" id="smtp-timeout" name="config[timeout]"
                                               min="1" max="120" placeholder="30">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="smtp-local-domain" class="form-label">
                                            Local Domain
                                            <span class="text-muted small">(optional)</span>
                                        </label>
                                        <input type="text" class="form-control" id="smtp-local-domain" name="config[local_domain]"
                                               placeholder="mail.yourdomain.com">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3">
                            <h6 class="mb-2">Incoming Mail (IMAP/POP3) Settings <span class="text-muted small fw-normal">(Optional)</span></h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="imap-host" class="form-label">Incoming Host</label>
                                        <input type="text" class="form-control" id="imap-host" name="config[imap_host]"
                                               placeholder="mail.yourdomain.com">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="imap-port" class="form-label">Port</label>
                                        <input type="number" class="form-control" id="imap-port" name="config[imap_port]"
                                               placeholder="993">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label for="imap-encryption" class="form-label">Encryption</label>
                                        <select class="form-select" id="imap-encryption" name="config[imap_encryption]">
                                            <option value="ssl">SSL</option>
                                            <option value="tls">TLS</option>
                                            <option value="none">None</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mailgun Configuration -->
                        <div id="mailgun-config" class="provider-config d-none">
                            <h6 class="mb-2">Mailgun Settings</h6>
                            <div class="alert alert-info py-2 small mb-3">
                                <i class="ti ti-info-circle me-1"></i>
                                Sends via <strong>Mailgun SMTP relay</strong> (smtp.mailgun.org).
                                Enter your Mailgun API key as the password — it also works as the SMTP password.
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mailgun-domain" class="form-label">Domain <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="mailgun-domain" name="config[domain]"
                                               placeholder="mg.yourdomain.com">
                                        <div class="form-text">Your Mailgun sending domain</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="mailgun-secret" class="form-label">API Key (SMTP Password) <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="mailgun-secret" name="config[secret]">
                                        <div class="form-text">Your Mailgun API key</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SendGrid Configuration -->
                        <div id="sendgrid-config" class="provider-config d-none">
                            <h6 class="mb-2">SendGrid Settings</h6>
                            <div class="mb-3">
                                <label for="sendgrid-api-key" class="form-label">API Key <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="sendgrid-api-key" name="config[api_key]">
                            </div>
                        </div>

                        <!-- SES Configuration -->
                        <div id="ses-config" class="provider-config d-none">
                            <h6 class="mb-2">Amazon SES Settings</h6>
                            <div class="alert alert-info py-2 small mb-3">
                                <i class="ti ti-info-circle me-1"></i>
                                Use your <strong>SES SMTP credentials</strong> (not your IAM credentials).
                                Generate them in the AWS console under <em>SES → SMTP Settings → Create SMTP credentials</em>.
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="ses-key" class="form-label">SMTP Username <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="ses-key" name="config[key]" placeholder="AKIAIOSFODNN7EXAMPLE">
                                        <div class="form-text">SES SMTP username (starts with AKIA…)</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="ses-secret" class="form-label">SMTP Password <span class="text-danger">*</span></label>
                                        <input type="password" class="form-control" id="ses-secret" name="config[secret]">
                                        <div class="form-text">SES SMTP password (not your IAM secret key)</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="ses-region" class="form-label">Region <span class="text-danger">*</span></label>
                                        <select class="form-select" id="ses-region" name="config[region]">
                                            <option value="us-east-1">US East (N. Virginia)</option>
                                            <option value="us-west-2">US West (Oregon)</option>
                                            <option value="eu-west-1">EU (Ireland)</option>
                                            <option value="ap-southeast-1">Asia Pacific (Singapore)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Common Fields for All Providers -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="from-address" class="form-label">From Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="from-address" name="config[from_address]"
                                           placeholder="noreply@yourdomain.com" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="from-name" class="form-label">From Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="from-name" name="config[from_name]"
                                           placeholder="Your Business Name" required>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="priority" class="form-label">Priority</label>
                                    <input type="number" class="form-control" id="priority" name="priority"
                                           value="0" min="0">
                                    <small class="form-text text-muted">Lower number = higher priority</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="daily-limit" class="form-label">Daily Limit</label>
                                    <input type="number" class="form-control" id="daily-limit" name="daily_limit"
                                           placeholder="Unlimited">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="monthly-limit" class="form-label">Monthly Limit</label>
                                    <input type="number" class="form-control" id="monthly-limit" name="monthly_limit"
                                           placeholder="Unlimited">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is-active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is-active">Active</label>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light" id="cancel-provider-btn">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i> Save Provider
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>