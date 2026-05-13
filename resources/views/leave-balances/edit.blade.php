@extends('layouts.vertical', ['title' => 'Edit Leave Balance'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Edit Leave Balance'])

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light">
                    <h4 class="card-title">{{ $leaveBalance->user->name }} — {{ ucfirst($leaveBalance->leave_type) }} ({{ $leaveBalance->year }})</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('leave-balances.update', $leaveBalance) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Total Days</label>
                                <input type="number" name="total_days" class="form-control" value="{{ old('total_days', $leaveBalance->total_days) }}" required min="0" max="365">
                                @error('total_days')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Used Days</label>
                                <input type="number" name="used_days" class="form-control" value="{{ old('used_days', $leaveBalance->used_days) }}" required min="0">
                                @error('used_days')<small class="text-danger">{{ $message }}</small>@enderror
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle me-1"></i>
                            Remaining days will be calculated automatically: <strong>Total - Used = Remaining</strong>
                        </div>
                        <div class="text-end">
                            <a href="{{ route('leave-balances.show', $leaveBalance->id) }}" class="btn btn-light me-2">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-device-floppy me-1"></i> Update Balance
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-danger">
                <div class="card-header border-danger bg-danger bg-opacity-10">
                    <h5 class="card-title text-danger mb-0">Delete Balance</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Remove this leave balance record permanently.</p>
                    <form action="{{ route('leave-balances.destroy', $leaveBalance) }}" method="POST" onsubmit="return confirm('Delete this leave balance?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger w-100">
                            <i class="ti ti-trash me-1"></i> Delete Balance
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
