/**
 * Inventory Supplies - DataTables and AJAX handlers for supply management
 */

import DataTable from 'datatables.net';
import 'datatables.net-bs5';
import 'datatables.net-responsive';
import 'datatables.net-responsive-bs5';
import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', () => {
    // Initialize DataTables for supplies table if present
    const suppliesTable = document.querySelector('table[data-table="supplies"]');
    if (suppliesTable) {
        new DataTable(suppliesTable, {
            language: {
                paginate: {
                    first: '<i class="ti ti-chevrons-left"></i>',
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next: '<i class="ti ti-chevron-right"></i>',
                    last: '<i class="ti ti-chevrons-right"></i>',
                },
                lengthMenu: '_MENU_ supplies per page',
                info: 'Showing <span class="fw-semibold">_START_</span> to <span class="fw-semibold">_END_</span> of <span class="fw-semibold">_TOTAL_</span> supplies',
                search: '',
                searchPlaceholder: 'Search supplies...',
            },
            pageLength: 10,
            responsive: true,
            order: [[0, 'desc']], // Sort by ID descending
        });
    }

    // Handle stock adjustment modal form submission via AJAX
    const adjustStockForms = document.querySelectorAll('form[id^="adjustStockForm"]');
    adjustStockForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const submitButton = this.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Adjusting...';
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
                    const modal = bootstrap.Modal.getInstance(document.getElementById('adjustStockModal'));
                    if (modal) modal.hide();

                    // Show success message
                    Swal.fire({
                        title: 'Stock Adjusted',
                        text: data.message || 'Stock has been successfully adjusted.',
                        icon: 'success',
                        confirmButtonText: 'OK',
                        buttonsStyling: false,
                        customClass: {
                            confirmButton: 'btn btn-primary',
                        },
                    }).then(() => {
                        // Reload page to reflect updated stock
                        window.location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Failed to adjust stock.');
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

    // Dynamic adjustment type handling
    const adjustmentTypeSelects = document.querySelectorAll('select[id="adjustment_type"]');
    adjustmentTypeSelects.forEach(select => {
        const quantityInput = select.closest('.modal-content').querySelector('input[id="quantity"]');
        if (!quantityInput) return;

        select.addEventListener('change', function() {
            if (this.value === 'set') {
                quantityInput.min = 0;
                quantityInput.placeholder = 'Enter new stock level';
            } else {
                quantityInput.min = 0.01;
                quantityInput.placeholder = 'Enter quantity to ' + this.value;
            }
        });
        // Trigger change on load
        select.dispatchEvent(new Event('change'));
    });

    // Delete confirmation (already in blade, but we can enhance)
    window.confirmDelete = function(supplyId, supplyName) {
        Swal.fire({
            title: 'Confirm Deletion',
            text: `Are you sure you want to delete the supply "${supplyName}"?`,
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
                document.getElementById(`delete-form-${supplyId}`).submit();
            }
        });
    };
});