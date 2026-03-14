/**
 * Provider Test Connection Component
 * Handles AJAX testing of notification provider connections
 */
import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', function() {
    initializeProviderTestButtons();
    initializeDynamicProviderForms();
});

/**
 * Initialize provider test buttons
 */
function initializeProviderTestButtons() {
    // Test buttons on provider listing page
    document.querySelectorAll('.test-provider-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const providerId = this.getAttribute('data-provider-id');
            testProviderConnection(providerId);
        });
    });

    // Test button on provider edit/create form
    const testConnectionBtn = document.getElementById('test-connection-btn');
    if (testConnectionBtn) {
        testConnectionBtn.addEventListener('click', function(e) {
            e.preventDefault();
            testProviderConnectionFromForm();
        });
    }
}

/**
 * Test provider connection from provider ID
 */
function testProviderConnection(providerId) {
    const button = document.querySelector(`.test-provider-btn[data-provider-id="${providerId}"]`);
    const originalText = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Testing...';

    fetch(`/notification-providers/${providerId}/test`, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
            
            // Update provider status in UI if on listing page
            updateProviderStatus(providerId, data.status);
        } else {
            throw new Error(data.message || 'Connection test failed');
        }
    })
    .catch(error => {
        console.error('Error testing provider connection:', error);
        Swal.fire({
            title: 'Error!',
            text: error.message || 'Failed to test provider connection',
            icon: 'error',
            customClass: {
                confirmButton: 'btn btn-primary'
            }
        });
    })
    .finally(() => {
        // Restore button state
        button.disabled = false;
        button.innerHTML = originalText;
    });
}

/**
 * Test provider connection from form data
 */
function testProviderConnectionFromForm() {
    const form = document.getElementById('provider-form');
    const testBtn = document.getElementById('test-connection-btn');
    
    if (!form || !testBtn) return;
    
    const originalText = testBtn.innerHTML;
    
    // Validate form
    if (!validateProviderForm()) {
        return;
    }
    
    // Show loading state
    testBtn.disabled = true;
    testBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Testing...';
    
    // Collect form data
    const formData = new FormData(form);
    
    // Add test flag
    formData.append('test_only', 'true');
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'Success!',
                text: data.message,
                icon: 'success',
                customClass: {
                    confirmButton: 'btn btn-primary'
                }
            });
            
            // If we're creating a new provider and test succeeded,
            // we could auto-fill some fields or enable the save button
            if (data.recommendations) {
                showTestRecommendations(data.recommendations);
            }
        } else {
            throw new Error(data.message || 'Connection test failed');
        }
    })
    .catch(error => {
        console.error('Error testing provider connection:', error);
        Swal.fire({
            title: 'Error!',
            text: error.message || 'Failed to test provider connection',
            icon: 'error',
            customClass: {
                confirmButton: 'btn btn-primary'
            }
        });
        
        // Show detailed error if available
        if (error.details) {
            showDetailedError(error.details);
        }
    })
    .finally(() => {
        // Restore button state
        testBtn.disabled = false;
        testBtn.innerHTML = originalText;
    });
}

/**
 * Validate provider form before testing
 */
function validateProviderForm() {
    const form = document.getElementById('provider-form');
    const channel = form.querySelector('#channel').value;
    const providerType = form.querySelector('#provider').value;
    
    // Check required fields based on provider type
    let isValid = true;
    let errorMessage = '';
    
    switch (channel) {
        case 'email':
            switch (providerType) {
                case 'smtp':
                    if (!form.querySelector('#config_host').value) {
                        isValid = false;
                        errorMessage = 'SMTP host is required';
                    }
                    if (!form.querySelector('#config_port').value) {
                        isValid = false;
                        errorMessage = 'SMTP port is required';
                    }
                    break;
                case 'mailgun':
                    if (!form.querySelector('#config_domain').value) {
                        isValid = false;
                        errorMessage = 'Mailgun domain is required';
                    }
                    if (!form.querySelector('#config_secret').value) {
                        isValid = false;
                        errorMessage = 'Mailgun secret is required';
                    }
                    break;
                case 'sendgrid':
                    if (!form.querySelector('#config_api_key').value) {
                        isValid = false;
                        errorMessage = 'SendGrid API key is required';
                    }
                    break;
                case 'ses':
                    if (!form.querySelector('#config_key').value) {
                        isValid = false;
                        errorMessage = 'AWS key is required';
                    }
                    if (!form.querySelector('#config_secret').value) {
                        isValid = false;
                        errorMessage = 'AWS secret is required';
                    }
                    break;
            }
            break;
        case 'sms':
            if (!form.querySelector('#config_api_key').value) {
                isValid = false;
                errorMessage = 'API key is required for SMS provider';
            }
            break;
        case 'push':
            if (!form.querySelector('#config_api_key').value) {
                isValid = false;
                errorMessage = 'API key is required for push provider';
            }
            break;
    }
    
    if (!isValid) {
        Swal.fire({
            title: 'Validation Error',
            text: errorMessage,
            icon: 'warning',
            customClass: {
                confirmButton: 'btn btn-primary'
            }
        });
    }
    
    return isValid;
}

