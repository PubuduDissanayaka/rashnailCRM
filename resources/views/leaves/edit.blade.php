@extends('layouts.vertical', ['title' => 'Edit Leave Request'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Edit Leave Request'])

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-light">
                <h4 class="card-title">Edit Leave Request</h4>
                <p class="text-muted mb-0">Update your leave request details</p>
            </div>
            <div class="card-body">
                @if(session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <form action="{{ route('leaves.update', $leaveRequest->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Leave Type</label>
                            <select name="leave_type" class="form-select" required>
                                <option value="">Select Type</option>
                                @foreach(['sick', 'vacation', 'personal', 'unpaid', 'emergency'] as $type)
                                <option value="{{ $type }}" {{ old('leave_type', $leaveRequest->leave_type) == $type ? 'selected' : '' }}>
                                    {{ ucfirst($type) }} Leave
                                </option>
                                @endforeach
                            </select>
                            @error('leave_type')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">&nbsp;</label>
                            <div class="form-text mt-2">
                                @if(isset($leaveBalances) && $leaveBalances->count() > 0)
                                    @foreach($leaveBalances as $bal)
                                        <span class="badge bg-info-subtle text-info me-1">{{ $bal->type_label ?? $bal->leave_type }}: {{ $bal->remaining_days }} left</span>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" name="start_date" class="form-control"
                                value="{{ old('start_date', $leaveRequest->start_date?->format('Y-m-d')) }}" required min="{{ date('Y-m-d') }}">
                            @error('start_date')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control"
                                value="{{ old('end_date', $leaveRequest->end_date?->format('Y-m-d')) }}" required min="{{ date('Y-m-d') }}">
                            @error('end_date')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <textarea name="reason" class="form-control" rows="4" required placeholder="Explain the reason...">{{ old('reason', $leaveRequest->reason) }}</textarea>
                        @error('reason')<small class="text-danger">{{ $message }}</small>@enderror
                    </div>
                    @if($leaveRequest->status === 'approved' || $leaveRequest->status === 'rejected')
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-1"></i>
                        This leave has already been <strong>{{ $leaveRequest->status }}</strong>.
                        Updates may re-trigger the approval process.
                    </div>
                    @endif
                    <div class="text-end">
                        <a href="{{ route('leaves.show', $leaveRequest->id) }}" class="btn btn-light me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-check me-1"></i> Update Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
