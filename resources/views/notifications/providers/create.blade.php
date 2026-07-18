@extends('layouts.vertical', ['title' => 'Add Provider'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Add Notification Provider'])

<div class="row"><div class="col-12"><div class="card"><div class="card-body">
<form action="{{ route('notification-providers.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Channel *</label>
            <select name="channel" class="form-select" required>
                <option value="">Select channel...</option>
                @foreach($channelTypes as $val => $label)
                <option value="{{ $val }}" {{ old('channel') == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Provider Type *</label>
            <select name="provider" class="form-select" required>
                <option value="">Select provider...</option>
                @foreach($providerTypes as $channel => $types)
                <optgroup label="{{ ucfirst($channel) }}">
                    @foreach($types as $type)
                    <option value="{{ $type }}" {{ old('provider') == $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Name *</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required placeholder="e.g. Main SMTP">
        </div>
        <div class="col-md-3 mb-3">
            <label class="form-label">Priority</label>
            <input type="number" name="priority" class="form-control" value="{{ old('priority', 1) }}" min="1" max="100">
        </div>
        <div class="col-md-3 mb-3 d-flex align-items-end gap-3">
            <div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" checked><label class="form-check-label" for="is_active">Active</label></div>
            <div class="form-check"><input type="checkbox" name="is_default" value="1" class="form-check-input" id="is_default"><label class="form-check-label" for="is_default">Default</label></div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">Daily Limit</label>
            <input type="number" name="daily_limit" class="form-control" value="{{ old('daily_limit') }}" placeholder="Unlimited if empty">
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">Monthly Limit</label>
            <input type="number" name="monthly_limit" class="form-control" value="{{ old('monthly_limit') }}" placeholder="Unlimited if empty">
        </div>
    </div>
    <div id="config-fields"></div>
    <div class="text-end">
        <a href="{{ route('notification-providers.index') }}" class="btn btn-light me-2">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Create Provider</button>
    </div>
</form>
</div></div></div></div>

<script>
document.querySelector('[name=channel]').addEventListener('change', loadConfigFields);
document.querySelector('[name=provider]').addEventListener('change', loadConfigFields);
function loadConfigFields() {
    const ch = document.querySelector('[name=channel]').value;
    const pr = document.querySelector('[name=provider]').value;
    if (!ch || !pr) return;
    fetch('{{ route("notification-providers.get-config-fields") }}?channel=' + ch + '&provider=' + pr)
        .then(r => r.text()).then(h => document.getElementById('config-fields').innerHTML = h);
}
</script>
@endsection
