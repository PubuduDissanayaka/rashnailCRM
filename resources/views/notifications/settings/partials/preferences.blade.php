@if(isset($preferences))
<div id="notification-prefs">
    <div class="table-responsive">
        <table class="table table-sm">
            <thead><tr><th>Type</th><th>In-App</th><th>Email</th><th>SMS</th><th>Push</th></tr></thead>
            <tbody>
            @foreach($preferences as $pref)
            <tr>
                <td>{{ $pref->type_label ?? $pref->notification_type }}</td>
                <td><input type="checkbox" class="form-check-input" {{ $pref->channel_in_app ? 'checked' : '' }} disabled></td>
                <td><input type="checkbox" class="form-check-input" {{ $pref->channel_email ? 'checked' : '' }} disabled></td>
                <td><input type="checkbox" class="form-check-input" {{ $pref->channel_sms ? 'checked' : '' }} disabled></td>
                <td><input type="checkbox" class="form-check-input" {{ $pref->channel_push ? 'checked' : '' }} disabled></td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<p class="text-muted">No preferences configured.</p>
@endif
