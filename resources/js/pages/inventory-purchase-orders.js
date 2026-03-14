/**
 * Inventory Purchase Orders - DataTables and AJAX handlers for purchase order management
 */

import DataTable from 'datatables.net';
import 'datatables.net-bs5';
import 'datatables.net-responsive';
import 'datatables.net-responsive-bs5';
import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', () => {
    // Initialize DataTables for purchase orders table if present
    const purchaseOrdersTable = document.querySelector('table[data-table="purchase-orders"]');
    if (purchaseOrdersTable) {
        new DataTable(purchaseOrdersTable, {
            language: {
                paginate: {
                    first: '<i class="ti ti-chevrons-left"></i>',
                    previous: '<i class="ti ti-chevron-left"></i>',
                    next: '<i class="ti ti-chevron-right"></i>',
                    last: '<i class="ti ti-chevrons-right"></i>',
                },
                lengthMenu: '_MENU_ purchase orders per page',
                info: 'Showing <span class="fw-semibold">_START_</span> to <span class="fw-semibold">_END_</span> of <span class="fw-semibold">_TOTAL_</span> purchase orders',
                search: '',
                searchPlaceholder: 'Search purchase orders...',
            },
            pageLength: 10,
            responsive: true,
            order: [[0, 'desc']], // Sort by PO number descending
        });
    }

    // Handle receive modal form validation
    const receiveModals = document.querySelectorAll('form[id^="receiveForm"]');
    receiveModals.forEach(form => {
        form.addEventListener('submit', function(e) {
            const quantityInputs = this.querySelectorAll('input[name*="quantity_received"]');
            let hasValidQuantity = false;
            
            quantityInputs.forEach(input => {
                const quantity = parseFloat(input.value) || 0;
                const max = parseFloat(input.getAttribute('max')) || 0;
                
                if (quantity > 0) {
                    hasValidQuantity = true;
                    
                    if (quantity > max) {
                        e.preventDefault();
                        Swal.fire({
                            title: 'Validation Error',
                            text: `Cannot receive more than ${max} units for item.`,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                        input.focus();
                        return;
                    }
                }
            });
            
            if (!hasValidQuantity) {
                e.preventDefault();
                Swal.fire({
                    title: 'Validation Error',
                    text: 'Please enter quantity to receive for at least one item.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            }
        });
    });

    // Delete confirmation
    window.confirmDelete = function(poId, poNumber) {
        Swal.fire({
            title: 'Confirm Deletion',
            text: `Are you sure you want to delete purchase order "${poNumber}"?`,
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
                document.getElementById(`delete-form-${poId}`).submit();
            }
        });
    };

    // Approve confirmation
    const approveButtons = document.querySelectorAll('button[data-action="approve"]');
    approveButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            
            Swal.fire({
                title: 'Approve Purchase Order',
                text: 'Are you sure you want to approve this purchase order?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, approve it!',
                cancelButtonText: 'Cancel',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-secondary',
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    // Cancel confirmation
    const cancelButtons = document.querySelectorAll('button[data-action="cancel"]');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            
            Swal.fire({
                title: 'Cancel Purchase Order',
                text: 'Are you sure you want to cancel this purchase order?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'Cancel',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-warning me-2',
                    cancelButton: 'btn btn-secondary',
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });

    // Dynamic line items functionality (if not already handled in blade)
    const addItemBtn = document.getElementById('add-item-btn');
    if (addItemBtn) {
        // This functionality is already implemented in the blade template
        // We'll just ensure the totals are calculated on page load
        calculateTotals();
        
        // Listen for tax and shipping changes
        const taxInput = document.getElementById('tax');
        const shippingInput = document.getElementById('shipping');
        
        if (taxInput) {
            taxInput.addEventListener('input', calculateTotals);
        }
        if (shippingInput) {
            shippingInput.addEventListener('input', calculateTotals);
        }
    }

    // Search functionality for index page
    const searchInput = document.querySelector('[data-table-search]');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    }
});

// Calculate totals for purchase order form
function calculateTotals() {
    let subtotal = 0;
    
    document.querySelectorAll('.item-row').forEach(row => {
        const totalInput = row.querySelector('.total-cost');
        const total = parseFloat(totalInput.value) || 0;
        subtotal += total;
    });
    
    const tax = parseFloat(document.getElementById('tax')?.value) || 0;
    const shipping = parseFloat(document.getElementById('shipping')?.value) || 0;
    const grandTotal = subtotal + tax + shipping;
    
    const subtotalEl = document.getElementById('subtotal');
    const taxEl = document.getElementById('tax-amount');
    const shippingEl = document.getElementById('shipping-amount');
    const grandTotalEl = document.getElementById('grand-total');
    
    if (subtotalEl) subtotalEl.textContent = subtotal.toFixed(2);
    if (taxEl) taxEl.textContent = tax.toFixed(2);
    if (shippingEl) shippingEl.textContent = shipping.toFixed(2);
    if (grandTotalEl) grandTotalEl.textContent = grandTotal.toFixed(2);
}

// Export functions for use in blade templates
window.inventoryPurchaseOrders = {
    calculateTotals,
    confirmDelete
};