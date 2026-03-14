/**
 * Staff Attendance JavaScript
 * Handles staff-specific attendance page view/edit/delete/approve/reject actions.
 * Delegates to shared functions in attendance.js where possible.
 */

// All viewAttendance / editAttendanceRecord / deleteAttendanceRecord / approveAttendance / rejectAttendance
// are imported from attendance.js which is also loaded on this page.
// This file provides the filter validation specific to the staff page.

document.addEventListener('DOMContentLoaded', function () {
    const staffSelect = document.getElementById('staff-select');
    const applyBtn = document.getElementById('apply-filters-btn');
    const startDateInput = document.getElementById('start-date');
    const endDateInput = document.getElementById('end-date');
    const statusFilter = document.getElementById('status-filter');

    function toggleApplyBtn() {
        if (applyBtn) applyBtn.disabled = !(staffSelect && staffSelect.value);
    }

    // Enable/disable filter button based on staff selection
    if (staffSelect) {
        staffSelect.addEventListener('change', toggleApplyBtn);
        toggleApplyBtn();
    }

    // Apply filters button click
    if (applyBtn) {
        applyBtn.addEventListener('click', function () {
            const staffId = staffSelect?.value;
            const startDate = startDateInput?.value;
            const endDate = endDateInput?.value;
            const status = statusFilter?.value;

            if (!staffId) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Staff Required', text: 'Please select a staff member first.', timer: 2000, showConfirmButton: false });
                } else {
                    alert('Please select a staff member.');
                }
                return;
            }

            if (startDate && endDate && new Date(startDate) > new Date(endDate)) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Invalid Dates', text: 'Start date must be on or before the end date.', timer: 2500, showConfirmButton: false });
                } else {
                    alert('Start date must be before or equal to end date.');
                }
                return;
            }

            let url = `/attendance/staff?staff_id=${staffId}`;
            if (startDate) url += `&start_date=${startDate}`;
            if (endDate) url += `&end_date=${endDate}`;
            if (status) url += `&status=${status}`;

            window.location.href = url;
        });
    }
});