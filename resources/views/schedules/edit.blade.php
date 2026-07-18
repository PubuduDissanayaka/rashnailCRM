@extends('layouts.vertical', ['title' => 'Edit Work Schedule'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Edit Work Schedule'])

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('schedules.update', $workSchedule->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Staff Member *</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">Select Staff</option>
                                @foreach($staffMembers as $staff)
                                <option value="{{ $staff->id }}" {{ old('user_id', $workSchedule->user_id) == $staff->id ? 'selected' : '' }}>{{ $staff->name }}</option>
                                @endforeach
                            </select>
                            @error('user_id') <span class="text-danger"><small>{{ $message }}</small></span> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Day of Week *</label>
                            <select name="day_of_week" class="form-select" required>
                                <option value="">Select Day</option>
                                @foreach(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day)
                                <option value="{{ $day }}" {{ old('day_of_week', $workSchedule->day_of_week) == $day ? 'selected' : '' }}>{{ ucfirst($day) }}</option>
                                @endforeach
                            </select>
                            @error('day_of_week') <span class="text-danger"><small>{{ $message }}</small></span> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Start Time *</label>
                            <input type="time" name="start_time" class="form-control" value="{{ old('start_time', $workSchedule->start_time?->format('H:i')) }}" required>
                            @error('start_time') <span class="text-danger"><small>{{ $message }}</small></span> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">End Time *</label>
                            <input type="time" name="end_time" class="form-control" value="{{ old('end_time', $workSchedule->end_time?->format('H:i')) }}" required>
                            @error('end_time') <span class="text-danger"><small>{{ $message }}</small></span> @enderror
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Grace Period (minutes)</label>
                            <input type="number" name="grace_period_minutes" class="form-control" value="{{ old('grace_period_minutes', $workSchedule->grace_period_minutes ?? 15) }}" min="0" max="60">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input type="checkbox" name="is_working_day" value="1" class="form-check-input" id="is_working_day" {{ old('is_working_day', $workSchedule->is_working_day) ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_working_day">Working Day</label>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <button type="button" class="btn btn-danger" onclick="if(confirm('Delete this schedule?')) { document.getElementById('delete-form').submit(); }">
                            <i class="ti ti-trash me-1"></i> Delete
                        </button>
                        <div>
                            <a href="{{ route('schedules.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Update Schedule</button>
                        </div>
                    </div>
                </form>
                <form id="delete-form" action="{{ route('schedules.destroy', $workSchedule->id) }}" method="POST" class="d-none">
                    @csrf @method('DELETE')
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
