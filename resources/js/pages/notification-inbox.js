/**
 * Notification Inbox Component
 * Provides real-time notification updates and management
 */
import Swal from 'sweetalert2';

document.addEventListener('DOMContentLoaded', function() {
    initializeNotificationInbox();
    initializeNotificationActions();
    initializeRealTimeUpdates();
});

/**
 * Initialize the notification inbox
 */
function initializeNotificationInbox() {
    const inboxContainer = document.getElementById('notification-inbox');
    if (!inboxContainer) return;

    // Load initial notifications
    loadNotifications();

    // Setup refresh button
    const refreshBtn = document.getElementById('refresh-notifications');
    if (refreshBtn) {
        refreshBtn.addEventListener('click', function() {
            loadNotifications();
        });
    }

    // Setup mark all as read button
    const markAllReadBtn = document.getElementById('mark-all-read');
    if (markAllReadBtn) {
        markAllReadBtn.addEventListener('click', function() {
            markAllAsRead();
        });
    }

    // Setup clear all button
    const clearAllBtn = document.getElementById('clear-all-notifications');
    if (clearAllBtn) {
        clearAllBtn.addEventListener('click', function() {
            clearAllNotifications();
        });
    }
}

/**
 * Load notifications from the server
 */
function loadNotifications() {
    const inboxContainer = document.getElementById('notification-inbox');
    const loadingIndicator = document.getElementById('notifications-loading');
    const emptyState = document.getElementById('notifications-empty');
    const refreshBtn = document.getElementById('refresh-notifications');

    if (loadingIndicator) loadingIndicator.classList.remove('d-none');
    if (emptyState) emptyState.classList.add('d-none');
    if (refreshBtn) refreshBtn.disabled = true;

    fetch('/notifications/inbox', {
        method: 'GET',
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
            renderNotifications(data.notifications);
            updateUnreadCount(data.unread_count);
        } else {
            throw new Error(data.message || 'Failed to load notifications');
        }
    })
    .catch(error => {
        console.error('Error loading notifications:', error);
        Swal.fire({
            title: 'Error!',
            text: 'Failed to load notifications. Please try again.',
            icon: 'error',
            customClass: {
                confirmButton: 'btn btn-primary'
            }
        });
    })
    .finally(() => {
        if (loadingIndicator) loadingIndicator.classList.add('d-none');
        if (refreshBtn) refreshBtn.disabled = false;
    });
}

/**
 * Render notifications in the inbox
 */
function renderNotifications(notifications) {
    const inboxContainer = document.getElementById('notification-inbox');
    const emptyState = document.getElementById('notifications-empty');
    
    if (!inboxContainer) return;

    if (!notifications || notifications.length === 0) {
        inboxContainer.innerHTML = '';
        if (emptyState) emptyState.classList.remove('d-none');
        return;
    }

    if (emptyState) emptyState.classList.add('d-none');

    let html = '';
    notifications.forEach(notification => {
        const timeAgo = formatTimeAgo(notification.created_at);
        const isUnread = notification.read_at === null;
        const badgeClass = notification.type === 'urgent' ? 'badge bg-danger' : 
                          notification.type === 'important' ? 'badge bg-warning' : 
                          'badge bg-info';
        
        html += `
            <div class="notification-item ${isUnread ? 'unread' : ''}" data-id="${notification.id}">
                <div class="d-flex align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="mb-1">
                                <span class="${badgeClass} me-2">${notification.type}</span>
                                ${notification.title || 'Notification'}
                            </h6>
                            <small class="text-muted">${timeAgo}</small>
                        </div>
                        <p class="mb-1 text-muted">${notification.message || ''}</p>
                        ${notification.data ? `
                            <div class="mt-2">
                                <small class="text-muted">${JSON.stringify(notification.data)}</small>
                            </div>
                        ` : ''}
                    </div>
                    <div class="dropdown ms-2">
                        <button class="btn btn-sm btn-link text-muted p-0" type="button" data-bs-toggle="dropdown">
                            <i class="ri-more-2-fill"></i>
                        </button>
                        <ul class="dropdown-menu">
                            ${isUnread ? `
                                <li><a class="dropdown-item mark-as-read" href="#" data-id="${notification.id}">
                                    <i class="ri-check-line me-2"></i>Mark as Read
                                </a></li>
                            ` : ''}
                            <li><a class="dropdown-item delete-notification" href="#" data-id="${notification.id}">
                                <i class="ri-delete-bin-line me-2"></i>Delete
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        `;
    });

    inboxContainer.innerHTML = html;
    
    // Re-attach event listeners to new elements
    initializeNotificationActions();
}

