<div class="tab-pane fade" id="attendance-tab" role="tabpanel">
    <form id="attendance-settings-form" data-group="attendance">
        @csrf
        <input type="hidden" name="group" value="attendance">

        @php
            $aw = $attendance ?? [];
            $clockInEarliest  = $aw['attendance.clock_in_earliest']  ?? '07:00';
            $clockInLatest    = $aw['attendance.clock_in_latest']    ?? '12:00';
            $clockOutEarliest = $aw['attendance.clock_out_earliest'] ?? '11:00';
            $clockOutLatest   = $aw['attendance.clock_out_latest']   ?? '23:00';
            $enforceClockIn   = ($aw['attendance.enforce_clock_in_window']  ?? '1') == '1';
            $enforceClockOut  = ($aw['attendance.enforce_clock_out_window'] ?? '0') == '1';
            $gracePeriod      = $aw['attendance.grace_period_minutes']       ?? 15;
            $halfDayThreshold = $aw['attendance.half_day_threshold_hours']   ?? 4;
            $minShift         = $aw['attendance.minimum_shift_hours']        ?? 4;
            $maxShift         = $aw['attendance.maximum_shift_hours']        ?? 12;
        @endphp

        {{-- ── Clock-In Window ──────────────────────────────────── --}}
        <div class="card border mb-4">
            <div class="card-header bg-success-subtle d-flex align-items-center justify-content-between py-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-login fs-5 text-success"></i>
                    <h6 class="mb-0 fw-semibold">Clock-In Allowed Window</h6>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="enforce_clock_in_window" name="settings[attendance.enforce_clock_in_window]"
                           value="1" {{ $enforceClockIn ? 'checked' : '' }}
                           onchange="toggleWindowCard('clock-in-window-body', this.checked)">
                    <label class="form-check-label small text-muted" for="enforce_clock_in_window">Enforce restriction</label>
                </div>
            </div>
            <div class="card-body" id="clock-in-window-body" style="{{ $enforceClockIn ? '' : 'opacity:.5; pointer-events:none;' }}">
                <p class="text-muted small mb-3">Staff can only clock in between these times. Attempts outside this window will be blocked.</p>
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Earliest Clock-In <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-success-subtle border-success-subtle"><i class="ti ti-clock-play text-success"></i></span>
                            <input type="time" class="form-control" id="clock_in_earliest"
                                   name="settings[attendance.clock_in_earliest]"
                                   value="{{ $clockInEarliest }}" required>
                        </div>
                        <div class="form-text">Earliest time staff can clock in</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Latest Clock-In <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-warning-subtle border-warning-subtle"><i class="ti ti-clock-stop text-warning"></i></span>
                            <input type="time" class="form-control" id="clock_in_latest"
                                   name="settings[attendance.clock_in_latest]"
                                   value="{{ $clockInLatest }}" required>
                        </div>
                        <div class="form-text">After this time, clock-in is blocked</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Grace Period (minutes)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-hourglass"></i></span>
                            <input type="number" class="form-control" id="grace_period_minutes"
                                   name="settings[attendance.grace_period_minutes]"
                                   value="{{ $gracePeriod }}" min="0" max="120">
                        </div>
                        <div class="form-text">Extra minutes after shift start before marking "Late"</div>
                    </div>
                </div>

                {{-- Visual Timeline Bar --}}
                <div class="mt-4">
                    <label class="form-label small text-muted fw-medium">Window Preview (24-hour day)</label>
                    <div class="position-relative" style="height:36px; background:#f1f3f4; border-radius:8px; overflow:hidden;">
                        <div id="clock-in-bar"
                             class="position-absolute h-100 bg-success bg-opacity-25 border border-success border-opacity-50"
                             style="border-radius:6px; transition:all .3s;"></div>
                        <div id="clock-in-bar-grace"
                             class="position-absolute h-100 bg-warning bg-opacity-25"
                             style="border-radius:0; transition:all .3s;"></div>
                        {{-- Hour ticks --}}
                        @foreach([0,6,12,18,24] as $h)
                        <span class="position-absolute small text-muted" style="left:{{ ($h/24)*100 }}%; top:50%; transform:translate(-50%,-50%); font-size:10px;">{{ str_pad($h,2,'0',STR_PAD_LEFT) }}:00</span>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span class="small text-success" id="clock-in-bar-label-start">from {{ $clockInEarliest }}</span>
                        <span class="small text-warning" id="clock-in-bar-label-grace"></span>
                        <span class="small text-danger" id="clock-in-bar-label-end">to {{ $clockInLatest }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Clock-Out Window ─────────────────────────────────── --}}
        <div class="card border mb-4">
            <div class="card-header bg-danger-subtle d-flex align-items-center justify-content-between py-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-logout fs-5 text-danger"></i>
                    <h6 class="mb-0 fw-semibold">Clock-Out Allowed Window</h6>
                </div>
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" role="switch"
                           id="enforce_clock_out_window" name="settings[attendance.enforce_clock_out_window]"
                           value="1" {{ $enforceClockOut ? 'checked' : '' }}
                           onchange="toggleWindowCard('clock-out-window-body', this.checked)">
                    <label class="form-check-label small text-muted" for="enforce_clock_out_window">Enforce restriction</label>
                </div>
            </div>
            <div class="card-body" id="clock-out-window-body" style="{{ $enforceClockOut ? '' : 'opacity:.5; pointer-events:none;' }}">
                <p class="text-muted small mb-3">Staff can only clock out between these times. Useful to prevent accidental early checkouts.</p>
                <div class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Earliest Clock-Out <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-info-subtle border-info-subtle"><i class="ti ti-clock-play text-info"></i></span>
                            <input type="time" class="form-control" id="clock_out_earliest"
                                   name="settings[attendance.clock_out_earliest]"
                                   value="{{ $clockOutEarliest }}" required>
                        </div>
                        <div class="form-text">Earliest time staff can clock out</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-medium">Latest Clock-Out <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-danger-subtle border-danger-subtle"><i class="ti ti-clock-stop text-danger"></i></span>
                            <input type="time" class="form-control" id="clock_out_latest"
                                   name="settings[attendance.clock_out_latest]"
                                   value="{{ $clockOutLatest }}" required>
                        </div>
                        <div class="form-text">After this time, clock-out is blocked</div>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="form-label small text-muted fw-medium">Window Preview (24-hour day)</label>
                    <div class="position-relative" style="height:36px; background:#f1f3f4; border-radius:8px; overflow:hidden;">
                        <div id="clock-out-bar"
                             class="position-absolute h-100 bg-danger bg-opacity-25 border border-danger border-opacity-50"
                             style="border-radius:6px; transition:all .3s;"></div>
                        @foreach([0,6,12,18,24] as $h)
                        <span class="position-absolute small text-muted" style="left:{{ ($h/24)*100 }}%; top:50%; transform:translate(-50%,-50%); font-size:10px;">{{ str_pad($h,2,'0',STR_PAD_LEFT) }}:00</span>
                        @endforeach
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span class="small text-info" id="clock-out-bar-label-start">from {{ $clockOutEarliest }}</span>
                        <span class="small text-danger" id="clock-out-bar-label-end">to {{ $clockOutLatest }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Shift Rules ───────────────────────────────────────── --}}
        <div class="card border mb-4">
            <div class="card-header py-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="ti ti-ruler-2 fs-5 text-primary"></i>
                    <h6 class="mb-0 fw-semibold">Shift Rules</h6>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Minimum Shift (hours)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-arrow-down"></i></span>
                            <input type="number" class="form-control" name="settings[attendance.minimum_shift_hours]"
                                   value="{{ $minShift }}" min="0" max="24" step="0.5">
                        </div>
                        <div class="form-text">Below this = invalid/absent</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Half-Day Threshold (hours)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-calendar-half"></i></span>
                            <input type="number" class="form-control" name="settings[attendance.half_day_threshold_hours]"
                                   value="{{ $halfDayThreshold }}" min="0" max="24" step="0.5">
                        </div>
                        <div class="form-text">Below this = half day</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-medium">Maximum Shift (hours)</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="ti ti-arrow-up"></i></span>
                            <input type="number" class="form-control" name="settings[attendance.maximum_shift_hours]"
                                   value="{{ $maxShift }}" min="0" max="24" step="0.5">
                        </div>
                        <div class="form-text">Beyond this = overtime flag</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary px-4">
                <i class="ti ti-device-floppy me-1"></i> Save Attendance Settings
            </button>
        </div>
    </form>
