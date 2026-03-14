@extends('layouts.vertical', ['title' => 'Staff Attendance Detail'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Staff Attendance Detail', 'subtitle' => 'Detailed view of individual staff attendance record'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-user-check me-2 text-primary"></i>
                        {{ $attendance->user->name ?? 'Staff Member' }}
                        <small class="text-muted fw-normal fs-13 ms-2">{{ $attendance->user->email ?? '' }}</small>
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('attendance.staff') }}" class="btn btn-sm btn-light">
                            <i class="ti ti-arrow-left me-1"></i>Back
                        </a>
                        @can('manage attendances')
                        <a href="{{ route('attendance.manual.update', $attendance) }}"
                           onclick="event.preventDefault(); window.location.href='/attendance/manual/{{ $attendance->id }}/edit'"
                           class="btn btn-sm btn-outline-primary">
                            <i class="ti ti-pencil me-1"></i>Edit
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">

                    {{-- Status + Approval row --}}
                    <div class="row g-3 mb-4">
                        <div class="col-md-2">
                            <div class="p-3 rounded bg-light text-center">
                                <p class="text-muted mb-1 fs-12">Date</p>
                                <p class="fw-bold mb-0">{{ $attendance->date->format('M d, Y') }}</p>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3 rounded bg-light text-center">
                                <p class="text-muted mb-1 fs-12">Status</p>
                                @switch($attendance->status)
                                    @case('present') <span class="badge bg-success-subtle text-success fs-12">Present</span> @break
                                    @case('late')    <span class="badge bg-warning-subtle text-warning fs-12">Late</span> @break
                                    @case('absent')  <span class="badge bg-danger-subtle text-danger fs-12">Absent</span> @break
                                    @case('leave')   <span class="badge bg-info-subtle text-info fs-12">On Leave</span> @break
                                    @case('half_day') <span class="badge bg-secondary-subtle text-secondary fs-12">Half Day</span> @break
                                    @default <span class="badge bg-light text-dark fs-12">{{ ucfirst($attendance->status) }}</span>
                                @endswitch
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3 rounded bg-light text-center">
                                <p class="text-muted mb-1 fs-12">Check-In</p>
                                <p class="fw-bold mb-0 text-success">{{ $attendance->check_in ? $attendance->check_in->format('h:i A') : '-' }}</p>
                                @if($attendance->late_arrival_minutes > 0)
                                    <small class="text-danger">Late by {{ $attendance->late_arrival_minutes }}m</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3 rounded bg-light text-center">
                                <p class="text-muted mb-1 fs-12">Check-Out</p>
                                <p class="fw-bold mb-0 text-info">{{ $attendance->check_out ? $attendance->check_out->format('h:i A') : '-' }}</p>
                                @if(isset($attendance->early_departure_minutes) && $attendance->early_departure_minutes > 0)
                                    <small class="text-warning">Early by {{ $attendance->early_departure_minutes }}m</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3 rounded bg-light text-center">
                                <p class="text-muted mb-1 fs-12">Hours Worked</p>
                                <p class="fw-bold mb-0">{{ $attendance->hours_worked ? number_format($attendance->hours_worked, 2) . ' hrs' : '-' }}</p>
                                @if(isset($attendance->overtime_hours) && $attendance->overtime_hours > 0)
                                    <small class="text-success">OT: {{ number_format($attendance->overtime_hours, 2) }} hrs</small>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="p-3 rounded bg-light text-center">
                                <p class="text-muted mb-1 fs-12">Approval</p>
                                @if($attendance->is_approved)
                                    <span class="badge bg-success-subtle text-success"><i class="ti ti-check me-1"></i>Approved</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary"><i class="ti ti-clock me-1"></i>Pending</span>
                                    @can('manage attendances')
                                    <div class="mt-1">
                                        <button class="btn btn-xs btn-success me-1" onclick="approveAttendance({{ $attendance->id }})">✓</button>
                                        <button class="btn btn-xs btn-warning" onclick="rejectAttendance({{ $attendance->id }})">✗</button>
                                    </div>
                                    @endcan
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Location Row --}}
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-2"><i class="ti ti-map-pin me-1 text-success"></i>Location Data</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            @if($attendance->latitude && $attendance->longitude)
                                <a href="https://maps.google.com/?q={{ $attendance->latitude }},{{ $attendance->longitude }}" target="_blank" class="btn btn-sm btn-outline-success">
                                    <i class="ti ti-map-pin me-1"></i>Check-in Location
                                </a>
                                <span class="text-muted align-self-center small">{{ $attendance->latitude }}, {{ $attendance->longitude }}</span>
                            @endif
                            @if($attendance->latitude_out && $attendance->longitude_out)
                                <a href="https://maps.google.com/?q={{ $attendance->latitude_out }},{{ $attendance->longitude_out }}" target="_blank" class="btn btn-sm btn-outline-info">
                                    <i class="ti ti-map-pin me-1"></i>Check-out Location
                                </a>
                                <span class="text-muted align-self-center small">{{ $attendance->latitude_out }}, {{ $attendance->longitude_out }}</span>
                            @endif
                            @if(!$attendance->latitude && !$attendance->latitude_out)
                                <span class="text-muted"><i class="ti ti-map-off me-1"></i>Location not recorded for this session</span>
                            @endif
                        </div>
                    </div>

                    {{-- Notes --}}
                    @if($attendance->notes)
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-2"><i class="ti ti-notes me-1"></i>Notes</h6>
                        <p class="text-muted mb-0 bg-light rounded p-3">{{ $attendance->notes }}</p>
                    </div>
                    @endif

                    {{-- Breaks --}}
                    <div class="mb-4">
                        <h6 class="fw-semibold mb-2"><i class="ti ti-coffee me-1 text-warning"></i>Breaks</h6>
                        @if($attendance->breaks && $attendance->breaks->count())
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr><th>Type</th><th>Start</th><th>End</th><th>Duration</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($attendance->breaks as $break)
                                    <tr>
                                        <td><span class="badge bg-secondary">{{ ucfirst($break->break_type ?? 'Break') }}</span></td>
                                        <td>{{ $break->start_time ? \Carbon\Carbon::parse($break->start_time)->format('h:i A') : '-' }}</td>
                                        <td>{{ $break->end_time ? \Carbon\Carbon::parse($break->end_time)->format('h:i A') : '<span class="text-warning">Active</span>' }}</td>
                                        <td>{{ $break->duration_minutes ?? '-' }} min</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <p class="text-muted mb-0"><i class="ti ti-mood-empty me-1"></i>No break records for this session</p>
                        @endif
                    </div>

                    {{-- Audit Log --}}
                    @can('manage attendances')
                    <div>
                        <h6 class="fw-semibold mb-2"><i class="ti ti-history me-1 text-info"></i>Audit Trail</h6>
                        @if($attendance->auditLogs && $attendance->auditLogs->count())
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light">
                                    <tr><th>Action</th><th>Performed By</th><th>Date & Time</th></tr>
                                </thead>
                                <tbody>
                                    @foreach($attendance->auditLogs as $log)
                                    <tr>
                                        <td><span class="badge bg-light text-dark">{{ ucfirst($log->action) }}</span></td>
                                        <td>{{ $log->user->name ?? 'System' }}</td>
                                        <td class="text-muted">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <p class="text-muted mb-0"><i class="ti ti-clipboard-off me-1"></i>No audit log entries</p>
                        @endif
                    </div>
                    @endcan

                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/attendance.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
@endsection