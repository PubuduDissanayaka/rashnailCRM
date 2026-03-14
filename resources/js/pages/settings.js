/**
 * Settings Page JavaScript
 * Handles form submissions, file uploads, and validation
 */
import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', function () {
    initializeFormSubmission();
    initializeLogoPreview();
    initializeTestEmail();
    initializeUnsavedChangesWarning();
    initializeBusinessHoursToggle();
    initializeDepositToggle();
    initializeEmailProviders();
});

/**
 * Initialize AJAX form submission for all settings forms
 */
function initializeFormSubmission() {
    const forms = ['business-form', 'appointment-form', 'notification-form', 'payment-form'];

    forms.forEach(formId => {
        const form = document.getElementById(formId);
        if (!form) return;

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

            fetch(this.action, {
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
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/json')) {
                        return response.json();
                    } else {
                        return response.text().then(text => {
                            throw new Error(`Expected JSON but got: ${text.substring(0, 100)}`);
                        });
                    }
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
                        form.dataset.saved = 'true';
                        console.log('Form saved, dataset.saved set to true for', form.id);
                        console.log('Verification: dataset.saved =', form.dataset.saved, 'data-saved attribute:', form.getAttribute('data-saved'));
                    } else {
                        throw new Error(data.message || 'An error occurred');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        title: 'Error!',
                        text: error.message || 'An error occurred while saving settings',
                        icon: 'error',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        }
                    });
                })
                .finally(() => {
                    // Restore button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                });
        });
    });
}

/**
 * Initialize logo preview functionality
 */
function initializeLogoPreview() {
    const logoInput = document.getElementById('logo');
    const faviconInput = document.getElementById('favicon');

    if (logoInput) {
        logoInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    // Here you could show a preview if needed
                    console.log('Logo selected:', file.name);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    if (faviconInput) {
        faviconInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    // Here you could show a preview if needed
                    console.log('Favicon selected:', file.name);
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

/**
 * Initialize test email functionality
 */
function initializeTestEmail() {
    const testEmailBtn = document.getElementById('test-email-btn');

    if (testEmailBtn) {
        testEmailBtn.addEventListener('click', function () {
            Swal.fire({
                title: 'Test Email Configuration',
                html: '<input type="email" id="email-input" class="swal2-input" placeholder="Enter recipient email">',
                confirmButtonText: 'Send Test Email',
                showCancelButton: true,
                customClass: {
                    confirmButton: 'btn btn-primary',
                    cancelButton: 'btn btn-light'
                },
                preConfirm: () => {
                    const email = document.getElementById('email-input').value;
                    if (!email) {
                        Swal.showValidationMessage('Please enter an email address');
                        return false;
                    }
                    return email;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('/settings/test-email', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ email: result.value })
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                            const contentType = response.headers.get('content-type');
                            if (contentType && contentType.includes('application/json')) {
                                return response.json();
                            } else {
                                return response.text().then(text => {
                                    throw new Error(`Expected JSON but got: ${text.substring(0, 100)}`);
                                });
                            }
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
                            } else {
                                throw new Error(data.message || 'An error occurred');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                title: 'Error!',
                                text: error.message || 'An error occurred while sending test email',
                                icon: 'error',
                                customClass: {
                                    confirmButton: 'btn btn-primary'
                                }
                            });
                        });
                }
            });
        });
    }
}

/**
 * Initialize unsaved changes warning
 */
function initializeUnsavedChangesWarning() {
    // Warn user if they try to navigate away with unsaved changes
    window.addEventListener('beforeunload', function (e) {
        const forms = ['business-form', 'appointment-form', 'notification-form', 'payment-form'];
        let hasUnsavedChanges = false;
        console.log('beforeunload triggered, checking forms:');
        forms.forEach(formId => {
            const form = document.getElementById(formId);
            if (form) {
                console.log(`Form ${formId}: dataset.saved =`, form.dataset.saved);
                if (form.dataset.saved !== 'true') {
                    hasUnsavedChanges = true;
                    console.log(` -> unsaved`);
                }
            } else {
                console.log(`Form ${formId}: not found`);
            }
        });

        if (hasUnsavedChanges) {
            e.preventDefault();
            e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            console.log('Warning shown');
        } else {
            console.log('No unsaved changes, allowing navigation');
        }
    });

    // Mark form as unsaved when changed
    const forms = ['business-form', 'appointment-form', 'notification-form', 'payment-form'];
    forms.forEach(formId => {
        const form = document.getElementById(formId);
        if (form) {
            // Initialize form as saved on page load
            form.dataset.saved = 'true';

            form.addEventListener('input', function (e) {
                console.log(`Input event on form ${formId}, isTrusted:`, e.isTrusted);
                if (e.isTrusted) {
                    delete form.dataset.saved;
                    console.log(`Deleted dataset.saved for ${formId}`);
                } else {
                    console.log(`Ignoring programmatic input event on ${formId}`);
                }
            });
            form.addEventListener('change', function (e) {
                console.log(`Change event on form ${formId}, isTrusted:`, e.isTrusted, 'target:', e.target.id || e.target.tagName);
                // Ignore change events from business hours day-enabled checkboxes
                // because they are triggered programmatically on page load and after save
                if (e.target.classList && e.target.classList.contains('day-enabled')) {
                    console.log(`Ignoring day-enabled checkbox change for ${formId}`);
                    return;
                }
                // Ignore all checkbox changes because they are often triggered programmatically
                // and cause false unsaved warnings. If a user intentionally changes a checkbox,
                // they can still save the form, and the warning will be cleared after save.
                if (e.target.type === 'checkbox') {
                    console.log(`Ignoring checkbox change for ${formId}`);
                    return;
                }
                if (e.isTrusted) {
                    delete form.dataset.saved;
                    console.log(`Deleted dataset.saved for ${formId}`);
                } else {
                    console.log(`Ignoring programmatic change event on ${formId}`);
                }
            });
        }
    });
}

