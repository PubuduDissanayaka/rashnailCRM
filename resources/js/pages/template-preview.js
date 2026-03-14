/**
 * Template Preview Component
 * Provides live variable substitution in template preview
 */
import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', function() {
    initializeTemplatePreview();
    initializeVariableInsertion();
    initializeTemplateValidation();
});

/**
 * Initialize template preview functionality
 */
function initializeTemplatePreview() {
    const previewBtn = document.getElementById('preview-template-btn');
    const contentEditor = document.getElementById('content');
    const subjectInput = document.getElementById('subject');
    const previewModal = document.getElementById('template-preview-modal');
    
    if (previewBtn) {
        previewBtn.addEventListener('click', function(e) {
            e.preventDefault();
            generatePreview();
        });
    }
    
    // Auto-update preview when content changes (with debounce)
    if (contentEditor) {
        let debounceTimer;
        contentEditor.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (previewModal && previewModal.classList.contains('show')) {
                    updateLivePreview();
                }
            }, 1000);
        });
    }
    
    if (subjectInput) {
        let debounceTimer;
        subjectInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                if (previewModal && previewModal.classList.contains('show')) {
                    updateLivePreview();
                }
            }, 1000);
        });
    }
}

/**
 * Generate template preview
 */
function generatePreview() {
    const content = document.getElementById('content').value;
    const subject = document.getElementById('subject').value;
    const templateType = document.getElementById('type').value;
    
    if (!content.trim()) {
        Swal.fire({
            title: 'No Content',
            text: 'Please enter template content before previewing.',
            icon: 'warning',
            customClass: {
                confirmButton: 'btn btn-primary'
            }
        });
        return;
    }
    
    // Collect variable values
    const variableValues = collectVariableValues();
    
    // Process template with variables
    const processedContent = processTemplate(content, variableValues);
    const processedSubject = processTemplate(subject, variableValues);
    
    // Display preview
    displayPreview(processedContent, processedSubject, templateType);
}

/**
 * Update live preview in modal
 */
function updateLivePreview() {
    const content = document.getElementById('content').value;
    const subject = document.getElementById('subject').value;
    const variableValues = collectVariableValues();
    
    const processedContent = processTemplate(content, variableValues);
    const processedSubject = processTemplate(subject, variableValues);
    
    // Update preview modal content
    const previewContent = document.getElementById('preview-content');
    const previewSubject = document.getElementById('preview-subject');
    
    if (previewContent) {
        previewContent.innerHTML = processedContent;
    }
    
    if (previewSubject) {
        previewSubject.textContent = processedSubject || '(No subject)';
    }
}

/**
 * Collect variable values from form
 */
function collectVariableValues() {
    const variables = {};
    
    // Get values from variable inputs
    document.querySelectorAll('.variable-input').forEach(input => {
        const varName = input.getAttribute('data-variable');
        variables[varName] = input.value || `[${varName}]`;
    });
    
    // Add default values for common variables
    const defaultVariables = {
        'user_name': 'John Doe',
        'user_email': 'john@example.com',
        'company_name': 'Your Company',
        'current_date': new Date().toLocaleDateString(),
        'current_time': new Date().toLocaleTimeString(),
        'appointment_date': '2024-01-15',
        'appointment_time': '10:00 AM',
        'service_name': 'Haircut',
        'staff_name': 'Jane Smith',
        'location': 'Main Branch',
        'amount': '$50.00',
        'invoice_number': 'INV-2024-001',
        'order_number': 'ORD-2024-001',
        'tracking_number': 'TRK-123456789',
        'reset_link': 'https://example.com/reset-password?token=abc123',
        'verification_link': 'https://example.com/verify-email?token=xyz789',
        'login_link': 'https://example.com/login',
        'support_email': 'support@example.com',
        'phone_number': '+1 (555) 123-4567',
        'address': '123 Main St, City, State 12345'
    };
    
    // Merge with defaults (user values override defaults)
    return { ...defaultVariables, ...variables };
}

/**
 * Process template with variable substitution
 */
function processTemplate(template, variables) {
    if (!template) return '';
    
    let processed = template;
    
    // Replace {{variable}} syntax
    Object.keys(variables).forEach(varName => {
        const pattern = new RegExp(`{{${varName}}}`, 'gi');
        processed = processed.replace(pattern, variables[varName]);
    });
    
    // Replace {variable} syntax (without double braces)
    Object.keys(variables).forEach(varName => {
        const pattern = new RegExp(`{${varName}}`, 'gi');
        processed = processed.replace(pattern, variables[varName]);
    });
    
    // Replace [variable] syntax
    Object.keys(variables).forEach(varName => {
        const pattern = new RegExp(`\\[${varName}\\]`, 'gi');
        processed = processed.replace(pattern, variables[varName]);
    });
    
    // Convert line breaks to HTML
    processed = processed.replace(/\n/g, '<br>');
    
    return processed;
}

