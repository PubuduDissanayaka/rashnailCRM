/**
 * Schedule Grid — Interactive Weekly Schedule Manager
 * Features: staff filter, quick toggle off-day, copy week, print, inline edit
 */
document.addEventListener('DOMContentLoaded', function () {
    const staffFilter = document.getElementById('staff-filter');
    const staffRows = document.querySelectorAll('[data-staff-id]');
    const printBtn = document.getElementById('print-schedule-btn');
    const copyModal = document.getElementById('copy-week-modal');
    const copyForm = document.getElementById('copy-week-form');

    // 1. Staff filter
    if (staffFilter) {
        staffFilter.addEventListener('change', function () {
            const val = this.value;
            staffRows.forEach(row => {
                if (!val || row.dataset.staffId === val) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // 2. Quick toggle off day (AJAX)
    document.querySelectorAll('[data-toggle-day]').forEach(btn => {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const scheduleId = this.dataset.scheduleId;
            const dayCell = this.closest('[data-day-cell]');
            const currentlyOn = this.dataset.isWorkingDay === '1';
            const newState = currentlyOn ? '0' : '1';

            fetch(`/schedules/${scheduleId}/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ is_working_day: newState === '1' })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Failed to toggle.');
                }
            })
            .catch(() => alert('Network error.'));
        });
    });

    // 3. Copy week
    if (copyForm) {
        copyForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch(this.action, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content') },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || 'Copy failed.');
                }
            })
            .catch(() => alert('Network error.'));
        });
    }

    // 4. Print
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }

    // 5. Total hours calculation (already done server-side, but update on filter change)
    function updateTotals() {
        staffRows.forEach(row => {
            if (row.style.display !== 'none') {
                const hoursEl = row.querySelector('[data-total-hours]');
                if (hoursEl) {
                    // Server-rendered — no recalculation needed
                }
            }
        });
    }
});