/**
 * Update provider status in UI
 */
function updateProviderStatus(providerId, status) {
    // Update status badge
    const statusBadge = document.querySelector(`.provider-status[data-provider-id="${providerId}"]`);
    if (statusBadge) {
        let badgeClass = 'badge ';
        let badgeText = '';
        
        switch (status) {
            case 'active':
                badgeClass += 'bg-success';
                badgeText = 'Active';
                break;
            case 'inactive':
                badgeClass += 'bg-secondary';
                badgeText = 'Inactive';
                break;
            case 'testing':
                badgeClass += 'bg-warning';
                badgeText = 'Testing';
                break;
            case 'failed':
                badgeClass += 'bg-danger';
                badgeText = 'Failed';
                break;
            default:
                badgeClass += 'bg-info';
                badgeText = 'Unknown';
        }
        
        statusBadge.className = badgeClass;
        statusBadge.textContent = badgeText;
    }
    
    // Update last test time
    const lastTestElement = document.querySelector(`.last-test[data-provider-id="${providerId}"]`);
    if (lastTestElement) {
        lastTestElement.textContent = 'Just now';
    }
}

/**
 * Show test recommendations
 */
function showTestRecommendations(recommendations) {
    let html = '<ul class="text-start">';
    recommendations.forEach(rec => {
        html += `<li>${rec}</li>`;
    });
    html += '</ul>';
    
    Swal.fire({
        title: 'Test Recommendations',
        html: html,
        icon: 'info',
        customClass: {
            confirmButton: 'btn btn-primary'
        }
    });
}

/**
 * Show detailed error
 */
function showDetailedError(details) {
    Swal.fire({
        title: 'Detailed Error Information',
        html: `<pre class="text-start">${JSON.stringify(details, null, 2)}</pre>`,
        icon: 'error',
        customClass: {
            confirmButton: 'btn btn-primary'
        },
        width: '600px'
    });
}

/**
 * Initialize dynamic provider forms
 */
function initializeDynamicProviderForms() {
    const channelSelect = document.getElementById('channel');
    const providerSelect = document.getElementById('provider');
    
    if (channelSelect && providerSelect) {
        // Update provider options based on channel
        channelSelect.addEventListener('change', function() {
            updateProviderOptions(this.value);
            updateConfigFields(this.value, providerSelect.value);
        });
        
        // Update config fields based on provider
        providerSelect.addEventListener('change', function() {
            updateConfigFields(channelSelect.value, this.value);
        });
        
        // Initial update
        updateConfigFields(channelSelect.value, providerSelect.value);
    }
}

/**
 * Update provider options based on channel
 */
function updateProviderOptions(channel) {
    const providerSelect = document.getElementById('provider');
    if (!providerSelect) return;
    
    // Clear existing options
    providerSelect.innerHTML = '';
    
    // Add options based on channel
    let options = [];
    
    switch (channel) {
        case 'email':
            options = [
                { value: 'smtp', text: 'SMTP' },
                { value: 'mailgun', text: 'Mailgun' },
                { value: 'sendgrid', text: 'SendGrid' },
                { value: 'ses', text: 'Amazon SES' }
            ];
            break;
        case 'sms':
            options = [
                { value: 'twilio', text: 'Twilio' },
                { value: 'nexmo', text: 'Nexmo/Vonage' },
                { value: 'plivo', text: 'Plivo' }
            ];
            break;
        case 'in_app':
            options = [
                { value: 'system', text: 'System' }
            ];
            break;
        case 'push':
            options = [
                { value: 'fcm', text: 'Firebase Cloud Messaging' },
                { value: 'apns', text: 'Apple Push Notification Service' }
            ];
            break;
        default:
            options = [{ value: '', text: 'Select provider type' }];
    }
    
    options.forEach(option => {
        const optionElement = document.createElement('option');
        optionElement.value = option.value;
        optionElement.textContent = option.text;
        providerSelect.appendChild(optionElement);
    });
}

/**
 * Update configuration fields based on channel and provider
 */