/**
 * Initialize business hours toggle functionality
 */
function initializeBusinessHoursToggle() {
    const dayCheckboxes = document.querySelectorAll('.day-enabled');

    dayCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            // Update the name attribute of the time inputs based on checkbox state
            const dayId = this.id.replace('_enabled', '');
            const openInput = document.getElementById(`${dayId}_open`);
            const closeInput = document.getElementById(`${dayId}_close`);

            if (this.checked) {
                openInput.disabled = false;
                closeInput.disabled = false;
                // Set the name attribute to ensure values are submitted when checked
                openInput.name = `business_hours[${dayId}][open]`;
                closeInput.name = `business_hours[${dayId}][close]`;
            } else {
                openInput.disabled = true;
                closeInput.disabled = true;
                // Clear the name attribute to prevent values from being submitted when unchecked
                openInput.name = '';
                closeInput.name = '';
                // Clear values when unchecked
                openInput.value = '';
                closeInput.value = '';
            }
        });

        // Trigger change event on page load to set initial state
        checkbox.dispatchEvent(new Event('change'));
    });
}

/**
 * Initialize deposit toggle functionality
 */
function initializeDepositToggle() {
    const requireDeposit = document.getElementById('require_deposit');
    const depositPercentage = document.getElementById('deposit_percentage');

    if (requireDeposit && depositPercentage) {
        requireDeposit.addEventListener('change', function () {
            depositPercentage.disabled = !this.checked;
        });
    }
}

/**
 * Initialize email provider management functionality
 */
/**
 * Initialize email provider management functionality
 */
