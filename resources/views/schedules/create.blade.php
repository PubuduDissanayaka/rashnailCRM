@extends('layouts.vertical', ['title' => 'Add Work Schedule'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Add Work Schedule'])

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('schedules.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Staff Member *</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">Select Staff</option>
                                @foreach($staffMembers as $staff)
                                <option value="{{ $staff->id }}" {{ old('user_id') == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                                @endforeach
                            </select>
                            @error('user_id') <span class="text-danger"><small>{{ $message }}</small></span> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Day of Week *</label>
                            <select name="day_of_week" class="form-select" required>
                                <option value="">Select Day</option>
                                @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                <option value="{{ $day }}" {{ old('day_of_week') == $day ? 'selected' : '' }}>{{ ucfirst($day) }}</option>
                                @endforeach
                            </select>
                            @error('day_of_week') <span class="text-danger"><small>{{ $message }}</small></span> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Start Time *</label>
                            <input type="time" name="start_time" class="form-control" value="{{ old('start_time', '09:00') }}" required>
                            @error('start_time') <span class="text-danger"><small>{{ $message }}</small></span> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">End Time *</label>
                            <input type="time" name="end_time" class="form-control" value="{{ old('end_time', '18:00') }}" required>
                            @error('end_time') <span class="text-danger"><small>{{ $message }}</small></span> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Grace Period (minutes)</label>
                            <input type="number" name="grace_period_minutes" class="form-control" value="{{ old('grace_period_minutes', 15) }}" min="0" max="60">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_working_day" value="1" class="form-check-input" id="is_working_day" {{ old('is_working_day', '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_working_day">Working Day</label>
                        </div>
                    </div>
                    <div class="text-end">
                        <a href="{{ route('schedules.index') }}" class="btn btn-light me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Save Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
