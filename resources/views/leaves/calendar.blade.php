@extends('layouts.vertical', ['title' => 'Leave Calendar'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Leave Calendar'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title">Upcoming Leaves</h4>
                        <p class="text-muted mb-0">Next 3 months</p>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th>Staff</th>
                                <th>Type</th>
                                <th>Start</th>
                                <th>End</th>
                                <th>Days</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaveRequests as $lr)
                            @php
                                $statusColors = ['approved' => 'success', 'pending' => 'warning'];
                                $color = $statusColors[$lr->status] ?? 'secondary';
                            @endphp
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm rounded-circle bg-soft-primary me-2">
                                            <span class="avatar-title rounded-circle text-uppercase">{{ substr($lr->user->name ?? '?', 0, 1) }}</span>
                                        </div>
                                        <h5 class="fs-base mb-0">{{ $lr->user->name ?? 'Unknown' }}</h5>
                                    </div>
                                </td>
                                <td>{{ $lr->type_label }}</td>
                                <td>{{ $lr->start_date->format('M d, Y') }}</td>
                                <td>{{ $lr->end_date->format('M d, Y') }}</td>
                                <td>{{ $lr->days_count }}</td>
                                <td>
                                    <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
                                        {{ ucfirst($lr->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="ti ti-calendar fs-32 mb-2 d-block"></i>
                                    No upcoming leaves in the next 3 months.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
