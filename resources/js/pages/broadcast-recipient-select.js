/**
 * Broadcast Recipient Selection Component
 * Provides search and selection functionality for broadcast recipients
 */
import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', function() {
    initializeRecipientSelection();
    initializeRecipientSearch();
    initializeRecipientFilters();
    initializeRecipientCount();
});

/**
 * Initialize recipient selection functionality
 */
function initializeRecipientSelection() {
    // Individual user selection
    document.querySelectorAll('.select-user').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectedUsers();
            updateRecipientCount();
        });
    });

    // Role selection
    document.querySelectorAll('.select-role').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const roleId = this.value;
            const isChecked = this.checked;
            
            // Select/deselect all users in this role
            document.querySelectorAll(`.user-role-${roleId}`).forEach(userCheckbox => {
                userCheckbox.checked = isChecked;
                userCheckbox.dispatchEvent(new Event('change'));
            });
            
            updateRecipientCount();
        });
    });

    // Department selection
    document.querySelectorAll('.select-department').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const deptId = this.value;
            const isChecked = this.checked;
            
            // Select/deselect all users in this department
            document.querySelectorAll(`.user-department-${deptId}`).forEach(userCheckbox => {
                userCheckbox.checked = isChecked;
                userCheckbox.dispatchEvent(new Event('change'));
            });
            
            updateRecipientCount();
        });
    });

    // Select all users
    const selectAllBtn = document.getElementById('select-all-users');
    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            selectAllUsers();
        });
    }

    // Deselect all users
    const deselectAllBtn = document.getElementById('deselect-all-users');
    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            deselectAllUsers();
        });
    }

    // Save selection
    const saveSelectionBtn = document.getElementById('save-recipient-selection');
    if (saveSelectionBtn) {
        saveSelectionBtn.addEventListener('click', function(e) {
            e.preventDefault();
            saveRecipientSelection();
        });
    }
}

/**
 * Initialize recipient search functionality
 */
function initializeRecipientSearch() {
    const searchInput = document.getElementById('recipient-search');
    if (!searchInput) return;

    let debounceTimer;
    
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            searchRecipients(this.value);
        }, 300);
    });

    // Clear search button
    const clearSearchBtn = document.getElementById('clear-recipient-search');
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            searchRecipients('');
        });
    }
}

/**
 * Search recipients by name, email, or role
 */
function searchRecipients(query) {
    const userRows = document.querySelectorAll('.user-row');
    const noResults = document.getElementById('no-users-found');
    
    if (!query.trim()) {
        // Show all users
        userRows.forEach(row => {
            row.classList.remove('d-none');
        });
        if (noResults) noResults.classList.add('d-none');
        return;
    }
    
    const searchTerm = query.toLowerCase();
    let foundCount = 0;
    
    userRows.forEach(row => {
        const userName = row.querySelector('.user-name').textContent.toLowerCase();
        const userEmail = row.querySelector('.user-email').textContent.toLowerCase();
        const userRole = row.querySelector('.user-role').textContent.toLowerCase();
        
        if (userName.includes(searchTerm) || 
            userEmail.includes(searchTerm) || 
            userRole.includes(searchTerm)) {
            row.classList.remove('d-none');
            foundCount++;
        } else {
            row.classList.add('d-none');
        }
    });
    
    // Show/hide no results message
    if (noResults) {
        if (foundCount === 0) {
            noResults.classList.remove('d-none');
        } else {
            noResults.classList.add('d-none');
        }
    }
}

/**
 * Initialize recipient filters
 */
function initializeRecipientFilters() {
    // Role filter
    const roleFilter = document.getElementById('filter-by-role');
    if (roleFilter) {
        roleFilter.addEventListener('change', function() {
            filterRecipientsByRole(this.value);
        });
    }

    // Department filter
    const deptFilter = document.getElementById('filter-by-department');
    if (deptFilter) {
        deptFilter.addEventListener('change', function() {
            filterRecipientsByDepartment(this.value);
        });
    }

    // Status filter
    const statusFilter = document.getElementById('filter-by-status');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            filterRecipientsByStatus(this.value);
        });
    }

    // Clear filters button
    const clearFiltersBtn = document.getElementById('clear-recipient-filters');
    if (clearFiltersBtn) {
        clearFiltersBtn.addEventListener('click', function() {
            clearRecipientFilters();
        });
    }
}

