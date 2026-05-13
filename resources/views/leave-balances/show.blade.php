@extends('layouts.vertical', ['title' => 'Leave Balance Detail'])

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Leave Balance Detail'])

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-light d-flex justify-content-between align-items-center">
                    <div>
                        <h4 class="card-title">{{ $balance->user->name }} — {{ ucfirst($balance->leave_type) }}</h4>
                        <p class="text-muted mb-0">Year {{ $balance->year }}</p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4 text-center">
                            <div class="p-3 border rounded">
                                <h2 class="text-primary mb-0">{{ $balance->total_days }}</h2>
                                <small class="text-muted text-uppercase">Total Days</small>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="p-3 border rounded">
                                <h2 class="text-warning mb-0">{{ $balance->used_days }}</h2>
                                <small class="text-muted text-uppercase">Used Days</small>
                            </div>
                        </div>
                        <div class="col-md-4 text-center">
                            <div class="p-3 border rounded">
                                <h2 class="text-{{ $balance->remaining_days > 0 ? 'success' : 'danger' }} mb-0">{{ $balance->remaining_days }}</h2>
                                <small class="text-muted text-uppercase">Remaining</small>
                            </div>
                        </div>
                    </div>
                    @php $pct = $balance->total_days > 0 ? ($balance->used_days / $balance->total_days) * 100 : 0; @endphp
                    <div class="mb-3">
                        <label class="form-label">Usage</label>
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-{{ $pct > 80 ? 'danger' : ($pct > 50 ? 'warning' : 'success') }}" style="width: {{ $pct }}%">
                                {{ round($pct) }}%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header border-light">
                    <h5 class="card-title">Staff Info</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <div class="avatar-lg rounded-circle bg-soft-primary mx-auto mb-2">
                            <span class="avatar-title rounded-circle text-uppercase fs-24">{{ substr($balance->user->name, 0, 1) }}</span>
                        </div>
                        <h5 class="mb-0">{{ $balance->user->name }}</h5>
                        <small class="text-muted">{{ $balance->user->email }}</small>
                    </div>
                </div>
            </div>
            @can('manage leave balances')
            <div class="mt-3">
                <a href="{{ route('leave-balances.edit', $balance) }}" class="btn btn-primary w-100">
                    <i class="ti ti-edit me-1"></i> Edit Balance
                </a>
            </div>
            @endcan
        </div>
    </div>
@endsection
