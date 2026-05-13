@extends('layouts.vertical', ['title' => 'Review Leave Request'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Review Leave Request'])

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light">
                    <h4 class="card-title">Review: {{ $leaveRequest->type_label }}</h4>
                    <p class="text-muted mb-0">From <strong>{{ $leaveRequest->user->name }}</strong></p>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <label class="text-muted fs-xs text-uppercase">Staff</label>
                            <p class="fw-semibold">{{ $leaveRequest->user->name }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted fs-xs text-uppercase">Dates</label>
                            <p class="fw-semibold">{{ $leaveRequest->date_range }}</p>
                        </div>
                        <div class="col-md-4">
                            <label class="text-muted fs-xs text-uppercase">Duration</label>
                            <p class="fw-semibold">{{ $leaveRequest->days_count }} day(s)</p>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="text-muted fs-xs text-uppercase">Reason</label>
                        <div class="p-3 bg-light rounded">{{ $leaveRequest->reason }}</div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <form action="{{ route('leaves.approve', $leaveRequest) }}" method="POST">
                                @csrf
                                <div class="card border-success">
                                    <div class="card-body">
                                        <h5 class="card-title text-success">Approve</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Notes (optional)</label>
                                            <textarea name="notes" class="form-control" rows="2" placeholder="Add any notes..."></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="ti ti-check me-1"></i> Approve Leave
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <form action="{{ route('leaves.reject', $leaveRequest) }}" method="POST">
                                @csrf
                                <div class="card border-danger">
                                    <div class="card-body">
                                        <h5 class="card-title text-danger">Reject</h5>
                                        <div class="mb-3">
                                            <label class="form-label">Reason for Rejection</label>
                                            <textarea name="rejection_reason" class="form-control" rows="2" required placeholder="Explain why..."></textarea>
                                        </div>
                                        <button type="submit" class="btn btn-danger w-100">
                                            <i class="ti ti-x me-1"></i> Reject Leave
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
