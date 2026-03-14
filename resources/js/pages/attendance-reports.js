/**
 * Attendance Reports JavaScript
 * Handles report filtering, CSV export, and print/PDF functionality.
 */

document.addEventListener('DOMContentLoaded', function () {
    const filterBtn = document.getElementById('filter-report-btn');
    const exportCsvBtn = document.getElementById('export-csv-btn');
    const printBtn = document.getElementById('print-report-btn');
    const startDate = document.getElementById('start-date');
    const endDate = document.getElementById('end-date');
    const staffMember = document.getElementById('staff-member');
    const statusFilter = document.getElementById('report-status');

    /* ── Apply Filters ──────────────────────────────── */
    if (filterBtn) {
        filterBtn.addEventListener('click', function () {
            const start = startDate?.value;
            const end = endDate?.value;
            const staff = staffMember?.value;
            const status = statusFilter?.value;

            if (start && end && new Date(start) > new Date(end)) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Invalid Date Range', text: 'Start date must be on or before the end date.', timer: 2500, showConfirmButton: false });
                } else {
                    alert('Start date must be before or equal to end date.');
                }
                return;
            }

            let url = '/attendance/report?';
            const params = [];
            if (start) params.push(`start_date=${start}`);
            if (end) params.push(`end_date=${end}`);
            if (staff) params.push(`staff_id=${staff}`);
            if (status) params.push(`status=${status}`);
            window.location.href = url + params.join('&');
        });
    }

    /* ── Export CSV ─────────────────────────────────── */
    if (exportCsvBtn) {
        exportCsvBtn.addEventListener('click', function () {
            const start = startDate?.value;
            const end = endDate?.value;
            const staff = staffMember?.value;

            if (!start || !end) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'warning', title: 'Date Required', text: 'Please select a start and end date before exporting.', timer: 2500, showConfirmButton: false });
                } else {
                    alert('Please select a start and end date before exporting.');
                }
                return;
            }

            let url = `/api/attendance/export?format=csv&start_date=${start}&end_date=${end}`;
            if (staff) url += `&staff_id=${staff}`;
            window.location.href = url;
        });
    }

    /* ── Print / PDF ────────────────────────────────── */
    if (printBtn) {
        printBtn.addEventListener('click', function () {
            window.print();
        });
    }
});