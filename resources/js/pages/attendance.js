/**
 * Attendance Management — Enterprise JavaScript
 * Handles: view modal, edit redirect, delete, approve, reject, break management, auto-refresh
 */

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

const apiFetch = (url, method = 'GET', body = null) => {
    const opts = {
        method,
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf(),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
        },
    };
    if (body) opts.body = JSON.stringify(body);
    return fetch(url, opts).then(r => r.json());
};

const toast = (icon, title, text = '') => {
    if (typeof Swal !== 'undefined') {
        Swal.fire({ icon, title, text, timer: 2500, showConfirmButton: false, timerProgressBar: true });
    } else {
        alert(`${title} ${text}`);
    }
};

/* ─── View Attendance Modal ─────────────────────────────── */
window.viewAttendance = function (id) {
    apiFetch(`/api/attendance/${id}/view`).then(res => {
        if (!res.success) { toast('error', 'Failed to load', res.message); return; }
        const a = res.attendance;

        const statusColors = { present: 'success', late: 'warning', absent: 'danger', leave: 'info', half_day: 'secondary' };
        const sc = statusColors[a.status] || 'secondary';

        let breaksHtml = a.breaks.length
            ? a.breaks.map(b => `<tr><td><span class="badge bg-secondary">${b.type}</span></td><td>${b.start}</td><td>${b.end}</td><td>${b.duration}</td></tr>`).join('')
            : '<tr><td colspan="4" class="text-muted text-center">No breaks recorded</td></tr>';

        let logsHtml = a.audit_logs.length
            ? a.audit_logs.map(l => `<tr><td><span class="badge bg-light text-dark">${l.action}</span></td><td>${l.user}</td><td class="text-muted">${l.at}</td></tr>`).join('')
            : '<tr><td colspan="3" class="text-muted text-center">No audit logs</td></tr>';

        let locationHtml = '';
        if (a.latitude && a.longitude) {
            locationHtml += `<a href="https://maps.google.com/?q=${a.latitude},${a.longitude}" target="_blank" class="btn btn-sm btn-outline-success me-1"><i class="ti ti-map-pin me-1"></i>Check-in Location</a>`;
        }
        if (a.latitude_out && a.longitude_out) {
            locationHtml += `<a href="https://maps.google.com/?q=${a.latitude_out},${a.longitude_out}" target="_blank" class="btn btn-sm btn-outline-info"><i class="ti ti-map-pin me-1"></i>Check-out Location</a>`;
        }
        if (!locationHtml) locationHtml = '<span class="text-muted">Location not recorded</span>';

        const lateInfo = a.late_arrival_minutes > 0 ? `<span class="badge bg-warning-subtle text-warning ms-2">${a.late_arrival_minutes}m late</span>` : '';

        document.getElementById('attendanceModalBody').innerHTML = `
            <div class="row g-3">
                <div class="col-12"><h5 class="fw-bold">${a.user_name} <small class="text-muted fw-normal">${a.user_email}</small></h5></div>
                <div class="col-md-4">
                    <label class="form-label text-muted mb-1">Date</label>
                    <p class="fw-bold mb-0">${a.date}</p>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted mb-1">Status</label>
                    <p class="mb-0"><span class="badge bg-${sc}-subtle text-${sc}">${a.status.replace('_', ' ').replace(/\b\w/g, c => c.toUpperCase())}</span>
                    ${a.is_approved ? '<span class="badge bg-success ms-1">Approved</span>' : '<span class="badge bg-secondary ms-1">Pending</span>'}
                    </p>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-muted mb-1">Hours Worked</label>
                    <p class="fw-bold mb-0">${a.hours_worked}</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted mb-1">Check-in</label>
                    <p class="mb-0">${a.check_in ? `<span class="badge bg-success-subtle text-success">${a.check_in}</span>${lateInfo}` : '<span class="text-muted">-</span>'}</p>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-muted mb-1">Check-out</label>
                    <p class="mb-0">${a.check_out ? `<span class="badge bg-info-subtle text-info">${a.check_out}</span>` : '<span class="text-muted">-</span>'}</p>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted mb-1">Notes</label>
                    <p class="mb-0">${a.notes || '<span class="text-muted">No notes</span>'}</p>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted mb-1">Location</label>
                    <div>${locationHtml}</div>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted mb-2 d-block">Breaks</label>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light"><tr><th>Type</th><th>Start</th><th>End</th><th>Duration</th></tr></thead>
                            <tbody>${breaksHtml}</tbody>
                        </table>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label text-muted mb-2 d-block">Audit Trail</label>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light"><tr><th>Action</th><th>By</th><th>At</th></tr></thead>
                            <tbody>${logsHtml}</tbody>
                        </table>
                    </div>
                </div>
            </div>`;

        // Store id for approve/reject from modal
        document.getElementById('attendanceModal').dataset.attendanceId = id;
        new bootstrap.Modal(document.getElementById('attendanceModal')).show();
    }).catch(() => toast('error', 'Network Error', 'Could not load attendance details.'));
};