/**
 * Initialize notification action handlers
 */
function initializeNotificationActions() {
    // Mark as read
    document.querySelectorAll('.mark-as-read').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const notificationId = this.getAttribute('data-id');
            markAsRead(notificationId);
        });
    });

    // Delete notification
    document.querySelectorAll('.delete-notification').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const notificationId = this.getAttribute('data-id');
            deleteNotification(notificationId);
        });
    });

    // Click on notification item
    document.querySelectorAll('.notification-item').forEach(item => {
        item.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown') && !e.target.closest('.mark-as-read') && !e.target.closest('.delete-notification')) {
                const notificationId = this.getAttribute('data-id');
                viewNotification(notificationId);
            }
        });
    });
}

/**
 * Mark a notification as read
 */
function markAsRead(notificationId) {
    fetch(`/notifications/${notificationId}/mark-read`, {
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
            // Update UI
            const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            if (notificationItem) {
                notificationItem.classList.remove('unread');
                
                // Remove mark as read option
                const dropdown = notificationItem.querySelector('.dropdown-menu');
                if (dropdown) {
                    const markAsReadOption = dropdown.querySelector('.mark-as-read');
                    if (markAsReadOption) {
                        markAsReadOption.remove();
                    }
                }
            }
            
            // Update unread count
            updateUnreadCount(data.unread_count);
        } else {
            throw new Error(data.message || 'Failed to mark notification as read');
        }
    })
    .catch(error => {
        console.error('Error marking notification as read:', error);
        Swal.fire({
            title: 'Error!',
            text: 'Failed to mark notification as read.',
            icon: 'error',
            customClass: {
                confirmButton: 'btn btn-primary'
            }
        });
    });
}

/**
 * Mark all notifications as read
 */
function markAllAsRead() {
    Swal.fire({
        title: 'Mark all as read?',
        text: 'This will mark all notifications as read.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Yes, mark all',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-primary',
            cancelButton: 'btn btn-light'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('/notifications/mark-all-read', {
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
                    // Update all notification items
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                        
                        // Remove mark as read options
                        const dropdown = item.querySelector('.dropdown-menu');
                        if (dropdown) {
                            const markAsReadOption = dropdown.querySelector('.mark-as-read');
                            if (markAsReadOption) {
                                markAsReadOption.remove();
                            }
                        }
                    });
                    
                    // Update unread count
                    updateUnreadCount(0);
                    
                    Swal.fire({
                        title: 'Success!',
                        text: 'All notifications marked as read.',
                        icon: 'success',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        }
                    });
                } else {
                    throw new Error(data.message || 'Failed to mark all notifications as read');
                }
            })
            .catch(error => {
                console.error('Error marking all notifications as read:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to mark all notifications as read.',
                    icon: 'error',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                });
            });
        }
    });
}

/**
 * Delete a notification
 */
function deleteNotification(notificationId) {
    Swal.fire({
        title: 'Delete notification?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        cancelButtonText: 'Cancel',
        customClass: {
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-light'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(`/notifications/${notificationId}`, {
                method: 'DELETE',
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
                    // Remove notification from UI
                    const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
                    if (notificationItem) {
                        notificationItem.remove();
                    }
                    
                    // Check if inbox is now empty
                    const inboxContainer = document.getElementById('notification-inbox');
                    if (inboxContainer && inboxContainer.children.length === 0) {
                        const emptyState = document.getElementById('notifications-empty');
                        if (emptyState) emptyState.classList.remove('d-none');
                    }
                    
                    // Update unread count
                    updateUnreadCount(data.unread_count);
                    
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Notification deleted successfully.',
                        icon: 'success',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        }
                    });
                } else {
                    throw new Error(data.message || 'Failed to delete notification');
                }
            })
            .catch(error => {
                console.error('Error deleting notification:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to delete notification.',
                    icon: 'error',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                });
            });
        }
    });
}

