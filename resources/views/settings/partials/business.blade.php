<div class="tab-pane active" id="business-tab" role="tabpanel">
    <form id="business-form" action="{{ route('settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        <input type="hidden" name="group" value="business">
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="business_name" class="form-label">Business Name</label>
                    <input type="text" class="form-control" id="business_name" name="settings[business.name]" 
                           value="{{ old('settings.business.name', $business['business.name'] ?? '') }}">
                    <div class="form-text">Business name displayed throughout the application</div>
                </div>
                
                <div class="mb-3">
                    <label for="business_tagline" class="form-label">Tagline</label>
                    <input type="text" class="form-control" id="business_tagline" name="settings[business.tagline]" 
                           value="{{ old('settings.business.tagline', $business['business.tagline'] ?? '') }}">
                    <div class="form-text">Business tagline or slogan</div>
                </div>
                
                <div class="mb-3">
                    <label for="business_phone" class="form-label">Phone</label>
                    <input type="text" class="form-control" id="business_phone" name="settings[business.phone]" 
                           value="{{ old('settings.business.phone', $business['business.phone'] ?? '') }}">
                    <div class="form-text">Primary business phone number</div>
                </div>
                
                <div class="mb-3">
                    <label for="business_email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="business_email" name="settings[business.email]" 
                           value="{{ old('settings.business.email', $business['business.email'] ?? '') }}">
                    <div class="form-text">Primary business email address</div>
                </div>
                
                <div class="mb-3">
                    <label for="business_website" class="form-label">Website</label>
                    <input type="url" class="form-control" id="business_website" name="settings[business.website]" 
                           value="{{ old('settings.business.website', $business['business.website'] ?? '') }}">
                    <div class="form-text">Business website URL</div>
                </div>
                
                <div class="mb-3">
                    <label for="business_timezone" class="form-label fw-semibold">
                        <i class="ti ti-clock me-1 text-primary"></i>Timezone
                    </label>
                    @php
                        $savedTz = old('settings.business.timezone', $business['business.timezone'] ?? 'Asia/Kolkata');
                        $timezones = [
                            'Asia' => [
                                'Asia/Kolkata'        => 'Asia/Kolkata (IST +05:30) 🇮🇳',
                                'Asia/Colombo'        => 'Asia/Colombo (SL +05:30) 🇱🇰',
                                'Asia/Karachi'        => 'Asia/Karachi (PKT +05:00) 🇵🇰',
                                'Asia/Dhaka'          => 'Asia/Dhaka (BST +06:00) 🇧🇩',
                                'Asia/Kathmandu'      => 'Asia/Kathmandu (NPT +05:45) 🇳🇵',
                                'Asia/Dubai'          => 'Asia/Dubai (GST +04:00) 🇦🇪',
                                'Asia/Riyadh'         => 'Asia/Riyadh (AST +03:00) 🇸🇦',
                                'Asia/Tehran'         => 'Asia/Tehran (IRST +03:30) 🇮🇷',
                                'Asia/Kabul'          => 'Asia/Kabul (AFT +04:30) 🇦🇫',
                                'Asia/Tashkent'       => 'Asia/Tashkent (+05:00)',
                                'Asia/Almaty'         => 'Asia/Almaty (+06:00)',
                                'Asia/Bangkok'        => 'Asia/Bangkok (ICT +07:00) 🇹🇭',
                                'Asia/Jakarta'        => 'Asia/Jakarta (WIB +07:00) 🇮🇩',
                                'Asia/Singapore'      => 'Asia/Singapore (SGT +08:00) 🇸🇬',
                                'Asia/Shanghai'       => 'Asia/Shanghai (CST +08:00) 🇨🇳',
                                'Asia/Taipei'         => 'Asia/Taipei (CST +08:00) 🇹🇼',
                                'Asia/Kuala_Lumpur'   => 'Asia/Kuala_Lumpur (MYT +08:00) 🇲🇾',
                                'Asia/Hong_Kong'      => 'Asia/Hong_Kong (HKT +08:00) 🇭🇰',
                                'Asia/Tokyo'          => 'Asia/Tokyo (JST +09:00) 🇯🇵',
                                'Asia/Seoul'          => 'Asia/Seoul (KST +09:00) 🇰🇷',
                                'Asia/Yangon'         => 'Asia/Yangon (MMT +06:30) 🇲🇲',
                                'Asia/Phnom_Penh'     => 'Asia/Phnom_Penh (ICT +07:00) 🇰🇭',
                                'Asia/Beirut'         => 'Asia/Beirut (EET +02:00/+03:00) 🇱🇧',
                                'Asia/Jerusalem'      => 'Asia/Jerusalem (IST +02:00/+03:00) 🇮🇱',
                                'Asia/Kuwait'         => 'Asia/Kuwait (AST +03:00) 🇰🇼',
                                'Asia/Muscat'         => 'Asia/Muscat (GST +04:00) 🇴🇲',
                                'Asia/Bahrain'        => 'Asia/Bahrain (AST +03:00) 🇧🇭',
                                'Asia/Doha'           => 'Asia/Doha (AST +03:00) 🇶🇦',
                            ],
                            'Europe' => [
                                'Europe/London'       => 'Europe/London (GMT/BST) 🇬🇧',
                                'Europe/Paris'        => 'Europe/Paris (CET +01:00/+02:00) 🇫🇷',
                                'Europe/Berlin'       => 'Europe/Berlin (CET +01:00/+02:00) 🇩🇪',
                                'Europe/Rome'         => 'Europe/Rome (CET +01:00/+02:00) 🇮🇹',
                                'Europe/Madrid'       => 'Europe/Madrid (CET +01:00/+02:00) 🇪🇸',
                                'Europe/Amsterdam'    => 'Europe/Amsterdam (CET +01:00/+02:00) 🇳🇱',
                                'Europe/Brussels'     => 'Europe/Brussels (CET +01:00/+02:00) 🇧🇪',
                                'Europe/Zurich'       => 'Europe/Zurich (CET +01:00/+02:00) 🇨🇭',
                                'Europe/Stockholm'    => 'Europe/Stockholm (CET +01:00/+02:00) 🇸🇪',
                                'Europe/Warsaw'       => 'Europe/Warsaw (CET +01:00/+02:00) 🇵🇱',
                                'Europe/Athens'       => 'Europe/Athens (EET +02:00/+03:00) 🇬🇷',
                                'Europe/Istanbul'     => 'Europe/Istanbul (TRT +03:00) 🇹🇷',
                                'Europe/Moscow'       => 'Europe/Moscow (MSK +03:00) 🇷🇺',
                                'Europe/Helsinki'     => 'Europe/Helsinki (EET +02:00/+03:00) 🇫🇮',
                                'Europe/Bucharest'    => 'Europe/Bucharest (EET +02:00/+03:00) 🇷🇴',
                                'Europe/Budapest'     => 'Europe/Budapest (CET +01:00/+02:00) 🇭🇺',
                                'Europe/Vienna'       => 'Europe/Vienna (CET +01:00/+02:00) 🇦🇹',
                                'Europe/Prague'       => 'Europe/Prague (CET +01:00/+02:00) 🇨🇿',
                                'Europe/Dublin'       => 'Europe/Dublin (GMT/IST) 🇮🇪',
                                'Europe/Lisbon'       => 'Europe/Lisbon (WET) 🇵🇹',
                            ],
                            'America' => [
                                'America/New_York'    => 'America/New_York (ET -05:00/-04:00) 🇺🇸',
                                'America/Chicago'     => 'America/Chicago (CT -06:00/-05:00) 🇺🇸',
                                'America/Denver'      => 'America/Denver (MT -07:00/-06:00) 🇺🇸',
                                'America/Phoenix'     => 'America/Phoenix (MST -07:00) 🇺🇸',
                                'America/Los_Angeles' => 'America/Los_Angeles (PT -08:00/-07:00) 🇺🇸',
                                'America/Anchorage'   => 'America/Anchorage (AKT -09:00/-08:00) 🇺🇸',
                                'America/Toronto'     => 'America/Toronto (ET -05:00/-04:00) 🇨🇦',
                                'America/Vancouver'   => 'America/Vancouver (PT -08:00/-07:00) 🇨🇦',
                                'America/Mexico_City' => 'America/Mexico_City (CT -06:00/-05:00) 🇲🇽',
                                'America/Bogota'      => 'America/Bogota (COT -05:00) 🇨🇴',
                                'America/Lima'        => 'America/Lima (PET -05:00) 🇵🇪',
                                'America/Sao_Paulo'   => 'America/Sao_Paulo (BRT -03:00/-02:00) 🇧🇷',
                                'America/Buenos_Aires'=> 'America/Buenos_Aires (ART -03:00) 🇦🇷',
                                'America/Santiago'    => 'America/Santiago (CLT -04:00/-03:00) 🇨🇱',
                                'America/Caracas'     => 'America/Caracas (VET -04:00) 🇻🇪',
                                'America/Havana'      => 'America/Havana (CST -05:00/-04:00) 🇨🇺',
                            ],
                            'Africa' => [
                                'Africa/Cairo'        => 'Africa/Cairo (EET +02:00) 🇪🇬',
                                'Africa/Johannesburg' => 'Africa/Johannesburg (SAST +02:00) 🇿🇦',
                                'Africa/Lagos'        => 'Africa/Lagos (WAT +01:00) 🇳🇬',
                                'Africa/Nairobi'      => 'Africa/Nairobi (EAT +03:00) 🇰🇪',
                                'Africa/Casablanca'   => 'Africa/Casablanca (WET/WEST) 🇲🇦',
                                'Africa/Accra'        => 'Africa/Accra (GMT) 🇬🇭',
                            ],
                            'Pacific & Australia' => [
                                'Australia/Sydney'    => 'Australia/Sydney (AEST +10:00/+11:00) 🇦🇺',
                                'Australia/Melbourne' => 'Australia/Melbourne (AEST +10:00/+11:00) 🇦🇺',
                                'Australia/Brisbane'  => 'Australia/Brisbane (AEST +10:00) 🇦🇺',
                                'Australia/Perth'     => 'Australia/Perth (AWST +08:00) 🇦🇺',
                                'Australia/Adelaide'  => 'Australia/Adelaide (ACST +09:30/+10:30) 🇦🇺',
                                'Pacific/Auckland'    => 'Pacific/Auckland (NZST +12:00/+13:00) 🇳🇿',
                                'Pacific/Honolulu'    => 'Pacific/Honolulu (HST -10:00) 🇺🇸',
                                'Pacific/Fiji'        => 'Pacific/Fiji (FJT +12:00) 🇫🇯',
                            ],
                            'UTC & Other' => [
                                'UTC'                 => 'UTC (Universal Coordinated Time)',
                                'GMT'                 => 'GMT (Greenwich Mean Time)',
                            ],
                        ];
                    @endphp
                    <select class="form-select" id="business_timezone" name="settings[business.timezone]">
                        @foreach($timezones as $region => $tzList)
                            <optgroup label="{{ $region }}">
                                @foreach($tzList as $tz => $label)
                                    <option value="{{ $tz }}" {{ $savedTz === $tz ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                    <div class="form-text">
                        <i class="ti ti-alert-circle text-warning me-1"></i>
                        This timezone applies to all attendance records, reports, and timestamps throughout the application.
                        Currently stored: <strong>{{ $savedTz }}</strong>
                    </div>
                </div>

            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="business_address" class="form-label">Address</label>
                    <input type="text" class="form-control" id="business_address" name="settings[business.address]" 
                           value="{{ old('settings.business.address', $business['business.address'] ?? '') }}">
                    <div class="form-text">Street address</div>
                </div>
                
                <div class="mb-3">
                    <label for="business_city" class="form-label">City</label>
                    <input type="text" class="form-control" id="business_city" name="settings[business.city]" 
                           value="{{ old('settings.business.city', $business['business.city'] ?? '') }}">
                </div>
                
                <div class="mb-3">
                    <label for="business_state" class="form-label">State/Province</label>
                    <input type="text" class="form-control" id="business_state" name="settings[business.state]" 
                           value="{{ old('settings.business.state', $business['business.state'] ?? '') }}">
                </div>
                
                <div class="mb-3">
                    <label for="business_zip" class="form-label">ZIP/Postal Code</label>
                    <input type="text" class="form-control" id="business_zip" name="settings[business.zip]" 
                           value="{{ old('settings.business.zip', $business['business.zip'] ?? '') }}">
                </div>
                
                <div class="mb-3">
                    <label for="business_about" class="form-label">About Business</label>
                    <textarea class="form-control" id="business_about" name="settings[business.about]" rows="3">{{ old('settings.business.about', $business['business.about'] ?? '') }}</textarea>
                    <div class="form-text">About business description</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="logo" class="form-label">Logo</label>
                    <input type="file" class="form-control" id="logo" name="logo" accept="image/*">
                    <div class="form-text">Recommended: 200x60px</div>
                    @if($business['business.logo'] ?? null)
                        <div class="mt-2">
                            <label class="form-label">Current Logo:</label>
                            <div>
                                <img src="{{ Storage::url($business['business.logo']) }}" alt="Current Logo" 
                                     style="max-height: 60px; max-width: 200px;">
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="favicon" class="form-label">Favicon</label>
                    <input type="file" class="form-control" id="favicon" name="favicon" accept="image/*">
                    <div class="form-text">Recommended: 32x32px</div>
                    @if($business['business.favicon'] ?? null)
                        <div class="mt-2">
                            <label class="form-label">Current Favicon:</label>
                            <div>
                                <img src="{{ Storage::url($business['business.favicon']) }}" alt="Current Favicon"
                                     style="max-height: 32px; max-width: 32px;">
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <h5 class="mt-2 mb-3">
                    <i class="ti ti-photo me-1 text-primary"></i> Auth Page Background Image
                </h5>
                <p class="text-muted small mb-3">This image appears on the right side of all login, register, and password reset pages.</p>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="auth_bg_image" class="form-label">Background Image</label>
                    <input type="file" class="form-control" id="auth_bg_image" name="auth_bg_image" accept="image/jpeg,image/png,image/webp"
                           onchange="previewAuthBg(this)">
                    <div class="form-text">Recommended: 800×1200px or taller. JPG, PNG or WebP. Max 4MB.</div>
                    @error('auth_bg_image')
                        <span class="text-danger small">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="col-md-6">
                @php
                    $authBg = $business['business.auth_bg_image'] ?? null;
                    $authBgUrl = $authBg ? \Illuminate\Support\Facades\Storage::url($authBg) : asset('images/auth.jpg');
                @endphp
                <div class="mb-3">
                    <label class="form-label">Preview</label>
                    <div class="position-relative rounded overflow-hidden border" style="height: 160px; background: #f0f0f0;">
                        <img id="auth-bg-preview" src="{{ $authBgUrl }}" alt="Auth Background Preview"
                             style="width: 100%; height: 100%; object-fit: cover; object-position: center;">
                        <div class="position-absolute bottom-0 start-0 end-0 p-1 text-center"
                             style="background: rgba(0,0,0,0.45);">
                            @if($authBg)
                                <span class="badge bg-success">Custom image active</span>
                            @else
                                <span class="badge bg-secondary">Default image</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @if($authBg)
            <div class="col-12">
                <button type="button" class="btn btn-sm btn-outline-danger" id="remove-auth-bg-btn">
                    <i class="ti ti-trash me-1"></i> Remove Custom Image (restore default)
                </button>
                <input type="hidden" name="settings[business.auth_bg_image_remove]" id="auth-bg-remove-flag" value="">
            </div>
            @endif
        </div>

        <script>
            function previewAuthBg(input) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = e => {
                        document.getElementById('auth-bg-preview').src = e.target.result;
                    };
                    reader.readAsDataURL(input.files[0]);
                }
            }
            const removeBtn = document.getElementById('remove-auth-bg-btn');
            if (removeBtn) {
                removeBtn.addEventListener('click', function() {
                    if (confirm('Remove the custom auth background image and restore the default?')) {
                        document.getElementById('auth-bg-remove-flag').value = '1';
                        this.closest('form').submit();
                    }
                });
            }
        </script>
        
        <div class="row">
            <div class="col-12">
                <h5 class="mt-3 mb-2">Social Media</h5>
                
                <div class="mb-3">
                    <label for="social_facebook" class="form-label">Facebook URL</label>
                    <input type="url" class="form-control" id="social_facebook" name="settings[business.social.facebook]" 
                           value="{{ old('settings.business.social.facebook', $business['business.social.facebook'] ?? '') }}">
                </div>
                
                <div class="mb-3">
                    <label for="social_instagram" class="form-label">Instagram URL</label>
                    <input type="url" class="form-control" id="social_instagram" name="settings[business.social.instagram]" 
                           value="{{ old('settings.business.social.instagram', $business['business.social.instagram'] ?? '') }}">
                </div>
                
                <div class="mb-3">
                    <label for="social_twitter" class="form-label">Twitter URL</label>
                    <input type="url" class="form-control" id="social_twitter" name="settings[business.social.twitter]" 
                           value="{{ old('settings.business.social.twitter', $business['business.social.twitter'] ?? '') }}">
                </div>
                
                <div class="mb-3">
                    <label for="social_linkedin" class="form-label">LinkedIn URL</label>
                    <input type="url" class="form-control" id="social_linkedin" name="settings[business.social.linkedin]" 
                           value="{{ old('settings.business.social.linkedin', $business['business.social.linkedin'] ?? '') }}">
                </div>
            </div>
        </div>
        
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
            </div>
        </div>
        
        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i> Save Business Settings
            </button>
        </div>
    </form>
</div>