</div>

<script>
(function () {
    function timeToFraction(timeStr) {
        if (!timeStr) return 0;
        const [h, m] = timeStr.split(':').map(Number);
        return (h * 60 + (m || 0)) / 1440;
    }

    function updateBar(barId, startId, endId, labelStartId, labelEndId, graceFraction) {
        const startInput = document.getElementById(startId);
        const endInput   = document.getElementById(endId);
        if (!startInput || !endInput) return;

        const s = timeToFraction(startInput.value);
        const e = timeToFraction(endInput.value);
        const bar = document.getElementById(barId);
        if (bar) {
            bar.style.left  = (s * 100) + '%';
            bar.style.width = (Math.max(0, e - s) * 100) + '%';
        }
        if (labelStartId) {
            const el = document.getElementById(labelStartId);
            if (el) el.textContent = 'from ' + (startInput.value || '--:--');
        }
        if (labelEndId) {
            const el = document.getElementById(labelEndId);
            if (el) el.textContent = 'to ' + (endInput.value || '--:--');
        }

        // Grace period bar on clock-in only
        if (graceFraction !== undefined) {
            const graceBar   = document.getElementById('clock-in-bar-grace');
            const graceLabel = document.getElementById('clock-in-bar-label-grace');
            const graceInput = document.getElementById('grace_period_minutes');
            const graceMin   = parseInt(graceInput?.value || 0);
            const graceFrac  = graceMin / 1440;
            if (graceBar) {
                graceBar.style.left  = (s * 100) + '%';
                graceBar.style.width = (graceFrac * 100) + '%';
            }
            if (graceLabel && graceMin > 0) {
                graceLabel.textContent = graceMin + 'min grace';
            }
        }
    }

    function wireBar(startId, endId, barId, labelStartId, labelEndId, withGrace) {
        const s = document.getElementById(startId);
        const e = document.getElementById(endId);
        const g = document.getElementById('grace_period_minutes');
        const update = () => updateBar(barId, startId, endId, labelStartId, labelEndId, withGrace ? 0 : undefined);
        s?.addEventListener('input', update);
        e?.addEventListener('input', update);
        if (withGrace) g?.addEventListener('input', update);
        update();
    }

    document.addEventListener('DOMContentLoaded', function () {
        wireBar('clock_in_earliest',  'clock_in_latest',  'clock-in-bar',  'clock-in-bar-label-start',  'clock-in-bar-label-end',  true);
        wireBar('clock_out_earliest', 'clock_out_latest', 'clock-out-bar', 'clock-out-bar-label-start', 'clock-out-bar-label-end', false);

        // Save via AJAX same as other tabs
        const form = document.getElementById('attendance-settings-form');
        form?.addEventListener('submit', function (e) {
            e.preventDefault();
            const btn = form.querySelector('button[type=submit]');
            const orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';

            // Ensure unchecked toggles send 0
            ['enforce_clock_in_window','enforce_clock_out_window'].forEach(id => {
                const cb = document.getElementById(id);
                if (cb && !cb.checked) {
                    let hidden = form.querySelector(`input[type=hidden][name="settings[attendance.${id.replace('enforce_', '')}]"]`);
                    if (!hidden) {
                        hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = `settings[attendance.${id}]`;
                        hidden.value = '0';
                        form.appendChild(hidden);
                    }
                }
            });

            fetch('/settings', {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({ title: 'Saved!', text: data.message, icon: 'success', timer: 2000, showConfirmButton: false });
                } else {
                    throw new Error(data.message || 'Save failed');
                }
            })
            .catch(err => Swal.fire({ title: 'Error', text: err.message, icon: 'error' }))
            .finally(() => { btn.disabled = false; btn.innerHTML = orig; });
        });
    });

    window.toggleWindowCard = function (bodyId, enabled) {
        const body = document.getElementById(bodyId);
        if (body) {
            body.style.opacity = enabled ? '1' : '0.5';
            body.style.pointerEvents = enabled ? '' : 'none';
        }
    };
})();
</script>