/**
 * Filter recipients by role
 */
function filterRecipientsByRole(roleId) {
    const userRows = document.querySelectorAll('.user-row');
    
    if (!roleId) {
        // Show all users
        userRows.forEach(row => {
            row.classList.remove('d-none');
        });
        return;
    }
    
    userRows.forEach(row => {
        const userRole = row.querySelector('.user-role').getAttribute('data-role-id');
        if (userRole === roleId) {
            row.classList.remove('d-none');
        } else {
            row.classList.add('d-none');
        }
    });
    
    updateVisibleCount();
}

/**
 * Filter recipients by department
 */
function filterRecipientsByDepartment(deptId) {
    const userRows = document.querySelectorAll('.user-row');
    
    if (!deptId) {
        // Show all users
        userRows.forEach(row => {
            row.classList.remove('d-none');
        });
        return;
    }
    
    userRows.forEach(row => {
        const userDept = row.querySelector('.user-department').getAttribute('data-dept-id');
        if (userDept === deptId) {
            row.classList.remove('d-none');
        } else {
            row.classList.add('d-none');
        }
    });
    
    updateVisibleCount();
}

/**
 * Filter recipients by status
 */
function filterRecipientsByStatus(status) {
    const userRows = document.querySelectorAll('.user-row');
    
    if (!status) {
        // Show all users
        userRows.forEach(row => {
            row.classList.remove('d-none');
        });
        return;
    }
    
    userRows.forEach(row => {
        const userStatus = row.querySelector('.user-status').getAttribute('data-status');
        if (userStatus === status) {
            row.classList.remove('d-none');
        } else {
            row.classList.add('d-none');
        }
    });
    
    updateVisibleCount();
}

/**
 * Clear all recipient filters
 */
function clearRecipientFilters() {
    // Reset filter dropdowns
    const roleFilter = document.getElementById('filter-by-role');
    const deptFilter = document.getElementById('filter-by-department');
    const statusFilter = document.getElementById('filter-by-status');
    
    if (roleFilter) roleFilter.value = '';
    if (deptFilter) deptFilter.value = '';
    if (statusFilter) statusFilter.value = '';
    
    // Show all users
    const userRows = document.querySelectorAll('.user-row');
    userRows.forEach(row => {
        row.classList.remove('d-none');
    });
    
    // Clear search
    const searchInput = document.getElementById('recipient-search');
    if (searchInput) {
        searchInput.value = '';
    }
    
    updateVisibleCount();
}

/**
 * Update visible user count
 */
function updateVisibleCount() {
    const visibleUsers = document.querySelectorAll('.user-row:not(.d-none)');
    const visibleCount = visibleUsers.length;
    const totalCount = document.querySelectorAll('.user-row').length;
    
    const countElement = document.getElementById('visible-users-count');
    if (countElement) {
        countElement.textContent = `${visibleCount} of ${totalCount} users`;
    }
}

/**
 * Update selected users list
 */
function updateSelectedUsers() {
    const selectedUsers = [];
    const selectedCheckboxes = document.querySelectorAll('.select-user:checked');
    
    selectedCheckboxes.forEach(checkbox => {
        const userId = checkbox.value;
        const userName = checkbox.closest('tr').querySelector('.user-name').textContent;
        const userEmail = checkbox.closest('tr').querySelector('.user-email').textContent;
        
        selectedUsers.push({
            id: userId,
            name: userName,
            email: userEmail
        });
    });
    
    // Update selected users display
    updateSelectedUsersDisplay(selectedUsers);
    
    // Update hidden input for form submission
    updateSelectedUsersInput(selectedUsers);
}

/**
 * Update selected users display
 */
function updateSelectedUsersDisplay(selectedUsers) {
    const selectedList = document.getElementById('selected-users-list');
    if (!selectedList) return;
    
    if (selectedUsers.length === 0) {
        selectedList.innerHTML = '<div class="text-muted">No users selected</div>';
        return;
    }
    
    let html = '';
    selectedUsers.slice(0, 10).forEach(user => {
        html += `
            <div class="selected-user-item">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${user.name}</strong>
                        <div class="text-muted small">${user.email}</div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-danger remove-user" data-user-id="${user.id}">
                        <i class="ri-close-line"></i>
                    </button>
                </div>
            </div>
        `;
    });
    
    if (selectedUsers.length > 10) {
        html += `<div class="text-muted small">+ ${selectedUsers.length - 10} more users</div>`;
    }
    
    selectedList.innerHTML = html;
    
    // Add event listeners to remove buttons
    document.querySelectorAll('.remove-user').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            deselectUser(userId);
        });
    });
}

