@extends('layouts.vertical', ['title' => 'Edit Attendance'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css', 'node_modules/choices.js/public/assets/styles/choices.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Edit Attendance Record', 'subtitle' => 'Modify existing attendance record'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Editing Attendance for {{ $attendance->user->name ?? 'Unknown User' }}</h4>
                    <div class="page-title-right">
                        <a href="{{ route('attendance.index') }}" class="btn btn-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Back to Attendance
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('attendance.manual.update', $attendance) }}">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user-select" class="form-label">Staff Member</label>
                                    <select class="form-select" id="user-select" name="user_id" data-choices required>
                                        <option value="">Select Staff Member</option>
                                        @foreach($staffMembers as $staff)
                                            <option value="{{ $staff->id }}" {{ $attendance->user_id == $staff->id ? 'selected' : '' }}>
                                                {{ $staff->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="date" name="date" value="{{ old('date', $attendance->date->format('Y-m-d')) }}" required>
                                    @error('date')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="check_in" class="form-label">Check-In Time</label>
                                    <input type="time" class="form-control" id="check_in" name="check_in" value="{{ old('check_in', $attendance->check_in ? $attendance->check_in->format('H:i') : '') }}">
                                    @error('check_in')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="check_out" class="form-label">Check-Out Time</label>
                                    <input type="time" class="form-control" id="check_out" name="check_out" value="{{ old('check_out', $attendance->check_out ? $attendance->check_out->format('H:i') : '') }}">
                                    @error('check_out')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status" data-choices required>
                                        <option value="present" {{ $attendance->status === 'present' ? 'selected' : '' }}>Present</option>
                                        <option value="late" {{ $attendance->status === 'late' ? 'selected' : '' }}>Late</option>
                                        <option value="absent" {{ $attendance->status === 'absent' ? 'selected' : '' }}>Absent</option>
                                        <option value="leave" {{ $attendance->status === 'leave' ? 'selected' : '' }}>On Leave</option>
                                        <option value="half_day" {{ $attendance->status === 'half_day' ? 'selected' : '' }}>Half Day</option>
                                    </select>
                                    @error('status')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="hours_worked" class="form-label">Hours Worked</label>
                                    <input type="number" step="0.01" class="form-control" id="hours_worked" name="hours_worked" value="{{ old('hours_worked', $attendance->hours_worked) }}">
                                    @error('hours_worked')
                                        <div class="text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3">{{ old('notes', $attendance->notes) }}</textarea>
                            @error('notes')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i> Update Attendance
                            </button>
                            <a href="{{ route('attendance.index') }}" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/form-choice.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
@endsection