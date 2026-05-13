@extends('layouts.vertical', ['title' => 'Leave Request Detail'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Leave Request Detail'])

    @php
        $statusColors = ['approved' => 'success', 'rejected' => 'danger', 'pending' => 'warning', 'cancelled' => 'secondary'];
        $color = $statusColors[$leaveRequest->status] ?? 'secondary';
    @endphp

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title">{{ $leaveRequest->type_label }}</h4>
                        <span class="badge bg-{{ $color }}-subtle text-{{ $color }} fs-sm">
                            <i class="ti ti-circle-filled fs-xs me-1"></i> {{ $leaveRequest->status_label }}
                        </span>
                    </div>
                    <div>
                        @if($leaveRequest->user_id === auth()->id() && $leaveRequest->status === 'pending')
                        <form action="{{ route('leaves.cancel', $leaveRequest) }}" method="POST" class="d-inline" onsubmit="return confirm('Cancel this leave request?')">
                            @csrf
                            @method('PUT')
                            <button class="btn btn-outline-danger btn-sm">
                                <i class="ti ti-x me-1"></i> Cancel Request
                            </button>
                        </form>
                        @endif
                        @can('approve leave requests')
                            @if($leaveRequest->status === 'pending')
                            <a href="{{ route('leaves.approval', $leaveRequest) }}" class="btn btn-primary btn-sm">
                                <i class="ti ti-check me-1"></i> Review
                            </a>
                            @endif
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted fs-xs text-uppercase">Staff Member</label>
                            <p class="fw-semibold">{{ $leaveRequest->user->name ?? 'Unknown' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fs-xs text-uppercase">Leave Type</label>
                            <p class="fw-semibold">{{ $leaveRequest->type_label }}</p>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted fs-xs text-uppercase">Date Range</label>
                            <p class="fw-semibold">{{ $leaveRequest->date_range }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fs-xs text-uppercase">Duration</label>
                            <p class="fw-semibold">{{ $leaveRequest->days_count }} day(s)</p>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted fs-xs text-uppercase">Reason</label>
                        <div class="p-3 bg-light rounded">
                            {{ $leaveRequest->reason }}
                        </div>
                    </div>
                    @if($leaveRequest->status === 'approved' || $leaveRequest->status === 'rejected')
                    <div class="row">
                        <div class="col-md-6">
                            <label class="text-muted fs-xs text-uppercase">{{ $leaveRequest->status === 'approved' ? 'Approved By' : 'Rejected By' }}</label>
                            <p class="fw-semibold">{{ $leaveRequest->approver->name ?? '—' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted fs-xs text-uppercase">Date</label>
                            <p class="fw-semibold">{{ $leaveRequest->approved_at?->format('M d, Y h:i A') ?? '—' }}</p>
                        </div>
                    </div>
                        @if($leaveRequest->rejection_reason)
                        <div class="mb-3">
                            <label class="text-muted fs-xs text-uppercase">Notes</label>
                            <div class="p-3 bg-light rounded">{{ $leaveRequest->rejection_reason }}</div>
                        </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title">Timeline</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 timeline">
                        <li class="mb-3">
                            <small class="text-muted">{{ $leaveRequest->created_at->format('M d, Y h:i A') }}</small>
                            <p class="mb-0 fw-semibold">Leave Request Submitted</p>
                        </li>
                        @if($leaveRequest->status === 'approved')
                        <li class="mb-3">
                            <small class="text-muted">{{ $leaveRequest->approved_at?->format('M d, Y h:i A') }}</small>
                            <p class="mb-0 fw-semibold text-success">Approved</p>
                        </li>
                        @elseif($leaveRequest->status === 'rejected')
                        <li class="mb-3">
                            <small class="text-muted">{{ $leaveRequest->approved_at?->format('M d, Y h:i A') }}</small>
                            <p class="mb-0 fw-semibold text-danger">Rejected</p>
                        </li>
                        @elseif($leaveRequest->status === 'cancelled')
                        <li class="mb-3">
                            <p class="mb-0 fw-semibold text-secondary">Cancelled</p>
                        </li>
                        @else
                        <li>
                            <p class="mb-0 text-muted">Awaiting approval...</p>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
