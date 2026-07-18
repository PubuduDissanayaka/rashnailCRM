<div id="provider-config-fields">
    @if(isset($fields) && count($fields))
    <div class="card mt-3"><div class="card-header"><h5 class="card-title">Provider Configuration</h5></div><div class="card-body"><div class="row">
        @foreach($fields as $field)
        <div class="col-md-6 mb-3">
            <label class="form-label">{{ $field['label'] }} @if($field['required'] ?? false)* @endif</label>
            @if(($field['type'] ?? 'text') === 'select')
            <select name="config[{{ $field['name'] }}]" class="form-select" {{ ($field['required'] ?? false) ? 'required' : '' }}>
                @foreach(($field['options'] ?? []) as $optVal => $optLabel)
                <option value="{{ $optVal }}" {{ (old("config." . $field['name'], $field['default'] ?? '') == $optVal) ? 'selected' : '' }}>{{ $optLabel }}</option>
                @endforeach
            </select>
            @elseif(($field['type'] ?? 'text') === 'password')
            <input type="password" name="config[{{ $field['name'] }}]" class="form-control"
                placeholder="{{ $field['label'] }}" {{ ($field['required'] ?? false) ? 'required' : '' }}>
            @elseif(($field['type'] ?? 'text') === 'email')
            <input type="email" name="config[{{ $field['name'] }}]" class="form-control"
                value="{{ old("config." . $field['name'], $field['default'] ?? '') }}" {{ ($field['required'] ?? false) ? 'required' : '' }}>
            @else
            <input type="{{ $field['type'] ?? 'text' }}" name="config[{{ $field['name'] }}]" class="form-control"
                value="{{ old("config." . $field['name'], $field['default'] ?? '') }}" {{ ($field['required'] ?? false) ? 'required' : '' }}>
            @endif
        </div>
        @endforeach
    </div></div></div>
    @endif
</div>