/**
 * Update selected users hidden input
 */
function updateSelectedUsersInput(selectedUsers) {
    const selectedInput = document.getElementById('selected_users');
    if (selectedInput) {
        selectedInput.value = JSON.stringify(selectedUsers.map(user => user.id));
    }
}

/**
 * Select all users
 */
function selectAllUsers() {
    Swal.fire({
        title: 'Select all users?',
        text: `This will select all ${document.querySelectorAll('.user-row').length} users.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, select all',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-light'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.querySelectorAll('.select-user').forEach(checkbox => {
                checkbox.checked = true;
                checkbox.dispatchEvent(new Event('change'));
            });
            
            document.querySelectorAll('.select-role').forEach(checkbox => {
                checkbox.checked = true;
            });
            
            document.querySelectorAll('.select-department').forEach(checkbox => {
                checkbox.checked = true;
            });
            
            updateRecipientCount();
            
            Swal.fire({
                title: 'Selected!',
                text: 'All users have been selected.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

/**
 * Deselect all users
 */
function deselectAllUsers() {
    Swal.fire({
        title: 'Deselect all users?',
        text: 'This will clear all user selections.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, clear all',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-light'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            document.querySelectorAll('.select-user').forEach(checkbox => {
                checkbox.checked = false;
                checkbox.dispatchEvent(new Event('change'));
            });
            
            document.querySelectorAll('.select-role').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            document.querySelectorAll('.select-department').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            updateRecipientCount();
            
            Swal.fire({
                title: 'Cleared!',
                text: 'All selections have been cleared.',
                icon: 'success',
                timer: 1500,
                showConfirmButton: false
            });
        }
    });
}

/**
 * Deselect a specific user
 */
function deselectUser(userId) {
    const checkbox = document.querySelector(`.select-user[value="${userId}"]`);
    if (checkbox) {
        checkbox.checked = false;
        checkbox.dispatchEvent(new Event('change'));
    }
}

/**
 * Initialize recipient count display
 */
function initializeRecipientCount() {
    updateRecipientCount();
}

/**
 * Update recipient count display
 */
function updateRecipientCount() {
    const selectedCount = document.querySelectorAll('.select-user:checked').length;
    const totalCount = document.querySelectorAll('.select-user').length;
    
    // Update count badge
    const countBadge = document.getElementById('selected-count-badge');
    if (countBadge) {
        countBadge.textContent = selectedCount;
        if (selectedCount > 0) {
            countBadge.classList.remove('d-none');
        } else {
            countBadge.classList.add('d-none');
        }
    }
    
    // Update count text
    const countText = document.getElementById('selected-count-text');
    if (countText) {
        countText.textContent = `${selectedCount} user${selectedCount !== 1 ? 's' : ''} selected`;
    }
    
    // Update form summary
    const formSummary = document.getElementById('recipient-summary');
    if (formSummary) {
        if (selectedCount === 0) {
            formSummary.textContent = 'No recipients selected';
            formSummary.className = 'text-danger';
        } else if (selectedCount === totalCount) {
            formSummary.textContent = `All ${selectedCount} users selected`;
            formSummary.className = 'text-success';
        } else {
            formSummary.textContent = `${selectedCount} of ${totalCount} users selected`;
            formSummary.className = 'text-primary';
        }
    }
}

/**
 * Save recipient selection
 */
function saveRecipientSelection() {
    const selectedCount = document.querySelectorAll('.select-user:checked').length;
    
    if (selectedCount === 0) {
        Swal.fire({
            title: 'No Recipients Selected',
            text: 'Please select at least one recipient before saving.',
            icon: 'warning',
            customClass: {
                confirmButton: 'btn btn-primary'
            }
        });
        return;
    }
    
    // Get selected user IDs
    const selectedUserIds = [];
    document.querySelectorAll('.select-user:checked').forEach(checkbox => {
        selectedUserIds.push(checkbox.value);
    });
    
    // Get selected roles
    const selectedRoleIds = [];
    document.querySelectorAll('.select-role:checked').forEach(checkbox => {
        selectedRoleIds.push(checkbox.value);
    });
    
    // Get selected departments
    const selectedDeptIds = [];
    document.querySelectorAll('.select-department:checked').forEach(checkbox => {
        selectedDeptIds.push(checkbox.value);
    });
    
    // Create selection data
    const selectionData = {
        user_ids: selectedUserIds,
        role_ids: selectedRoleIds,
        department_ids: selectedDeptIds,
        total_recipients: selectedCount,
        selection_timestamp: new Date().toISOString()
    };
    
    // Save to localStorage for persistence
    localStorage.setItem('broadcast_recipient_selection', JSON.stringify(selectionData));
    
    // Update form fields
    updateFormFields(selectionData);
    
    // Show success message
    Swal.fire({
        title: 'Selection Saved!',
        text: `Recipient selection saved (${selectedCount} users)`,
        icon: 'success',
        timer: 2000,
        showConfirmButton: false
    });
}

/**
 * Update form fields with selection data
 */
function updateFormFields(selectionData) {
    // Update hidden inputs
    const usersInput = document.getElementById('selected_user_ids');
    const rolesInput = document.getElementById('selected_role_ids');
    const departmentsInput = document.getElementById('selected_department_ids');
    
    if (usersInput) {
        usersInput.value = JSON.stringify(selectionData.user_ids);
    }
    
    if (rolesInput) {
        rolesInput.value = JSON.stringify(selectionData.role_ids);
    }
    
    if (departmentsInput) {
        departmentsInput.value = JSON.stringify(selectionData.department_ids);
    }
    
    // Update summary display
    const summaryElement = document.getElementById('saved-selection-summary');
    if (summaryElement) {
        let summaryHtml = `
            <div class="alert alert-success">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${selectionData.total_recipients} recipients selected</strong>
                        <div class="small">
                            ${selectionData.user_ids.length} individual users, 
                            ${selectionData.role_ids.length} roles, 
                            ${selectionData.department_ids.length} departments
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-success" id="edit-selection">
                        Edit Selection
                    </button>
                </div>
            </div>
        `;
        
        summaryElement.innerHTML = summaryHtml;
        
        // Add event listener to edit button
        const editBtn = document.getElementById('edit-selection');
        if (editBtn) {
            editBtn.addEventListener('click', function() {
                // Scroll to selection section
                const selectionSection = document.getElementById('recipient-selection-section');
                if (selectionSection) {
                    selectionSection.scrollIntoView({ behavior: 'smooth' });
                }
            });
        }
    }
}
}

/**
* Load saved selection from localStorage
*/
function loadSavedSelection() {
const savedSelection = localStorage.getItem('broadcast_recipient_selection');
if (!savedSelection) return;

try {
    const selectionData = JSON.parse(savedSelection);
    
    // Check if selection is recent (within last hour)
    const selectionTime = new Date(selectionData.selection_timestamp);
    const now = new Date();
    const hoursDiff = (now - selectionTime) / (1000 * 60 * 60);
    
    if (hoursDiff > 1) {
        // Selection is too old, clear it
        localStorage.removeItem('broadcast_recipient_selection');
        return;
    }
    
    // Restore selection
    selectionData.user_ids.forEach(userId => {
        const checkbox = document.querySelector(`.select-user[value="${userId}"]`);
        if (checkbox) {
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change'));
        }
    });
    
    selectionData.role_ids.forEach(roleId => {
        const checkbox = document.querySelector(`.select-role[value="${roleId}"]`);
        if (checkbox) {
            checkbox.checked = true;
        }
    });
    
    selectionData.department_ids.forEach(deptId => {
        const checkbox = document.querySelector(`.select-department[value="${deptId}"]`);
        if (checkbox) {
            checkbox.checked = true;
        }
    });
    
    // Update UI
    updateRecipientCount();
    updateSelectedUsers();
    
    // Show restored message
    const Toast = Swal.mixin({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
    
    Toast.fire({
        icon: 'info',
        title: `Restored previous selection (${selectionData.total_recipients} recipients)`
    });
    
} catch (error) {
    console.error('Error loading saved selection:', error);
    localStorage.removeItem('broadcast_recipient_selection');
}
}

// Load saved selection on page load
window.addEventListener('load', function() {
loadSavedSelection();
});