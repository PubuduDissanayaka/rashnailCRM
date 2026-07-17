<div class="tab-pane" id="security-tab" role="tabpanel">
    <form id="security-form" action="{{ route('settings.update') }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="group" value="security">

        <div class="row">
            <div class="col-md-6">
                <h6 class="fw-semibold mb-3"><i class="ti ti-lock me-1 text-primary"></i> Password Policy</h6>

                <div class="mb-3">
                    <label for="password_min_length" class="form-label">Minimum Password Length</label>
                    <input type="number" class="form-control" id="password_min_length" name="settings[security.password_min_length]"
                           value="{{ old('settings.security.password_min_length', $security['security.password_min_length'] ?? '8') }}" min="4" max="64">
                    <div class="form-text">Minimum characters required for user passwords</div>
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="settings[security.password_require_uppercase]" value="0">
                    <input type="checkbox" class="form-check-input" id="password_require_uppercase"
                           name="settings[security.password_require_uppercase]" value="1"
                           {{ (old('settings.security.password_require_uppercase', $security['security.password_require_uppercase'] ?? '1') == '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="password_require_uppercase">Require uppercase letter</label>
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="settings[security.password_require_numbers]" value="0">
                    <input type="checkbox" class="form-check-input" id="password_require_numbers"
                           name="settings[security.password_require_numbers]" value="1"
                           {{ (old('settings.security.password_require_numbers', $security['security.password_require_numbers'] ?? '1') == '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="password_require_numbers">Require numbers</label>
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="settings[security.password_require_special]" value="0">
                    <input type="checkbox" class="form-check-input" id="password_require_special"
                           name="settings[security.password_require_special]" value="1"
                           {{ (old('settings.security.password_require_special', $security['security.password_require_special'] ?? '0') == '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="password_require_special">Require special characters</label>
                </div>
            </div>

            <div class="col-md-6">
                <h6 class="fw-semibold mb-3"><i class="ti ti-session me-1 text-primary"></i> Session & Access</h6>

                <div class="mb-3">
                    <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                    <input type="number" class="form-control" id="session_timeout" name="settings[security.session_timeout]"
                           value="{{ old('settings.security.session_timeout', $security['security.session_timeout'] ?? '120') }}" min="5" max="1440">
                    <div class="form-text">Auto-logout idle users after this many minutes</div>
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="settings[security.enforce_2fa]" value="0">
                    <input type="checkbox" class="form-check-input" id="enforce_2fa"
                           name="settings[security.enforce_2fa]" value="1"
                           {{ (old('settings.security.enforce_2fa', $security['security.enforce_2fa'] ?? '0') == '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="enforce_2fa">Enforce Two-Factor Authentication</label>
                    <div class="form-text">Require 2FA for all staff accounts</div>
                </div>

                <div class="mb-3 form-check">
                    <input type="hidden" name="settings[security.login_notification]" value="0">
                    <input type="checkbox" class="form-check-input" id="login_notification"
                           name="settings[security.login_notification]" value="1"
                           {{ (old('settings.security.login_notification', $security['security.login_notification'] ?? '1') == '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="login_notification">New device login alerts</label>
                    <div class="form-text">Email staff when login from new device detected</div>
                </div>
            </div>
        </div>

        <div class="row mt-3">
            <div class="col-12">
                <button type="submit" class="btn btn-primary setting-save-btn">
                    <span class="btn-text"><i class="ti ti-device-floppy me-1"></i> Save Security Settings</span>
                    <span class="btn-spinner d-none"><span class="spinner-border spinner-border-sm me-1"></span> Saving...</span>
                </button>
            </div>
        </div>
    </form>
</div>
