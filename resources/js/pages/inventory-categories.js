/**
 * Inventory Categories - JavaScript for category management
 */

import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', () => {
    // Search functionality (already handled by custom-table.js)
    // We'll add any additional category-specific interactions

    // Delete confirmation (enhanced)
    window.confirmDelete = function(categoryId, categoryName) {
        Swal.fire({
            title: 'Confirm Deletion',
            text: `Are you sure you want to delete the category "${categoryName}"? This will also delete all subcategories and unassign supplies from this category.`,
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
                document.getElementById(`delete-form-${categoryId}`).submit();
            }
        });
    };

    // Toggle subcategories visibility (optional)
    const categoryCards = document.querySelectorAll('.card.border');
    categoryCards.forEach(card => {
        const header = card.querySelector('.card-header');
        if (header) {
            header.style.cursor = 'pointer';
            header.addEventListener('click', function(e) {
                // Only toggle if not clicking a button
                if (e.target.tagName === 'BUTTON' || e.target.closest('button') || e.target.tagName === 'A' || e.target.closest('a')) {
                    return;
                }
                const body = card.querySelector('.card-body');
                if (body) {
                    body.classList.toggle('d-none');
                }
            });
        }
    });

    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});