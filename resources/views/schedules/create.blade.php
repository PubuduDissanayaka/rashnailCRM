@extends('layouts.vertical', ['title' => 'Quick Add Schedule'])

@section('css')
<style>
    .day-checkbox { display: none; }
    .day-label {
        display: inline-flex; align-items: center; justify-content: center;
        width: 80px; height: 80px; border-radius: 12px;
        border: 2px solid #e5e7eb; background: #f9fafb;
        cursor: pointer; transition: all .15s;
        font-size: 0.8rem; font-weight: 600; color: #6b7280;
        flex-direction: column; gap: 4px;
        margin: 0 4px 8px 4px;
    }
    .day-label:hover { border-color: #93c5fd; background: #eff6ff; color: #2563eb; transform: translateY(-2px); }
    .day-label .ti { font-size: 1.3rem; }
    .day-checkbox:checked + .day-label { border-color: #2563eb; background: #eff6ff; color: #2563eb; box-shadow: 0 2px 8px rgba(37,99,235,0.15); }
    .day-checkbox:checked + .day-label .ti { color: #2563eb; }

    .preset-btn {
        padding: 6px 14px; border-radius: 8px; border: 1px solid #e5e7eb;
        background: white; font-size: 0.8rem; cursor: pointer; transition: all .15s;
    }
    .preset-btn:hover { border-color: #2563eb; color: #2563eb; background: #eff6ff; }
    .preset-btn.active { border-color: #2563eb; background: #2563eb; color: white; }

    .staff-preview { font-size: 0.8rem; }
    .staff-preview .existing-day { display: inline-block; padding: 2px 8px; border-radius: 4px; margin: 1px; font-size: 0.7rem; }
</style>
@endsection

@section('content')
@include('layouts.partials.page-title', ['title' => 'Quick Add Schedule'])

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('schedules.store') }}" method="POST" id="scheduleForm">
                    @csrf

                    <!-- Step 1: Staff -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">1. Select Staff *</label>
                        <select name="user_id" id="staffSelect" class="form-select" required style="max-width:400px">
                            <option value="">Choose a staff member...</option>
                            @foreach($staffMembers as $staff)
                            <option value="{{ $staff->id }}" {{ old('user_id') == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                            @endforeach
                        </select>
                        @error('user_id') <span class="text-danger"><small>{{ $message }}</small></span> @enderror

                        <!-- Existing schedule preview -->
                        <div id="staffPreview" class="staff-preview mt-2 text-muted d-none">
                            <i class="ti ti-info-circle me-1"></i>Existing schedules: <span id="existingDays"></span>
                        </div>
                    </div>

                    <!-- Step 2: Days -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">2. Select Days *</label>
                        <div class="d-flex flex-wrap">
                            @php
                                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                                $icons = ['calendar', 'calendar', 'calendar', 'calendar', 'calendar', 'calendar', 'calendar'];
                            @endphp
                            @foreach($days as $i => $day)
                            <div>
                                <input type="checkbox" name="days[]" value="{{ $day }}" id="day_{{ $day }}"
                                    class="day-checkbox" {{ is_array(old('days')) && in_array($day, old('days')) ? 'checked' : '' }}>
                                <label for="day_{{ $day }}" class="day-label">
                                    <i class="ti ti-{{ $icons[$i] }}"></i>
                                    {{ substr(ucfirst($day), 0, 3) }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                        <div class="mt-2">
                            <button type="button" class="btn btn-sm btn-light" onclick="document.querySelectorAll('.day-checkbox').forEach(c => c.checked = true); updateDayCount();">Select All</button>
                            <button type="button" class="btn btn-sm btn-light" onclick="document.querySelectorAll('.day-checkbox').forEach(c => { if(['monday','tuesday','wednesday','thursday','friday'].includes(c.value)) c.checked = true; }); updateDayCount();">Mon-Fri</button>
                            <button type="button" class="btn btn-sm btn-light" onclick="document.querySelectorAll('.day-checkbox').forEach(c => c.checked = false); updateDayCount();">Clear</button>
                            <span id="dayCount" class="ms-2 text-muted small">0 days selected</span>
                        </div>
                        @error('days') <span class="text-danger d-block mt-1"><small>{{ $message }}</small></span> @enderror
                    </div>

                    <!-- Step 3: Time + Presets -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">3. Set Time</label>
                        <div class="mb-2">
                            <span class="text-muted small me-2">Quick fill:</span>
                            <button type="button" class="preset-btn" data-preset='{"start":"09:00","end":"18:00"}'>🌅 Morning 9-6</button>
                            <button type="button" class="preset-btn" data-preset='{"start":"10:00","end":"19:00"}'>☀️ Day 10-7</button>
                            <button type="button" class="preset-btn" data-preset='{"start":"14:00","end":"22:00"}'>🌙 Evening 2-10</button>
                            <button type="button" class="preset-btn" data-preset='{"start":"08:00","end":"17:00"}'>🏢 Office 8-5</button>
                            <button type="button" class="preset-btn" data-preset='{"start":"10:00","end":"16:00"}'>🕐 Short 10-4</button>
                        </div>
                        <div class="row g-2" style="max-width:600px">
                            <div class="col-md-4">
                                <label class="form-label small">Start Time *</label>
                                <input type="time" name="start_time" id="startTime" class="form-control" value="{{ old('start_time', '09:00') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">End Time *</label>
                                <input type="time" name="end_time" id="endTime" class="form-control" value="{{ old('end_time', '18:00') }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Grace Period (min)</label>
                                <input type="number" name="grace_period_minutes" class="form-control" value="{{ old('grace_period_minutes', 15) }}" min="0" max="60">
                            </div>
                        </div>
                        @error('start_time') <span class="text-danger"><small>{{ $message }}</small></span> @enderror
                        @error('end_time') <span class="text-danger"><small>{{ $message }}</small></span> @enderror
                    </div>

                    <!-- Step 4: Confirm -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_working_day" value="1" class="form-check-input" id="is_working_day" {{ old('is_working_day', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_working_day">Working Day</label>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div id="summaryBox" class="alert alert-info d-none mb-3">
                        <i class="ti ti-info-circle me-1"></i>
                        Will create schedule for <strong id="summaryDays">0 days</strong>
                        at <strong id="summaryTime">09:00 - 18:00</strong>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('schedules.index') }}" class="btn btn-light">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="ti ti-check me-1"></i> Create Schedule(s)
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Day count
function updateDayCount() {
    const checked = document.querySelectorAll('.day-checkbox:checked').length;
    document.getElementById('dayCount').textContent = checked + ' day(s) selected';
    const summary = document.getElementById('summaryBox');
    const start = document.getElementById('startTime').value || '09:00';
    const end = document.getElementById('endTime').value || '18:00';
    if (checked > 0) {
        summary.classList.remove('d-none');
        document.getElementById('summaryDays').textContent = checked + ' day(s)';
        document.getElementById('summaryTime').textContent = start + ' - ' + end;
    } else {
        summary.classList.add('d-none');
    }
}
document.querySelectorAll('.day-checkbox').forEach(c => c.addEventListener('change', updateDayCount));
document.getElementById('startTime').addEventListener('change', updateDayCount);
document.getElementById('endTime').addEventListener('change', updateDayCount);

// Presets
document.querySelectorAll('.preset-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const p = JSON.parse(this.dataset.preset);
        document.getElementById('startTime').value = p.start;
        document.getElementById('endTime').value = p.end;
        updateDayCount();
        document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
    });
});
updateDayCount();

// Staff preview — show existing schedules
document.getElementById('staffSelect').addEventListener('change', function () {
    const staffId = this.value;
    const preview = document.getElementById('staffPreview');
    const existingDays = document.getElementById('existingDays');
    if (!staffId) { preview.classList.add('d-none'); return; }
    fetch('/schedules/schedule/' + staffId, {
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    }).then(r => r.json()).then(data => {
        if (data.success && data.schedules.length > 0) {
            let html = '';
            const days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
            data.schedules.forEach(s => {
                const label = s.is_working_day
                    ? '<span class="existing-day bg-success-subtle text-success">' + ucfirst(s.day_of_week) + ' ' + fmtTime(s.start_time) + '-' + fmtTime(s.end_time) + '</span>'
                    : '<span class="existing-day bg-secondary-subtle text-secondary">' + ucfirst(s.day_of_week) + ' OFF</span>';
                html += label;
            });
            existingDays.innerHTML = html;
            preview.classList.remove('d-none');
        } else {
            preview.classList.add('d-none');
        }
    }).catch(() => {});
});

function ucfirst(s) { return s.charAt(0).toUpperCase() + s.slice(1); }
function fmtTime(t) { if (!t) return ''; return t.substring(0,5); }
</script>
@endsection
