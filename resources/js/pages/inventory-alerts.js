/**
 * Inventory Alerts - DataTables and AJAX handlers for alert management
 */

import DataTable from 'datatables.net';
import 'datatables.net-bs5';
import 'datatables.net-responsive';
import 'datatables.net-responsive-bs5';
import 'datatables.net-select';
import 'datatables.net-select-bs5';
import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', () => {
    // Initialize DataTables for alerts table if present
    const alertsTable = document.getElementById('alertsTable');
    if (alertsTable) {
        const table = new DataTable(alertsTable, {
            language: {
                paginate: {
                    first: '<i class="ti ti-chevrons-left"></i>',
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next: '<i class="ti ti-chevron-right"></i>',
                    last: '<i class="ti ti-chevrons-right"></i>',
                },
                lengthMenu: '_MENU_ alerts per page',
                info: 'Showing <span class="fw-semibold">_START_</span> to <span class="fw-semibold">_END_</span> of <span class="fw-semibold">_TOTAL_</span> alerts',
                search: '',
                searchPlaceholder: 'Search alerts...',
            },
            pageLength: 10,
            responsive: true,
            order: [[1, 'desc']], // Sort by ID descending
            select: {
                style: 'multi',
                selector: 'td:first-child .alert-checkbox',
            },
            columnDefs: [
                {
                    orderable: false,
                    className: 'select-checkbox',
                    targets: 0,
                },
                {
                    responsivePriority: 1,
                    targets: [1, 2, 3], // ID, Type, Severity
                },
                {
                    responsivePriority: 2,
                    targets: [4, 5], // Message, Supply
                },
                {
                    responsivePriority: 3,
                    targets: [6, 7, 8], // Stock, Created, Status
                },
                {
                    orderable: false,
                    targets: [0, 9], // Checkbox and Actions columns
                },
            ],
        });

        // Update bulk resolve button when selection changes
        table.on('select deselect', function() {
            updateBulkResolveButton();
        });

        // Search functionality
        const searchInput = document.querySelector('input[data-table-search]');
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                table.search(this.value).draw();
            });
        }
    }

    // Handle resolve modal form submission via AJAX
    const resolveForms = document.querySelectorAll('form[id^="resolveForm"]');
    resolveForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Resolving...';
            submitButton.disabled = true;

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    const modalId = this.closest('.modal').id;
                    const modal = bootstrap.Modal.getInstance(document.getElementById(modalId));
                    if (modal) modal.hide();

                    // Show success message
                    Swal.fire({
                        title: 'Alert Resolved',
                        text: data.message || 'Alert has been successfully resolved.',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-primary',
                        },
                    }).then(() => {
                        // Reload page to reflect updated status
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to resolve alert.');
                }
            })
            .catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: error.message,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn btn-danger',
                    },
                });
            })
            .finally(() => {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            });
        });
    });

    // Bulk resolve functionality
    const bulkResolveBtn = document.getElementById('bulkResolveBtn');
    if (bulkResolveBtn) {
        bulkResolveBtn.addEventListener('click', function() {
            const selectedCheckboxes = document.querySelectorAll('.alert-checkbox:checked');
            if (selectedCheckboxes.length === 0) {
                Swal.fire({
                    title: 'No Alerts Selected',
                    text: 'Please select at least one alert to resolve.',
                    icon: 'warning',
                    confirmButtonText: 'OK',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn btn-primary',
                    },
                });
                return;
            }

            const alertIds = Array.from(selectedCheckboxes).map(cb => cb.value);
            const alertNames = Array.from(selectedCheckboxes).map(cb => {
                const row = cb.closest('tr');
                return row.querySelector('.alert-message').textContent.trim();
            });

            // Show confirmation modal
            Swal.fire({
                title: 'Resolve Selected Alerts',
                html: `
                    <p>You are about to resolve <strong>${alertIds.length}</strong> alert(s).</p>
                    <div class="alert alert-info mt-3">
                        <small>
                            ${alertNames.slice(0, 3).map(name => 
                                `<div>• ${name.length > 50 ? name.substring(0, 50) + '...' : name}</div>`
                            ).join('')}
                            ${alertNames.length > 3 ? `<div>... and ${alertNames.length - 3} more</div>` : ''}
                        </small>
                    </div>
                    <div class="mt-3">
                        <label for="bulkResolutionNotes" class="form-label">Resolution Notes (Optional)</label>
                        <textarea id="bulkResolutionNotes" class="form-control" rows="3" placeholder="Add any notes about the resolution..."></textarea>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Resolve Selected',
                cancelButtonText: 'Cancel',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-secondary',
                },
                preConfirm: () => {
                    const notes = document.getElementById('bulkResolutionNotes').value;
                    
                    return fetch('/inventory/alerts/bulk-resolve', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        },
                        body: JSON.stringify({
                            alert_ids: alertIds,
                            resolution_notes: notes,
                        }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (!data.success) {
                            throw new Error(data.message || 'Failed to resolve alerts.');
                        }
                        return data;
                    });
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Alerts Resolved',
                        text: result.value.message || 'Selected alerts have been resolved.',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-primary',
                        },
                    }).then(() => {
                        window.location.reload();
                    });
                }
            }).catch(error => {
                Swal.fire({
                    title: 'Error',
                    text: error.message,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn btn-danger',
                    },
                });
            });
        });
    }

    // Export functionality
    const exportBtn = document.querySelector('a[href*="export"]');
    if (exportBtn) {
        exportBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Export Alerts',
                text: 'Preparing export file...',
                icon: 'info',
                showConfirmButton: false,
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                },
            });

            // Add loading parameters to URL
            const url = new URL(this.href);
            url.searchParams.append('exporting', 'true');
            
            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(response => {
                if (response.ok) {
                    return response.blob();
                }
                throw new Error('Export failed');
            })
            .then(blob => {
                Swal.close();
                
                // Create download link
                const downloadUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = downloadUrl;
                a.download = `alerts-export-${new Date().toISOString().slice(0, 10)}.csv`;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(downloadUrl);
            })
            .catch(error => {
                Swal.fire({
                    title: 'Export Failed',
                    text: error.message || 'Failed to export alerts. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'btn btn-danger',
                    },
                });
            });
        });
    }

    // Real-time alert updates (polling for new alerts)
    if (window.location.pathname.includes('/inventory/alerts')) {
        let lastUpdate = new Date().toISOString();
        
        // Check for new alerts every 30 seconds
        setInterval(() => {
            fetch(`/api/alerts/recent?since=${lastUpdate}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    // Update last update time
                    lastUpdate = new Date().toISOString();
                    
                    // Show notification
                    if (data.data.length === 1) {
                        Swal.fire({
                            title: 'New Alert',
                            text: data.data[0].message,
                            icon: 'warning',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true,
                        });
                    } else {
                        Swal.fire({
                            title: 'New Alerts',
                            text: `${data.data.length} new alerts have been generated.`,
                            icon: 'warning',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 5000,
                            timerProgressBar: true,
                        });
                    }
                    
                    // Optionally refresh the table
                    if (alertsTable && table) {
                        table.ajax.reload(null, false); // false = keep current page
                    }
                }
            })
            .catch(() => {
                // Silently fail - network issues are okay for polling
            });
        }, 30000); // 30 seconds
    }

    // Delete confirmation
    window.confirmDelete = function(alertId, alertName) {
        Swal.fire({
            title: 'Confirm Deletion',
            text: `Are you sure you want to delete the alert "${alertName}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-primary me-2',
                cancelButton: 'btn btn-secondary',
            },
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(`delete-form-${alertId}`).submit();
            }
        });
    };

    // Helper function to update bulk resolve button
    function updateBulkResolveButton() {
        if (!bulkResolveBtn) return;
        
        const selectedCount = document.querySelectorAll('.alert-checkbox:checked').length;
        bulkResolveBtn.disabled = selectedCount === 0;
        bulkResolveBtn.innerHTML = `<i class="ti ti-check me-1"></i> Resolve Selected (${selectedCount})`;
    }

    // Initialize bulk resolve button state
    updateBulkResolveButton();
});