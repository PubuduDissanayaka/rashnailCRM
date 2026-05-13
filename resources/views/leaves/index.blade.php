@extends('layouts.vertical', ['title' => 'Leave Requests'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Leave Requests'])

    <div class="row">
        <div class="col-12">
            <div class="card" data-table="" data-table-rows-per-page="10">
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex gap-2">
                        <div class="app-search">
                            <input class="form-control" data-table-search="" placeholder="Search requests..." type="search" />
                            <i class="app-search-icon text-muted" data-lucide="search"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <span class="me-2 fw-semibold">Filter By:</span>
                        <div class="app-search">
                            <select class="form-select form-control my-1 my-md-0" data-table-filter="status">
                                <option value="All">Status</option>
                                <option value="Pending Approval">Pending</option>
                                <option value="Approved">Approved</option>
                                <option value="Rejected">Rejected</option>
                                <option value="Cancelled">Cancelled</option>
                            </select>
                            <i class="app-search-icon text-muted" data-lucide="filter"></i>
                        </div>
                        <div>
                            <select class="form-select form-control my-1 my-md-0" data-table-set-rows-per-page="">
                                <option value="5">5</option>
                                <option value="10" selected>10</option>
                                <option value="15">15</option>
                                <option value="20">20</option>
                            </select>
                        </div>
                        @can('create leave requests')
                        <a href="{{ route('leaves.create') }}" class="btn btn-primary">
                            <i class="ti ti-plus me-1"></i> Request Leave
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-custom table-centered table-hover w-100 mb-0">
                        <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                            <tr class="text-uppercase fs-xxs">
                                <th data-table-sort="sort-staff">Staff</th>
                                <th data-table-sort="sort-type">Type</th>
                                <th data-table-sort="sort-dates">Dates</th>
                                <th data-table-sort="sort-days">Days</th>
                                <th data-table-sort="sort-status">Status</th>
                                <th data-table-sort="sort-requested">Requested</th>
                                <th class="text-center" style="width: 1%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaveRequests as $lr)
                            @php
                                $statusColors = ['approved' => 'success', 'rejected' => 'danger', 'pending' => 'warning', 'cancelled' => 'secondary'];
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
                                <td data-sort="{{ $lr->leave_type }}">{{ $lr->type_label }}</td>
                                <td data-sort="{{ $lr->start_date->format('Y-m-d') }}">{{ $lr->date_range }}</td>
                                <td data-sort="{{ $lr->days_count }}">{{ $lr->days_count }} day(s)</td>
                                <td data-sort="{{ ucfirst($lr->status) }}">
                                    <span class="badge bg-{{ $color }}-subtle text-{{ $color }}">
                                        <i class="ti ti-circle-filled fs-xs me-1"></i> {{ $lr->status_label }}
                                    </span>
                                </td>
                                <td data-sort="{{ $lr->created_at->format('Y-m-d') }}">{{ $lr->created_at->format('M d, Y') }}</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <a href="{{ route('leaves.show', $lr) }}" class="btn btn-light btn-icon btn-sm rounded-circle" title="View">
                                            <i class="ti ti-eye fs-lg"></i>
                                        </a>
                                        @can('approve leave requests')
                                            @if($lr->status === 'pending')
                                            <a href="{{ route('leaves.approval', $lr) }}" class="btn btn-light btn-icon btn-sm rounded-circle" title="Review">
                                                <i class="ti ti-check fs-lg"></i>
                                            </a>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="ti ti-calendar-off fs-32 mb-2 d-block"></i>
                                    No leave requests found.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer border-0">
                    {{ $leaveRequests->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/custom-table.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
@endsection