/* ─── Edit ──────────────────────────────────────────────── */
window.editAttendanceRecord = function (id) {
    window.location.href = `/attendance/manual/${id}/edit`;
};

/* ─── Delete ────────────────────────────────────────────── */
window.deleteAttendanceRecord = function (id) {
    if (typeof Swal === 'undefined') {
        if (!confirm('Are you sure you want to delete this attendance record?')) return;
        doDelete(id);
        return;
    }
    Swal.fire({
        icon: 'warning',
        title: 'Delete Attendance?',
        text: 'This action cannot be undone.',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Yes, delete it',
        cancelButtonText: 'Cancel',
    }).then(result => { if (result.isConfirmed) doDelete(id); });
};

function doDelete(id) {
    apiFetch(`/attendance/${id}`, 'DELETE').then(res => {
        if (res.success) {
            toast('success', 'Deleted!', 'Attendance record removed.');
            setTimeout(() => location.reload(), 1200);
        } else {
            toast('error', 'Delete Failed', res.message || 'Unknown error');
        }
    }).catch(() => toast('error', 'Network Error', 'Could not delete this record.'));
}

/* ─── Approve ───────────────────────────────────────────── */
window.approveAttendance = function (id) {
    if (typeof Swal === 'undefined') {
        if (!confirm('Approve this attendance record?')) return;
        doApprove(id);
        return;
    }
    Swal.fire({
        icon: 'question',
        title: 'Approve Attendance?',
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Approve',
    }).then(result => { if (result.isConfirmed) doApprove(id); });
};

function doApprove(id) {
    apiFetch(`/api/attendance/${id}/approve`, 'POST').then(res => {
        if (res.success) {
            toast('success', '✅ Approved!', 'Attendance record has been approved.');
            setTimeout(() => location.reload(), 1200);
        } else {
            toast('error', 'Approval Failed', res.message || 'Unknown error');
        }
    }).catch(() => toast('error', 'Network Error', 'Could not approve this record.'));
}

/* ─── Reject ────────────────────────────────────────────── */
window.rejectAttendance = function (id) {
    if (typeof Swal === 'undefined') {
        const reason = prompt('Enter rejection reason:');
        if (!reason) return;
        doReject(id, reason);
        return;
    }
    Swal.fire({
        icon: 'warning',
        title: 'Reject Attendance',
        input: 'textarea',
        inputLabel: 'Reason for rejection',
        inputPlaceholder: 'Enter reason...',
        inputAttributes: { 'aria-label': 'Rejection reason' },
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Reject',
        preConfirm: (reason) => {
            if (!reason || reason.trim().length < 3) {
                Swal.showValidationMessage('Please enter a reason (at least 3 characters)');
            }
            return reason;
        },
    }).then(result => { if (result.isConfirmed) doReject(id, result.value); });
};

function doReject(id, reason) {
    apiFetch(`/api/attendance/${id}/reject`, 'POST', { reason }).then(res => {
        if (res.success) {
            toast('success', '❌ Rejected', 'Attendance record has been rejected.');
            setTimeout(() => location.reload(), 1200);
        } else {
            toast('error', 'Rejection Failed', res.message || 'Unknown error');
        }
    }).catch(() => toast('error', 'Network Error', 'Could not reject this record.'));
}

