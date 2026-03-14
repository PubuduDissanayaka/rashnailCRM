        <div class="row">
            <div class="col-12">
                <h5 class="mt-3 mb-2">Business Hours for Attendance</h5>
                
                <div class="alert alert-info">
                    <i class="ti ti-info-circle me-2"></i>
                    Configure business hours for attendance tracking. These hours determine when staff are expected to work.
                </div>
                
                @php
                    $businessHours = [];
                    if (isset($business['attendance.business_hours'])) {
                        $value = $business['attendance.business_hours'];
                        if (is_array($value)) {
                            $businessHours = $value;
                        } elseif (is_string($value)) {
                            $businessHours = json_decode($value, true) ?? [];
                        }
                    }
                    
                    // Set defaults if not exists
                    $defaults = [
                        'grace_period_minutes' => 15,
                        'overtime_start_after_hours' => 1,
                        'minimum_shift_hours' => 4,
                        'maximum_shift_hours' => 12,
                        'half_day_threshold_hours' => 4,
                        'weekdays' => [
                            'monday' => ['enabled' => true, 'open' => '09:00', 'close' => '17:00'],
                            'tuesday' => ['enabled' => true, 'open' => '09:00', 'close' => '17:00'],
                            'wednesday' => ['enabled' => true, 'open' => '09:00', 'close' => '17:00'],
                            'thursday' => ['enabled' => true, 'open' => '09:00', 'close' => '17:00'],
                            'friday' => ['enabled' => true, 'open' => '09:00', 'close' => '17:00'],
                            'saturday' => ['enabled' => false, 'open' => '09:00', 'close' => '17:00'],
                            'sunday' => ['enabled' => false, 'open' => '09:00', 'close' => '17:00'],
                        ]
                    ];
                    
                    $businessHours = array_merge($defaults, $businessHours);
                @endphp
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="grace_period_minutes" class="form-label">Grace Period (minutes)</label>
                            <input type="number" class="form-control" id="grace_period_minutes" 
                                   name="settings[attendance.business_hours][grace_period_minutes]"
                                   value="{{ old('settings.attendance.business_hours.grace_period_minutes', $businessHours['grace_period_minutes'] ?? 15) }}"
                                   min="0" max="60">
                            <div class="form-text">Allowed minutes after opening time before being marked late</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="overtime_start_after_hours" class="form-label">Overtime Start (hours)</label>
                            <input type="number" class="form-control" id="overtime_start_after_hours" 
                                   name="settings[attendance.business_hours][overtime_start_after_hours]"
                                   value="{{ old('settings.attendance.business_hours.overtime_start_after_hours', $businessHours['overtime_start_after_hours'] ?? 1) }}"
                                   min="0" max="24" step="0.5">
                            <div class="form-text">Hours after closing time when overtime begins</div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="minimum_shift_hours" class="form-label">Minimum Shift Hours</label>
                            <input type="number" class="form-control" id="minimum_shift_hours" 
                                   name="settings[attendance.business_hours][minimum_shift_hours]"
                                   value="{{ old('settings.attendance.business_hours.minimum_shift_hours', $businessHours['minimum_shift_hours'] ?? 4) }}"
                                   min="0" max="24" step="0.5">
                            <div class="form-text">Minimum hours for a valid shift</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="maximum_shift_hours" class="form-label">Maximum Shift Hours</label>
                            <input type="number" class="form-control" id="maximum_shift_hours" 
                                   name="settings[attendance.business_hours][maximum_shift_hours]"
                                   value="{{ old('settings.attendance.business_hours.maximum_shift_hours', $businessHours['maximum_shift_hours'] ?? 12) }}"
                                   min="0" max="24" step="0.5">
                            <div class="form-text">Maximum hours for a single shift</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label for="half_day_threshold_hours" class="form-label">Half Day Threshold (hours)</label>
                            <input type="number" class="form-control" id="half_day_threshold_hours" 
                                   name="settings[attendance.business_hours][half_day_threshold_hours]"
                                   value="{{ old('settings.attendance.business_hours.half_day_threshold_hours', $businessHours['half_day_threshold_hours'] ?? 4) }}"
                                   min="0" max="24" step="0.5">
                            <div class="form-text">Hours worked below this threshold counts as half day</div>
                        </div>
                    </div>
                </div>
                
                <h6 class="mb-3">Weekly Schedule</h6>
                <div class="row">
                    <div class="col-md-6">
                        @foreach(['monday', 'tuesday', 'wednesday', 'thursday'] as $day)
                        <div class="d-flex align-items-center mb-2">
                            <div class="form-check me-3">
                                <input class="form-check-input day-enabled" type="checkbox" 
                                       id="{{ $day }}_enabled"
                                       name="settings[attendance.business_hours][weekdays][{{ $day }}][enabled]"
                                       value="1"
                                       @if($businessHours['weekdays'][$day]['enabled'] ?? false) checked @endif>
                                <label class="form-check-label" for="{{ $day }}_enabled">
                                    {{ ucfirst($day) }}
                                </label>
                            </div>
                            <div class="flex-grow-1 d-flex">
                                <input type="time" class="form-control me-2" 
                                       id="{{ $day }}_open" 
                                       name="settings[attendance.business_hours][weekdays][{{ $day }}][open]"
                                       value="{{ $businessHours['weekdays'][$day]['open'] ?? '09:00' }}"
                                       @if(!($businessHours['weekdays'][$day]['enabled'] ?? false)) disabled @endif>
                                <input type="time" class="form-control" 
                                       id="{{ $day }}_close" 
                                       name="settings[attendance.business_hours][weekdays][{{ $day }}][close]"
                                       value="{{ $businessHours['weekdays'][$day]['close'] ?? '17:00' }}"
                                       @if(!($businessHours['weekdays'][$day]['enabled'] ?? false)) disabled @endif>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    
                    <div class="col-md-6">
                        @foreach(['friday', 'saturday', 'sunday'] as $day)
                        <div class="d-flex align-items-center mb-2">
                            <div class="form-check me-3">
                                <input class="form-check-input day-enabled" type="checkbox" 
                                       id="{{ $day }}_enabled"
                                       name="settings[attendance.business_hours][weekdays][{{ $day }}][enabled]"
                                       value="1"
                                       @if($businessHours['weekdays'][$day]['enabled'] ?? false) checked @endif>
                                <label class="form-check-label" for="{{ $day }}_enabled">
                                    {{ ucfirst($day) }}
                                </label>
                            </div>
                            <div class="flex-grow-1 d-flex">
                                <input type="time" class="form-control me-2" 
                                       id="{{ $day }}_open" 
                                       name="settings[attendance.business_hours][weekdays][{{ $day }}][open]"
                                       value="{{ $businessHours['weekdays'][$day]['open'] ?? '09:00' }}"
                                       @if(!($businessHours['weekdays'][$day]['enabled'] ?? false)) disabled @endif>
                                <input type="time" class="form-control" 
                                       id="{{ $day }}_close" 
                                       name="settings[attendance.business_hours][weekdays][{{ $day }}][close]"
                                       value="{{ $businessHours['weekdays'][$day]['close'] ?? '17:00' }}"
                                       @if(!($businessHours['weekdays'][$day]['enabled'] ?? false)) disabled @endif>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-primary" id="enable-all-days">
                        <i class="ti ti-check me-1"></i> Enable All Days
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="disable-all-days">
                        <i class="ti ti-x me-1"></i> Disable All Days
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" id="set-standard-hours">
                        <i class="ti ti-clock me-1"></i> Set Standard 9-5
                    </button>
                </div>
            </div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Enable/disable time inputs based on checkbox
            document.querySelectorAll('.day-enabled').forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    const day = this.id.replace('_enabled', '');
                    const openInput = document.getElementById(day + '_open');
                    const closeInput = document.getElementById(day + '_close');
                    
                    if (openInput && closeInput) {
                        openInput.disabled = !this.checked;
                        closeInput.disabled = !this.checked;
                        
                        if (!this.checked) {
                            openInput.value = '';
                            closeInput.value = '';
                        } else {
                            openInput.value = openInput.value || '09:00';
                            closeInput.value = closeInput.value || '17:00';
                        }
                    }
                });
            });
            
            // Enable all days button
            document.getElementById('enable-all-days')?.addEventListener('click', function() {
                document.querySelectorAll('.day-enabled').forEach(function(checkbox) {
                    checkbox.checked = true;
                    checkbox.dispatchEvent(new Event('change'));
                });
            });
            
            // Disable all days button
            document.getElementById('disable-all-days')?.addEventListener('click', function() {
                document.querySelectorAll('.day-enabled').forEach(function(checkbox) {
                    checkbox.checked = false;
                    checkbox.dispatchEvent(new Event('change'));
                });
            });
            
            // Set standard hours button
            document.getElementById('set-standard-hours')?.addEventListener('click', function() {
                document.querySelectorAll('.day-enabled').forEach(function(checkbox) {
                    if (checkbox.checked) {
                        const day = checkbox.id.replace('_enabled', '');
                        const openInput = document.getElementById(day + '_open');
                        const closeInput = document.getElementById(day + '_close');
                        
                        if (openInput) openInput.value = '09:00';
                        if (closeInput) closeInput.value = '17:00';
                    }
                });
            });
        });
        </script>