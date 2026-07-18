@extends('layouts.vertical', ['title' => 'Schedule Details'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Schedule Details'])

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Staff Member</label>
                        <h5>{{ $schedule->user->name ?? 'N/A' }}</h5>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-muted">Day</label>
                        <h5>{{ ucfirst($schedule->day_of_week) }}</h5>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">Start Time</label>
                        <h5>{{ $schedule->start_time_formatted }}</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">End Time</label>
                        <h5>{{ $schedule->end_time_formatted }}</h5>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label text-muted">Status</label>
                        <h5><span class="badge bg-{{ $schedule->is_working_day ? 'success' : 'secondary' }}-subtle text-{{ $schedule->is_working_day ? 'success' : 'secondary' }}">{{ $schedule->working_day_label }}</span></h5>
                    </div>
                </div>
                <div class="text-end">
                    <a href="{{ route('schedules.edit', $schedule) }}" class="btn btn-primary">Edit</a>
                    <a href="{{ route('schedules.index') }}" class="btn btn-light">Back</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
