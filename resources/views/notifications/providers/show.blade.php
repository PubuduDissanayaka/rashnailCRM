@extends('layouts.vertical', ['title' => 'Provider Details'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Provider: ' . $provider->name])
<div class="row"><div class="col-12"><div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">{{ $provider->name }} @if($provider->is_default)<span class="badge bg-info ms-2">Default</span>@endif</h4>
        <div class="d-flex gap-1">
            <a href="{{ route('notification-providers.edit', $provider->id) }}" class="btn btn-warning btn-sm"><i class="ti ti-edit me-1"></i>Edit</a>
            <form action="{{ route('notification-providers.destroy', $provider->id) }}" method="POST" class="d-inline"
                  onsubmit="return confirm('Delete provider?')">@csrf @method('DELETE')
                <button class="btn btn-danger btn-sm"><i class="ti ti-trash me-1"></i>Delete</button>
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6 mb-3"><label class="text-muted fs-xs">Channel</label><p class="fw-semibold">{{ ucfirst($provider->channel) }}</p></div>
            <div class="col-md-6 mb-3"><label class="text-muted fs-xs">Provider Type</label><p class="fw-semibold">{{ ucfirst($provider->provider) }}</p></div>
            <div class="col-md-4 mb-3"><label class="text-muted fs-xs">Priority</label><p class="fw-semibold">{{ $provider->priority }}</p></div>
            <div class="col-md-4 mb-3"><label class="text-muted fs-xs">Status</label><p class="fw-semibold">@if($provider->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif</p></div>
            <div class="col-md-4 mb-3"><label class="text-muted fs-xs">Usage</label><p class="fw-semibold">{{ $provider->usage_count ?? 0 }}</p></div>
            @if($provider->daily_limit)<div class="col-md-3 mb-3"><label class="text-muted fs-xs">Daily Limit</label><p class="fw-semibold">{{ $provider->daily_limit }}</p></div>@endif
            @if($provider->monthly_limit)<div class="col-md-3 mb-3"><label class="text-muted fs-xs">Monthly Limit</label><p class="fw-semibold">{{ $provider->monthly_limit }}</p></div>@endif
        </div>
    </div>
</div></div></div>

@if(isset($usageStats) && count($usageStats))
<div class="row"><div class="col-12"><div class="card"><div class="card-header"><h5 class="card-title">Usage Statistics</h5></div>
<div class="card-body"><div class="table-responsive"><table class="table table-sm"><thead><tr><th>Period</th><th>Sent</th><th>Failed</th><th>Total</th></tr></thead>
<tbody>@foreach($usageStats as $stat)<tr><td>{{ $stat['period'] }}</td><td>{{ $stat['sent'] ?? 0 }}</td><td>{{ $stat['failed'] ?? 0 }}</td><td>{{ $stat['total'] ?? 0 }}</td></tr>@endforeach</tbody></table></div></div></div></div></div>
@endif
@endsection
