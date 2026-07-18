@extends('layouts.vertical', ['title' => 'Work Schedules'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Work Schedules'])

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Staff Work Schedules</h4>
                @can('manage work schedules')
                <a href="{{ route('schedules.create') }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus me-1"></i>Add Schedule
                </a>
                @endcan
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Staff Member</th>
                                <th>Monday</th>
                                <th>Tuesday</th>
                                <th>Wednesday</th>
                                <th>Thursday</th>
                                <th>Friday</th>
                                <th>Saturday</th>
                                <th>Sunday</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($staffMembers as $staff)
                            @php
                                $schedules = $staff->workSchedules->keyBy('day_of_week');
                                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar avatar-sm bg-primary-subtle rounded-circle d-flex align-items-center justify-content-center">
                                            <span class="fw-semibold fs-xs">{{ $staff->initials ?? substr($staff->name, 0, 1) }}</span>
                                        </div>
                                        <span class="fw-medium">{{ $staff->name }}</span>
                                    </div>
                                </td>
                                @foreach($days as $day)
                                <td>
                                    @if(isset($schedules[$day]) && $schedules[$day]->is_working_day)
                                        <span class="badge bg-success-subtle text-success">
                                            {{ $schedules[$day]->start_time_formatted }} - {{ $schedules[$day]->end_time_formatted }}
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">Off</span>
                                    @endif
                                </td>
                                @endforeach
                                <td class="text-center">
                                    <a href="{{ route('schedules.edit', $schedules->first()?->id ?? 0) }}" class="btn btn-sm btn-light">
                                        <i class="ti ti-edit"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="ti ti-calendar-off fs-24 text-muted mb-2 d-block"></i>
                                    <p class="text-muted mb-0">No staff members found.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
