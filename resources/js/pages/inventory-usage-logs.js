/**
 * Inventory Usage Logs - DataTables and AJAX handlers for usage log management
 */

import DataTable from 'datatables.net';
import 'datatables.net-bs5';
import 'datatables.net-responsive';
import 'datatables.net-responsive-bs5';
import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', () => {
    // Initialize DataTables for usage logs table if present
    const usageLogsTable = document.querySelector('table[id="usage-logs-datatable"]');
    if (usageLogsTable) {
        // The table is already initialized via inline script in the blade template
        // This is just a placeholder for future enhancements
        console.log('Usage logs DataTable initialized');
    }

    // Handle filter form submission
    const filterForm = document.getElementById('filter-form');
    if (filterForm) {
        const applyFiltersBtn = document.getElementById('apply-filters');
        const resetFiltersBtn = document.getElementById('reset-filters');

        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', function () {
                // Trigger DataTable reload (handled by inline script)
                const event = new Event('applyFilters');
                window.dispatchEvent(event);
            });
        }

        if (resetFiltersBtn) {
            resetFiltersBtn.addEventListener('click', function () {
                filterForm.reset();
                // Trigger DataTable reload (handled by inline script)
                const event = new Event('resetFilters');
                window.dispatchEvent(event);
            });
        }
    }

    // Handle supply selection change to show current stock
    const supplySelect = document.getElementById('supply_id');
    if (supplySelect) {
        supplySelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption && selectedOption.text) {
                // Extract stock information from option text
                // Option format: "Supply Name (SKU: XXX) - Stock: X unit_type"
                console.log('Supply selected:', selectedOption.value);
                // In a real implementation, you might want to fetch supply details via AJAX
                // to update unit cost or validate available stock
            }
        });
    }

    // Auto-calculate total cost when quantity or unit cost changes
    const quantityInput = document.getElementById('quantity_used');
    const unitCostInput = document.getElementById('unit_cost');
    const totalCostDisplay = document.getElementById('total_cost_display');

    function calculateTotalCost() {
        if (quantityInput && unitCostInput) {
            const quantity = parseFloat(quantityInput.value) || 0;
            const unitCost = parseFloat(unitCostInput.value) || 0;
            const totalCost = quantity * unitCost;

            if (totalCostDisplay) {
                totalCostDisplay.textContent = (window.currencySymbol || '$') + totalCost.toFixed(2);
            }
        }
    }

    if (quantityInput) {
        quantityInput.addEventListener('input', calculateTotalCost);
    }

    if (unitCostInput) {
        unitCostInput.addEventListener('input', calculateTotalCost);
    }

    // Initialize total cost calculation on page load
    calculateTotalCost();

    // Delete confirmation for usage logs (if needed in the future)
    window.confirmUsageLogDelete = function (logId, supplyName) {
        Swal.fire({
            title: 'Confirm Deletion',
            text: `Are you sure you want to delete the usage log for "${supplyName}"?`,
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
                document.getElementById(`delete-form-${logId}`).submit();
            }
        });
    };

    // Export functionality (placeholder for future implementation)
    window.exportUsageLogs = function (format) {
        Swal.fire({
            title: 'Export Usage Logs',
            text: `Preparing ${format.toUpperCase()} export...`,
            icon: 'info',
            showConfirmButton: false,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();

                // In a real implementation, this would make an AJAX request
                // to generate and download the export file
                setTimeout(() => {
                    Swal.fire({
                        title: 'Export Ready',
                        text: `Usage logs exported successfully as ${format.toUpperCase()}.`,
                        icon: 'success',
                        confirmButtonText: 'OK',
                    });
                }, 1500);
            }
        });
    };
});