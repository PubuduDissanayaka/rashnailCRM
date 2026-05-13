@extends('layouts.vertical', ['title' => 'Add Leave Balance'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Add Leave Balance'])

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light">
                    <h4 class="card-title">New Leave Balance</h4>
                    <p class="text-muted mb-0">Set leave entitlement for a staff member</p>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <form action="{{ route('leave-balances.store') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Staff Member</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">Select Staff</option>
                                @foreach($staffMembers as $staff)
                                    <option value="{{ $staff->id }}" {{ old('user_id', request('user_id')) == $staff->id ? 'selected' : '' }}>
                                        {{ $staff->name }} ({{ $staff->email }})
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Year</label>
                                <input type="number" name="year" class="form-control" value="{{ old('year', date('Y')) }}" required>
                                @error('year')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Leave Type</label>
                                <select name="leave_type" class="form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="sick" {{ old('leave_type') == 'sick' ? 'selected' : '' }}>Sick Leave</option>
                                    <option value="vacation" {{ old('leave_type') == 'vacation' ? 'selected' : '' }}>Vacation</option>
                                    <option value="personal" {{ old('leave_type') == 'personal' ? 'selected' : '' }}>Personal</option>
                                    <option value="unpaid" {{ old('leave_type') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                                    <option value="emergency" {{ old('leave_type') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                                </select>
                                @error('leave_type')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Total Days</label>
                            <input type="number" name="total_days" class="form-control" value="{{ old('total_days') }}" required min="0" max="365">
                            @error('total_days')<small class="text-danger">{{ $message }}</small>@enderror
                        </div>
                        <div class="text-end">
                            <a href="{{ route('leave-balances.index') }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-1"></i> Save Balance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