/* ─── Break Management ──────────────────────────────────── */
let breakTimerInterval = null;
let breakStartTime = null;

window.startBreak = function (breakType = 'lunch') {
    apiFetch('/api/attendance/break/start', 'POST', { break_type: breakType }).then(res => {
        if (res.success) {
            toast('success', '☕ Break Started', `Break started at ${res.start_time}`);
            breakStartTime = new Date();
            startBreakTimer();
            updateBreakUI(true);
        } else {
            toast('error', 'Break Failed', res.message);
        }
    }).catch(() => toast('error', 'Network Error', 'Could not start break.'));
};

window.endBreak = function () {
    apiFetch('/api/attendance/break/end', 'POST').then(res => {
        if (res.success) {
            toast('success', '🔄 Break Ended', 'Welcome back!');
            stopBreakTimer();
            updateBreakUI(false);
        } else {
            toast('error', 'Break Failed', res.message);
        }
    }).catch(() => toast('error', 'Network Error', 'Could not end break.'));
};

function startBreakTimer() {
    if (breakTimerInterval) clearInterval(breakTimerInterval);
    breakTimerInterval = setInterval(() => {
        if (!breakStartTime) return;
        const elapsed = Math.floor((new Date() - breakStartTime) / 1000);
        const m = Math.floor(elapsed / 60);
        const s = elapsed % 60;
        const el = document.getElementById('break-timer');
        if (el) el.textContent = `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
    }, 1000);
}

function stopBreakTimer() {
    if (breakTimerInterval) clearInterval(breakTimerInterval);
    breakTimerInterval = null;
    breakStartTime = null;
    const el = document.getElementById('break-timer');
    if (el) el.textContent = '00:00';
}

function updateBreakUI(onBreak) {
    const startBtn = document.getElementById('start-break-btn');
    const endBtn = document.getElementById('end-break-btn');
    const timerWrap = document.getElementById('break-timer-wrap');
    if (startBtn) startBtn.style.display = onBreak ? 'none' : '';
    if (endBtn) endBtn.style.display = onBreak ? '' : 'none';
    if (timerWrap) timerWrap.style.display = onBreak ? '' : 'none';
}

/* ─── Bulk Approve ──────────────────────────────────────── */
window.bulkApprove = function () {
    const checked = [...document.querySelectorAll('.attendance-checkbox:checked')];
    if (!checked.length) {
        toast('warning', 'No records selected', 'Please select at least one attendance record to approve.');
        return;
    }
    const ids = checked.map(cb => cb.dataset.id);

    Swal.fire({
        icon: 'question',
        title: `Approve ${ids.length} record(s)?`,
        showCancelButton: true,
        confirmButtonColor: '#198754',
        confirmButtonText: 'Approve All',
    }).then(async result => {
        if (!result.isConfirmed) return;
        let success = 0, failed = 0;
        for (const id of ids) {
            const res = await apiFetch(`/api/attendance/${id}/approve`, 'POST');
            res.success ? success++ : failed++;
        }
        toast('success', 'Bulk Approval', `${success} approved${failed ? `, ${failed} failed` : ''}.`);
        setTimeout(() => location.reload(), 1500);
    });
};

/* ─── Initialise on DOMContentLoaded ───────────────────── */
document.addEventListener('DOMContentLoaded', function () {

    // Check if on break at page load
    fetch('/api/attendance/break/status', {
        headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(res => {
        if (res.on_break && res.break) {
            breakStartTime = new Date(res.break.start_time);
            startBreakTimer();
            updateBreakUI(true);
        }
    }).catch(() => { });

    // Select-all checkbox
    const selectAll = document.getElementById('select-all-checkbox');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.attendance-checkbox').forEach(cb => cb.checked = this.checked);
        });
    }

    // Auto-refresh stats every 60 seconds
    setInterval(() => {
        const statsBar = document.getElementById('attendance-stats-bar');
        if (!statsBar) return;
        const filterDate = document.getElementById('attendance-date')?.value || '';
        fetch(`/api/attendance/today-status?date=${filterDate}`, {
            headers: { 'X-CSRF-TOKEN': csrf(), 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(r => r.json()).catch(() => { });
    }, 60000);
});