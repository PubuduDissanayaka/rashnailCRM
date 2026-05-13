@extends('layouts.vertical', ['title' => 'Leave Balances'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Leave Balances'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title">Staff Leave Balances</h4>
                        <p class="text-muted mb-0">Current year leave entitlements</p>
                    </div>
                    @can('manage leave balances')
                    <a href="{{ route('leave-balances.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus me-1"></i> Add Balance
                    </a>
                    @endcan
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="ti ti-check me-1"></i> {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="ti ti-alert-circle me-1"></i> {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-custom table-centered table-hover w-100 mb-0">
                            <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                <tr class="text-uppercase fs-xxs">
                                    <th>Staff</th>
                                    <th>Leave Type</th>
                                    <th>Year</th>
                                    <th>Total</th>
                                    <th>Used</th>
                                    <th>Remaining</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($staffMembers as $staff)
                                    @php $balances = $staff->leaveBalances()->where('year', now()->year)->get(); @endphp
                                    @if($balances->isEmpty())
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-sm rounded-circle bg-soft-primary me-2">
                                                    <span class="avatar-title text-uppercase">{{ substr($staff->name, 0, 1) }}</span>
                                                </div>
                                                <h5 class="fs-base mb-0">{{ $staff->name }}</h5>
                                            </div>
                                        </td>
                                        <td colspan="5"><span class="text-muted">No balances configured</span></td>
                                        <td class="text-center">
                                            @can('manage leave balances')
                                            <a href="{{ route('leave-balances.create') }}?user_id={{ $staff->id }}" class="btn btn-light btn-icon btn-sm rounded-circle" title="Add">
                                                <i class="ti ti-plus fs-lg"></i>
                                            </a>
                                            @endcan
                                        </td>
                                    </tr>
                                    @else
                                        @foreach($balances as $bal)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-sm rounded-circle bg-soft-primary me-2">
                                                        <span class="avatar-title text-uppercase">{{ substr($staff->name, 0, 1) }}</span>
                                                    </div>
                                                    <h5 class="fs-base mb-0">{{ $staff->name }}</h5>
                                                </div>
                                            </td>
                                            <td>{{ $bal->type_label ?? ucfirst($bal->leave_type) }}</td>
                                            <td>{{ $bal->year }}</td>
                                            <td><span class="fw-semibold">{{ $bal->total_days }}</span></td>
                                            <td><span class="text-muted">{{ $bal->used_days }}</span></td>
                                            <td>
                                                @php
                                                    $pct = $bal->total_days > 0 ? ($bal->remaining_days / $bal->total_days) * 100 : 0;
                                                    $color = $pct > 50 ? 'success' : ($pct > 20 ? 'warning' : 'danger');
                                                @endphp
                                                <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
                                                    {{ $bal->remaining_days }} days
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-flex justify-content-center gap-1">
                                                    <a href="{{ route('leave-balances.show', $bal->id) }}" class="btn btn-light btn-icon btn-sm rounded-circle" title="View">
                                                        <i class="ti ti-eye fs-lg"></i>
                                                    </a>
                                                    @can('manage leave balances')
                                                    <a href="{{ route('leave-balances.edit', $bal) }}" class="btn btn-light btn-icon btn-sm rounded-circle" title="Edit">
                                                        <i class="ti ti-edit fs-lg"></i>
                                                    </a>
                                                    @endcan
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    @endif
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="ti ti-calendar-off fs-32 mb-2 d-block"></i>
                                        No staff members found.
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