function updateConfigFields(channel, provider) {
    const configContainer = document.getElementById('config-fields-container');
    if (!configContainer) return;
    
    let html = '';
    
    switch (channel) {
        case 'email':
            switch (provider) {
                case 'smtp':
                    html = `
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_host" class="form-label">SMTP Host *</label>
                                    <input type="text" class="form-control" id="config_host" name="config[host]" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_port" class="form-label">SMTP Port *</label>
                                    <input type="number" class="form-control" id="config_port" name="config[port]" value="587" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_username" class="form-label">Username</label>
                                    <input type="text" class="form-control" id="config_username" name="config[username]">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="config_password" name="config[password]">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_encryption" class="form-label">Encryption</label>
                                    <select class="form-select" id="config_encryption" name="config[encryption]">
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                        <option value="">None</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_from_address" class="form-label">From Address *</label>
                                    <input type="email" class="form-control" id="config_from_address" name="config[from_address]" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="config_from_name" class="form-label">From Name</label>
                            <input type="text" class="form-control" id="config_from_name" name="config[from_name]">
                        </div>
                    `;
                    break;
                case 'mailgun':
                    html = `
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_domain" class="form-label">Domain *</label>
                                    <input type="text" class="form-control" id="config_domain" name="config[domain]" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_secret" class="form-label">Secret Key *</label>
                                    <input type="password" class="form-control" id="config_secret" name="config[secret]" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_endpoint" class="form-label">Endpoint</label>
                                    <select class="form-select" id="config_endpoint" name="config[endpoint]">
                                        <option value="api.mailgun.net">US (api.mailgun.net)</option>
                                        <option value="api.eu.mailgun.net">EU (api.eu.mailgun.net)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_from_address" class="form-label">From Address *</label>
                                    <input type="email" class="form-control" id="config_from_address" name="config[from_address]" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="config_from_name" class="form-label">From Name</label>
                            <input type="text" class="form-control" id="config_from_name" name="config[from_name]">
                        </div>
                    `;
                    break;
                default:
                    html = '<div class="alert alert-info">Select a provider type to see configuration options.</div>';
            }
            break;
        case 'sms':
            html = `
                <div class="mb-3">
                    <label for="config_api_key" class="form-label">API Key *</label>
                    <input type="password" class="form-control" id="config_api_key" name="config[api_key]" required>
                </div>
                <div class="mb-3">
                    <label for="config_api_secret" class="form-label">API Secret</label>
                    <input type="password" class="form-control" id="config_api_secret" name="config[api_secret]">
                </div>
                <div class="mb-3">
                    <label for="config_from_number" class="form-label">From Number *</label>
                    <input type="text" class="form-control" id="config_from_number" name="config[from_number]" required>
                </div>
                <div class="mb-3">
                    <label for="config_account_sid" class="form-label">Account SID (Twilio)</label>
                    <input type="text" class="form-control" id="config_account_sid" name="config[account_sid]">
                </div>
            `;
            break;
        case 'in_app':
            html = '<div class="alert alert-info">In-app notifications require no additional configuration.</div>';
            break;
        case 'push':
            html = `
                <div class="mb-3">
                    <label for="config_api_key" class="form-label">API Key *</label>
                    <input type="password" class="form-control" id="config_api_key" name="config[api_key]" required>
                </div>
                <div class="mb-3">
                    <label for="config_project_id" class="form-label">Project ID (FCM)</label>
                    <input type="text" class="form-control" id="config_project_id" name="config[project_id]">
                </div>
                <div class="mb-3">
                    <label for="config_bundle_id" class="form-label">Bundle ID (APNS)</label>
                    <input type="text" class="form-control" id="config_bundle_id" name="config[bundle_id]">
                </div>
                <div class="mb-3">
                    <label for="config_certificate" class="form-label">Certificate (APNS)</label>
                    <textarea class="form-control" id="config_certificate" name="config[certificate]" rows="4"></textarea>
                </div>
            `;
            break;
        default:
            html = '<div class="alert alert-info">Select a channel to see configuration options.</div>';
    }
    
    configContainer.innerHTML = html;
}
                case 'sendgrid':
                    html = `
                        <div class="mb-3">
                            <label for="config_api_key" class="form-label">API Key *</label>
                            <input type="password" class="form-control" id="config_api_key" name="config[api_key]" required>
                        </div>
                        <div class="mb-3">
                            <label for="config_from_address" class="form-label">From Address *</label>
                            <input type="email" class="form-control" id="config_from_address" name="config[from_address]" required>
                        </div>
                        <div class="mb-3">
                            <label for="config_from_name" class="form-label">From Name</label>
                            <input type="text" class="form-control" id="config_from_name" name="config[from_name]">
                        </div>
                    `;
                    break;
                case 'ses':
                    html = `
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_key" class="form-label">AWS Access Key ID *</label>
                                    <input type="text" class="form-control" id="config_key" name="config[key]" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_secret" class="form-label">AWS Secret Access Key *</label>
                                    <input type="password" class="form-control" id="config_secret" name="config[secret]" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_region" class="form-label">AWS Region *</label>
                                    <input type="text" class="form-control" id="config_region" name="config[region]" value="us-east-1" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="config_from_address" class="form-label