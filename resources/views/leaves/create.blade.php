@extends('layouts.vertical', ['title' => 'Request Leave'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Request Leave'])

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light">
                    <h4 class="card-title">New Leave Request</h4>
                    <p class="text-muted mb-0">Submit a leave request for approval</p>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('leaves.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Leave Type</label>
                                <select name="leave_type" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="sick" {{ old('leave_type') == 'sick' ? 'selected' : '' }}>Sick Leave</option>
                                    <option value="vacation" {{ old('leave_type') == 'vacation' ? 'selected' : '' }}>Vacation Leave</option>
                                    <option value="personal" {{ old('leave_type') == 'personal' ? 'selected' : '' }}>Personal Leave</option>
                                    <option value="unpaid" {{ old('leave_type') == 'unpaid' ? 'selected' : '' }}>Unpaid Leave</option>
                                    <option value="emergency" {{ old('leave_type') == 'emergency' ? 'selected' : '' }}>Emergency Leave</option>
                                </select>
                                @error('leave_type')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">&nbsp;</label>
                                <div class="form-text mt-2">
                                    @if($leaveBalances->count() > 0)
                                        @foreach($leaveBalances as $bal)
                                            <span class="badge bg-info-subtle text-info me-1">{{ $bal->type_label ?? $bal->leave_type }}: {{ $bal->remaining_days }} left</span>
                                        @endforeach
                                    @else
                                        <span class="text-muted">No leave balances configured yet.</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required min="{{ date('Y-m-d') }}">
                                @error('start_date')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required min="{{ date('Y-m-d') }}">
                                @error('end_date')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Reason</label>
                            <textarea name="reason" class="form-control" rows="4" required placeholder="Explain the reason for your leave request...">{{ old('reason') }}</textarea>
                            @error('reason')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="text-end">
                            <a href="{{ route('leaves.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-send me-1"></i> Submit Request
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title">Leave Policy</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="ti ti-check text-success me-1"></i> Sick leave: Submit before or on the day</li>
                        <li class="mb-2"><i class="ti ti-check text-success me-1"></i> Vacation: Request at least 3 days ahead</li>
                        <li class="mb-2"><i class="ti ti-check text-success me-1"></i> Personal: Request at least 1 day ahead</li>
                        <li class="mb-2"><i class="ti ti-alert-triangle text-warning me-1"></i> Overlapping requests will be rejected</li>
                        <li><i class="ti ti-info-circle text-info me-1"></i> Approval required before leave starts</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
