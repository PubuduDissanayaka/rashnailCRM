<div class="tab-pane" id="appointment-tab" role="tabpanel">
    <form id="appointment-form" action="{{ route('settings.update') }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="group" value="appointment">
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="default_duration" class="form-label">Default Appointment Duration (minutes)</label>
                    <input type="number" class="form-control" id="default_duration" name="settings[appointment.default_duration]" 
                           value="{{ old('settings.appointment.default_duration', $appointment['appointment.default_duration'] ?? '60') }}">
                    <div class="form-text">Default appointment duration in minutes</div>
                </div>
                
                <div class="mb-3">
                    <label for="buffer_time" class="form-label">Buffer Time Between Appointments (minutes)</label>
                    <input type="number" class="form-control" id="buffer_time" name="settings[appointment.buffer_time]" 
                           value="{{ old('settings.appointment.buffer_time', $appointment['appointment.buffer_time'] ?? '15') }}">
                    <div class="form-text">Buffer time between appointments (minutes)</div>
                </div>
                
                <div class="mb-3">
                    <label for="max_per_day" class="form-label">Maximum Appointments Per Day</label>
                    <input type="number" class="form-control" id="max_per_day" name="settings[appointment.max_per_day]" 
                           value="{{ old('settings.appointment.max_per_day', $appointment['appointment.max_per_day'] ?? '20') }}">
                    <div class="form-text">Maximum appointments per day</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label for="advance_booking_days" class="form-label">Advance Booking Limit (days)</label>
                    <input type="number" class="form-control" id="advance_booking_days" name="settings[appointment.advance_booking_days]" 
                           value="{{ old('settings.appointment.advance_booking_days', $appointment['appointment.advance_booking_days'] ?? '30') }}">
                    <div class="form-text">How far in advance customers can book (days)</div>
                </div>
                
                <div class="mb-3">
                    <label for="min_advance_hours" class="form-label">Minimum Advance Notice (hours)</label>
                    <input type="number" class="form-control" id="min_advance_hours" name="settings[appointment.min_advance_hours]" 
                           value="{{ old('settings.appointment.min_advance_hours', $appointment['appointment.min_advance_hours'] ?? '2') }}">
                    <div class="form-text">Minimum advance notice for booking (hours)</div>
                </div>
                
                <div class="mb-3">
                    <label for="cancellation_hours" class="form-label">Cancellation Deadline (hours)</label>
                    <input type="number" class="form-control" id="cancellation_hours" name="settings[appointment.cancellation_hours]" 
                           value="{{ old('settings.appointment.cancellation_hours', $appointment['appointment.cancellation_hours'] ?? '24') }}">
                    <div class="form-text">Cancellation deadline (hours before appointment)</div>
                </div>
            </div>
        </div>
        
        <div class="mb-3">
            <label for="cancellation_policy" class="form-label">Cancellation Policy</label>
            <textarea class="form-control" id="cancellation_policy" name="settings[appointment.cancellation_policy]" rows="3">{{ old('settings.appointment.cancellation_policy', $appointment['appointment.cancellation_policy'] ?? 'Appointments must be cancelled at least 24 hours in advance to avoid cancellation fees.') }}</textarea>
            <div class="form-text">Cancellation policy text shown to customers</div>
        </div>
        
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3 form-check">
                    <input type="hidden" name="settings[appointment.online_booking]" value="0">
                    <input type="checkbox" class="form-check-input" id="online_booking" name="settings[appointment.online_booking]" 
                           value="1" {{ (old('settings.appointment.online_booking', $appointment['appointment.online_booking'] ?? '0') == '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="online_booking">Enable Online Booking</label>
                    <div class="form-text">Enable online booking (future feature)</div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3 form-check">
                    <input type="hidden" name="settings[appointment.require_confirmation]" value="0">
                    <input type="checkbox" class="form-check-input" id="require_confirmation" name="settings[appointment.require_confirmation]" 
                           value="1" {{ (old('settings.appointment.require_confirmation', $appointment['appointment.require_confirmation'] ?? '1') == '1') ? 'checked' : '' }}>
                    <label class="form-check-label" for="require_confirmation">Require Confirmation</label>
                    <div class="form-text">Require customer confirmation for bookings</div>
                </div>
            </div>
        </div>
        
        <div class="d-flex justify-content-end mt-4">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i> Save Appointment Settings
            </button>
        </div>
    </form>
</div>