/**
 * Display preview in modal
 */
function displayPreview(content, subject, templateType) {
    // Create or update preview modal
    let previewModal = document.getElementById('template-preview-modal');
    
    if (!previewModal) {
        previewModal = document.createElement('div');
        previewModal.className = 'modal fade';
        previewModal.id = 'template-preview-modal';
        previewModal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Template Preview</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="preview-container">
                            <div class="preview-header mb-3">
                                <h6 class="text-muted mb-1">Subject:</h6>
                                <h5 id="preview-subject" class="mb-3">${subject || '(No subject)'}</h5>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="badge bg-primary">${templateType || 'General'}</span>
                                    <small class="text-muted">Live Preview: Updates as you type</small>
                                </div>
                            </div>
                            <div class="preview-content border rounded p-4 bg-light">
                                <div id="preview-content">${content}</div>
                            </div>
                            <div class="preview-variables mt-4">
                                <h6 class="text-muted mb-2">Variable Values Used:</h6>
                                <div id="variable-values" class="row"></div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-primary" id="send-test-email">
                            <i class="ri-send-plane-line me-1"></i> Send Test Email
                        </button>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(previewModal);
        
        // Initialize test email button
        const testEmailBtn = document.getElementById('send-test-email');
        if (testEmailBtn) {
            testEmailBtn.addEventListener('click', function() {
                sendTestEmail(content, subject, templateType);
            });
        }
    } else {
        // Update existing modal
        const previewSubject = document.getElementById('preview-subject');
        const previewContent = document.getElementById('preview-content');
        const typeBadge = previewModal.querySelector('.badge');
        
        if (previewSubject) previewSubject.textContent = subject || '(No subject)';
        if (previewContent) previewContent.innerHTML = content;
        if (typeBadge) typeBadge.textContent = templateType || 'General';
    }
    
    // Update variable values display
    updateVariableValuesDisplay();
    
    // Show modal
    const modal = new bootstrap.Modal(previewModal);
    modal.show();
}

/**
 * Update variable values display in preview modal
 */
function updateVariableValuesDisplay() {
    const variableValuesContainer = document.getElementById('variable-values');
    if (!variableValuesContainer) return;
    
    const variables = collectVariableValues();
    
    let html = '';
    Object.keys(variables).forEach(varName => {
        html += `
            <div class="col-md-6 mb-2">
                <div class="card card-sm">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <code class="text-primary">${varName}</code>
                            <span class="text-muted small">${variables[varName]}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    variableValuesContainer.innerHTML = html;
}

/**
 * Initialize variable insertion functionality
 */
function initializeVariableInsertion() {
    const variableButtons = document.querySelectorAll('.insert-variable');
    
    variableButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const variable = this.getAttribute('data-variable');
            insertVariable(variable);
        });
    });
    
    // Initialize variable list if present
    const variableList = document.getElementById('variable-list');
    if (variableList) {
        populateVariableList();
    }
}

/**
 * Insert variable at cursor position
 */
function insertVariable(variable) {
    const contentEditor = document.getElementById('content');
    const subjectInput = document.getElementById('subject');
    
    const variableSyntax = `{{${variable}}}`;
    
    // Determine which field is focused
    const focusedElement = document.activeElement;
    
    if (focusedElement === contentEditor) {
        insertAtCursor(contentEditor, variableSyntax);
    } else if (focusedElement === subjectInput) {
        insertAtCursor(subjectInput, variableSyntax);
    } else {
        // Default to content editor
        insertAtCursor(contentEditor, variableSyntax);
        contentEditor.focus();
    }
}

/**
 * Insert text at cursor position
 */
function insertAtCursor(element, text) {
    if (!element) return;
    
    if (document.selection) {
        // IE support
        element.focus();
        const sel = document.selection.createRange();
        sel.text = text;
    } else if (element.selectionStart !== undefined) {
        // Modern browsers
        const startPos = element.selectionStart;
        const endPos = element.selectionEnd;
        const scrollTop = element.scrollTop;
        
        element.value = element.value.substring(0, startPos) + 
                       text + 
                       element.value.substring(endPos, element.value.length);
        
        element.selectionStart = startPos + text.length;
        element.selectionEnd = startPos + text.length;
        element.scrollTop = scrollTop;
        
        // Trigger input event for live preview
        element.dispatchEvent(new Event('input', { bubbles: true }));
    } else {
        // Fallback
        element.value += text;
        element.dispatchEvent(new Event('input', { bubbles: true }));
    }
}

/**
 * Populate variable list
 */
function populateVariableList() {
    const variableList = document.getElementById('variable-list');
    if (!variableList) return;
    
    const variables = [
        { name: 'user_name', description: 'Full name of the user' },
        { name: 'user_email', description: 'Email address of the user' },
        { name: 'company_name', description: 'Your company name' },
        { name: 'current_date', description: 'Current date' },
        { name: 'current_time', description: 'Current time' },
        { name: 'appointment_date', description: 'Appointment date' },
        { name: 'appointment_time', description: 'Appointment time' },
        { name: 'service_name', description: 'Service name' },
        { name: 'staff_name', description: 'Staff member name' },
        { name: 'location', description: 'Business location' },
        { name: 'amount', description: 'Payment amount' },
        { name: 'invoice_number', description: 'Invoice number' },
        { name: 'order_number', description: 'Order number' },
        { name: 'tracking_number', description: 'Tracking number' },
        { name: 'reset_link', description: 'Password reset link' },
        { name: 'verification_link', description: 'Email verification link' },
        { name: 'login_link', description: 'Login link' },
        { name: 'support_email', description: 'Support email address' },
        { name: 'phone_number', description: 'Phone number' },
        { name: 'address', description: 'Business address' }
    ];
    
    let html = '<div class="row">';
    variables.forEach(variable => {
        html += `
            <div class="col-md-6 mb-2">
                <div class="card card-sm">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <code class="text-primary">${variable.name}</code>
                                <small class="text-muted d-block">${variable.description}</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary insert-variable" data-variable="${variable.name}">
                                <i class="ri-add-line"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    html += '</div>';
    
    variableList.innerHTML = html;
    
    // Re-attach event listeners
    document.querySelectorAll('.insert-variable').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const variable = this.getAttribute('data-variable');
            insertVariable(variable);
        });
    });
}