/**
 * Clear all notifications
 */
function clearAllNotifications() {
    Swal.fire({
        title: 'Clear all notifications?',
        text: 'This will delete all notifications and cannot be undone.',
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
            fetch('/notifications/clear-all', {
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
                    // Clear inbox
                    const inboxContainer = document.getElementById('notification-inbox');
                    if (inboxContainer) {
                        inboxContainer.innerHTML = '';
                    }
                    
                    // Show empty state
                    const emptyState = document.getElementById('notifications-empty');
                    if (emptyState) emptyState.classList.remove('d-none');
                    
                    // Update unread count
                    updateUnreadCount(0);
                    
                    Swal.fire({
                        title: 'Cleared!',
                        text: 'All notifications cleared.',
                        icon: 'success',
                        customClass: {
                            confirmButton: 'btn btn-primary'
                        }
                    });
                } else {
                    throw new Error(data.message || 'Failed to clear notifications');
                }
            })
            .catch(error => {
                console.error('Error clearing notifications:', error);
                Swal.fire({
                    title: 'Error!',
                    text: 'Failed to clear notifications.',
                    icon: 'error',
                    customClass: {
                        confirmButton: 'btn btn-primary'
                    }
                });
            });
        }
    });
}

/**
 * View notification details
 */
function viewNotification(notificationId) {
    // In a real implementation, this would open a modal or navigate to a detail page
    // For now, just mark as read if unread
    const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
    if (notificationItem && notificationItem.classList.contains('unread')) {
        markAsRead(notificationId);
    }
    
    // Show notification details (simplified)
    console.log('Viewing notification:', notificationId);
}

/**
 * Update unread count badge
 */
function updateUnreadCount(count) {
    // Update badge in navbar
    const badge = document.getElementById('notification-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    }
    
    // Update badge in inbox header
    const inboxBadge = document.getElementById('unread-count');
    if (inboxBadge) {
        inboxBadge.textContent = count;
    }
}

/**
 * Initialize real-time updates using polling
 */
function initializeRealTimeUpdates() {
    // Check for new notifications every 30 seconds
    setInterval(() => {
        checkForNewNotifications();
    }, 30000);
}

/**
 * Check for new notifications
 */
function checkForNewNotifications() {
    fetch('/notifications/check-new', {
        method: 'GET',
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
        if (data.success && data.has_new) {
            // Update unread count
            updateUnreadCount(data.unread_count);
            
            // Show notification toast if not focused on inbox
            if (!document.getElementById('notification-inbox')) {
                showNewNotificationToast(data.new_count);
            }
        }
    })
    .catch(error => {
        console.error('Error checking for new notifications:', error);
    });
}

/**
 * Show toast for new notifications
 */
function showNewNotificationToast(count) {
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
        title: `${count} new notification${count > 1 ? 's' : ''}`,
        text: 'Click to view',
        showCloseButton: true
    });
}

/**
 * Format time ago
 */
function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffSec = Math.floor(diffMs / 1000);
    const diffMin = Math.floor(diffSec / 60);
    const diffHour = Math.floor(diffMin / 60);
    const diffDay = Math.floor(diffHour / 24);

    if (diffDay > 7) {
        return date.toLocaleDateString();
    } else if (diffDay > 0) {
        return `${diffDay} day${diffDay > 1 ? 's' : ''} ago`;
    } else if (diffHour > 0) {
        return `${diffHour} hour${diffHour > 1 ? 's' : ''} ago`;
    } else if (diffMin > 0) {
        return `${diffMin} minute${diffMin > 1 ? 's' : ''} ago`;
    } else {
        return 'Just now';
    }
}