function initializeEmailProviders() {
    const addProviderBtn = document.getElementById('add-email-provider-btn');
    const providerFormContainer = document.getElementById('email-provider-form-container');
    const providerForm = document.getElementById('email-provider-form');
    const cancelProviderBtn = document.getElementById('cancel-provider-btn');
    const providerTypeSelect = document.getElementById('provider-type');
    const providersList = document.getElementById('email-providers-list');

    // Check if critical elements exist
    if (!addProviderBtn || !providerFormContainer || !providerForm) {
        return;
    }

    let editingProviderId = null;

    // --- Event Listeners ---

    // 1. Add New Provider
    addProviderBtn.addEventListener('click', () => {
        showProviderForm();
    });

    // 2. Cancel Form
    if (cancelProviderBtn) {
        cancelProviderBtn.addEventListener('click', () => {
            hideProviderForm();
        });
    }

    // 3. Provider Type Change
    if (providerTypeSelect) {
        providerTypeSelect.addEventListener('change', () => {
            updateConfigVisibility(providerTypeSelect.value);
        });
    }

    // 4. Form Submission
    providerForm.addEventListener('submit', (e) => {
        e.preventDefault();
        submitProviderForm(providerForm, editingProviderId);
    });

    // 5. Provider Actions (Edit, Test, Toggle, Delete) - Event Delegation
    if (providersList) {
        providersList.addEventListener('click', (e) => {
            const target = e.target;

            // Edit
            const editBtn = target.closest('.btn-action-edit');
            if (editBtn) {
                handleEditProvider(editBtn.dataset.id);
                return;
            }

            // Test
            const testBtn = target.closest('.btn-action-test');
            if (testBtn) {
                handleTestProvider(testBtn.dataset.id, testBtn.dataset.name);
                return;
            }

            // Toggle
            const toggleBtn = target.closest('.btn-action-toggle');
            if (toggleBtn) {
                handleToggleProvider(toggleBtn.dataset.id, toggleBtn.dataset.name, toggleBtn.dataset.active);
                return;
            }

            // Delete
            const deleteBtn = target.closest('.btn-action-delete');
            if (deleteBtn) {
                handleDeleteProvider(deleteBtn.dataset.id, deleteBtn.dataset.name);
                return;
            }
        });
    }

    // --- Helper Functions ---

    function showProviderForm(provider = null) {
        providerForm.reset();

        if (provider) {
            // Edit Mode
            editingProviderId = provider.id;
            populateProviderForm(provider);
            document.getElementById('provider-form-title').textContent = 'Edit Email Provider';
            document.getElementById('provider-form-method').value = 'PUT';

            // Trigger type change to show correct config
            if (providerTypeSelect) {
                providerTypeSelect.dispatchEvent(new Event('change'));
            }
        } else {
            // Add Mode
            editingProviderId = null;
            document.getElementById('provider-form-title').textContent = 'Add Email Provider';
            document.getElementById('provider-form-method').value = 'POST';
            resetPasswordFields();

            // Trigger type change
            if (providerTypeSelect) {
                providerTypeSelect.dispatchEvent(new Event('change'));
            }
        }

        providerFormContainer.classList.remove('d-none');
        addProviderBtn.classList.add('d-none');

        // Scroll to form
        providerFormContainer.scrollIntoView({ behavior: 'smooth' });
    }

    function hideProviderForm() {
        providerFormContainer.classList.add('d-none');
        addProviderBtn.classList.remove('d-none');
        editingProviderId = null;
        providerForm.reset();
    }

    function updateConfigVisibility(selectedProvider) {
        // Hide all config sections
        document.querySelectorAll('.provider-config').forEach(section => {
            section.classList.add('d-none');
        });

        // Show selected provider config
        if (selectedProvider) {
            const configSection = document.getElementById(`${selectedProvider}-config`);
            if (configSection) {
                configSection.classList.remove('d-none');
            }
        }
    }

    function handleEditProvider(id) {
        // Show loading
        Swal.fire({
            title: 'Loading...',
            text: 'Fetching provider details',
            allowOutsideClick: false,
            showConfirmButton: false,
            willOpen: () => Swal.showLoading()
        });

        fetch(`/settings/email-providers/${id}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.provider) {
                    showProviderForm(data.provider);
                } else {
                    throw new Error('Provider data not found');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to load provider details',
                    icon: 'error',
                    customClass: { confirmButton: 'btn btn-primary' }
                });
            });
    }

    function handleTestProvider(id, name) {
        Swal.fire({
            title: `Test ${name}`,
            html: '<input type="email" id="test-email-input" class="swal2-input" placeholder="Enter recipient email">',
            confirmButtonText: 'Send Test Email',
            showCancelButton: true,
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-light'
            },
            preConfirm: () => {
                const email = document.getElementById('test-email-input').value;
                if (!email) {
                    Swal.showValidationMessage('Please enter an email address');
                    return false;
                }
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    Swal.showValidationMessage('Please enter a valid email address');
                    return false;
                }
                return email;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show sending state
                Swal.fire({
                    title: 'Sending...',
                    text: 'Please wait while we send the test email',
                    allowOutsideClick: false,
                    showConfirmButton: false,
                    willOpen: () => Swal.showLoading()
                });

                fetch(`/settings/email-providers/${id}/test`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email: result.value })
                })
                    .then(response => {
                        if (!response.ok) return response.json().then(err => { throw err; });
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: data.message,
                                icon: 'success',
                                customClass: { confirmButton: 'btn btn-primary' }
                            }).then(() => window.location.reload());
                        } else {
                            throw new Error(data.message || 'An error occurred');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        icon = 'error';
                        Swal.fire({
                            title: 'Error!',
                            text: error.message || 'Failed to send test email',
                            icon: 'error',
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
                    });
            }
        });
    }

    function handleToggleProvider(id, name, currentStatus) {
        // currentStatus comes from dataset as string "1" or "0"
        const isActive = currentStatus === '1';
        const action = isActive ? 'deactivate' : 'activate';

        Swal.fire({
            title: `${action.charAt(0).toUpperCase() + action.slice(1)} Provider?`,
            text: `Are you sure you want to ${action} ${name}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: `Yes, ${action}`,
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-light'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                fetch(`/settings/email-providers/${id}/toggle`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                    .then(response => {
                        if (!response.ok) return response.json().then(err => { throw err; });
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: data.message,
                                icon: 'success',
                                customClass: { confirmButton: 'btn btn-primary' }
                            }).then(() => window.location.reload());
                        } else {
                            throw new Error(data.message || 'An error occurred');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: error.message || 'Failed to toggle provider status',
                            icon: 'error',
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
                    });
            }
        });
    }

    function handleDeleteProvider(id, name) {
        Swal.fire({
            title: 'Delete Provider?',
            text: `Are you sure you want to delete ${name}? This action cannot be undone.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-danger',
                cancelButton: 'btn btn-light'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const deleteForm = new FormData();
                deleteForm.append('_method', 'DELETE');

                fetch(`/settings/email-providers/${id}`, {
                    method: 'POST',
                    body: deleteForm,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                    .then(response => {
                        if (!response.ok) return response.json().then(err => { throw err; });
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                title: 'Deleted!',
                                text: data.message,
                                icon: 'success',
                                customClass: { confirmButton: 'btn btn-primary' }
                            }).then(() => window.location.reload());
                        } else {
                            throw new Error(data.message || 'An error occurred');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            title: 'Error!',
                            text: error.message || 'Failed to delete provider',
                            icon: 'error',
                            customClass: { confirmButton: 'btn btn-primary' }
                        });
                    });
            }
        });
    }

    function submitProviderForm(form, id) {
        const formData = new FormData(form);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        // Determine URL and method
        let url = '/settings/email-providers';
        if (id) {
            url = `/settings/email-providers/${id}`;
            formData.append('_method', 'PUT');
        }

        // Show loading state
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

        fetch(url, {
            method: 'POST', // Always POST for FormData, Laravel handles _method for PUT/DELETE
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
            .then(response => {
                if (!response.ok) return response.json().then(err => { throw err; });
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        customClass: { confirmButton: 'btn btn-primary' }
                    }).then(() => window.location.reload());
                } else {
                    throw new Error(data.message || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                let errorMessage = 'An error occurred while saving the email provider';

                if (error.errors) {
                    const errorMessages = Object.values(error.errors).flat();
                    errorMessage = errorMessages.join('<br>');
                } else if (error.message) {
                    errorMessage = error.message;
                }

                Swal.fire({
                    title: 'Error!',
                    html: errorMessage,
                    icon: 'error',
                    customClass: { confirmButton: 'btn btn-primary' }
                });
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
    }
}

/**
 * Populate provider form with existing data
 */
/**
 * Populate provider form with existing data
 */
function populateProviderForm(provider) {
    document.getElementById('provider-type').value = provider.provider;
    document.getElementById('provider-name').value = provider.name;
    document.getElementById('priority').value = provider.priority || 0;
    document.getElementById('daily-limit').value = provider.daily_limit || '';
    document.getElementById('monthly-limit').value = provider.monthly_limit || '';
    document.getElementById('is-active').checked = provider.is_active;
    document.getElementById('provider-form-method').value = 'PUT';

    // Trigger provider type change to show correct config section
    const providerTypeEl = document.getElementById('provider-type');
    if (providerTypeEl) providerTypeEl.dispatchEvent(new Event('change'));

    // Populate provider-specific config
    const config = provider.config || {};

    // Common fields (these live in their own shared section, not inside provider-specific divs)
    if (config.from_address) {
        const fromAddressInput = document.getElementById('from-address');
        if (fromAddressInput) fromAddressInput.value = config.from_address;
    }
    if (config.from_name) {
        const fromNameInput = document.getElementById('from-name');
        if (fromNameInput) fromNameInput.value = config.from_name;
    }

    // Provider-specific fields
    switch (provider.provider) {
        case 'smtp':
            if (config.host) document.getElementById('smtp-host').value = config.host;
            if (config.port) document.getElementById('smtp-port').value = config.port;
            if (config.encryption) document.getElementById('smtp-encryption').value = config.encryption;
            if (config.username) document.getElementById('smtp-username').value = config.username;
            if (config.timeout) document.getElementById('smtp-timeout').value = config.timeout;
            if (config.local_domain) document.getElementById('smtp-local-domain').value = config.local_domain;

            // IMAP settings
            if (config.imap_host) document.getElementById('imap-host').value = config.imap_host;
            if (config.imap_port) document.getElementById('imap-port').value = config.imap_port;
            if (config.imap_encryption) document.getElementById('imap-encryption').value = config.imap_encryption;
            break;

        case 'mailgun':
            if (config.domain) document.getElementById('mailgun-domain').value = config.domain;
            if (config.endpoint) document.getElementById('mailgun-endpoint').value = config.endpoint;
            break;

        case 'sendgrid':
            // Only API key, managed by password field logic
            break;

        case 'ses':
            if (config.key) document.getElementById('ses-key').value = config.key;
            if (config.region) document.getElementById('ses-region').value = config.region;
            break;
    }
}

/**
 * Reset password field placeholders
 */
function resetPasswordFields() {
    const passwordInputs = document.querySelectorAll('input[name="config[password]"], input[name="config[secret]"], input[name="config[api_key]"]');
    passwordInputs.forEach(input => {
        input.placeholder = '';
        input.value = '';
    });
}