/**
 * Initialize template validation
 */
function initializeTemplateValidation() {
    const form = document.getElementById('template-form');
    if (!form) return;
    
    form.addEventListener('submit', function(e) {
        if (!validateTemplate()) {
            e.preventDefault();
        }
    });
}

/**
 * Validate template before submission
 */
function validateTemplate() {
    const content = document.getElementById('content').value;
    const subject = document.getElementById('subject').value;
    const name = document.getElementById('name').value;
    
    let isValid = true;
    let errors = [];
    
    if (!name.trim()) {
        isValid = false;
        errors.push('Template name is required');
    }
    
    if (!subject.trim()) {
        isValid = false;
        errors.push('Subject is required');
    }
    
    if (!content.trim()) {
        isValid = false;
        errors.push('Content is required');
    }
    
    // Check for unclosed variable syntax
    const unclosedVariables = findUnclosedVariables(content);
    if (unclosedVariables.length > 0) {
        isValid = false;
        errors.push(`Unclosed variable syntax: ${unclosedVariables.join(', ')}`);
    }
    
    if (!isValid) {
        Swal.fire({
            title: 'Validation Errors',
            html: `<ul class="text-start">${errors.map(err => `<li>${err}</li>`).join('')}</ul>`,
            icon: 'error',
            customClass: {
                confirmButton: 'btn btn-primary'
            }
        });
    }
    
    return isValid;
}

/**
 * Find unclosed variable syntax in content
 */
function findUnclosedVariables(content) {
    const patterns = [
        { open: '{{', close: '}}' },
        { open: '{', close: '}' },
        { open: '[', close: ']' }
    ];
    
    const errors = [];
    
    patterns.forEach(pattern => {
        const openMatches = (content.match(new RegExp(escapeRegExp(pattern.open), 'g')) || []).length;
        const closeMatches = (content.match(new RegExp(escapeRegExp(pattern.close), 'g')) || []).length;
        
        if (openMatches !== closeMatches) {
            errors.push(`${pattern.open}...${pattern.close}`);
        }
    });
    
    return errors;
}

/**
 * Escape special regex characters
 */
function escapeRegExp(string) {
    return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
}

/**
 * Send test email with template
 */
function sendTestEmail(content, subject, templateType) {
    Swal.fire({
        title: 'Send Test Email',
        html: '<input type="email" id="test-email-input" class="swal2-input" placeholder="Enter recipient email">',
        confirmButtonText: 'Send Test',
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
            if (!validateEmail(email)) {
                Swal.showValidationMessage('Please enter a valid email address');
                return false;
            }
            return email;
        }
    }).then((result) => {
        if (result.isConfirmed) {
            sendTestEmailRequest(result.value, content, subject, templateType);
        }
    });
}

/**
 * Send test email request
 */
function sendTestEmailRequest(email, content, subject, templateType) {
    const button = document.getElementById('send-test-email');
    const originalText = button.innerHTML;
    
    // Show loading state
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Sending...';
    
    fetch('/templates/send-test', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            email: email,
            content: content,
            subject: subject,
            type: templateType
        })
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
        } else {
            throw new Error(data.message || 'Failed to send test email');
        }
    })
    .catch(error => {
        console.error('Error sending test email:', error);
        Swal.fire({
            title: 'Error!',
            text: error.message || 'Failed to send test email',
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
 * Validate email address
 */
function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}