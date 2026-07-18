@extends('layouts.vertical', ['title' => 'Notification Providers'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Notification Providers'])

<div class="row">
    @if(isset($stats))
    <div class="col-xl-2 col-md-4 mb-3">
        <div class="card border-start border-3 border-primary shadow-none">
            <div class="card-body py-3"><h5 class="mb-0 fw-bold">{{ $stats['total'] }}</h5><small class="text-muted">Total</small></div>
        </div>
    </div>
    <div class="col-xl-2 col-md-4 mb-3">
        <div class="card border-start border-3 border-success shadow-none">
            <div class="card-body py-3"><h5 class="mb-0 fw-bold">{{ $stats['active'] }}</h5><small class="text-muted">Active</small></div>
        </div>
    </div>
    @foreach(($stats['by_channel'] ?? []) as $ch => $cnt)
    <div class="col-xl-2 col-md-4 mb-3">
        <div class="card border-start border-3 border-info shadow-none">
            <div class="card-body py-3"><h5 class="mb-0 fw-bold">{{ $cnt }}</h5><small class="text-muted">{{ ucfirst($ch) }}</small></div>
        </div>
    </div>
    @endforeach
    @endif
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h4 class="card-title mb-0">Providers</h4>
                <a href="{{ route('notification-providers.create') }}" class="btn btn-primary btn-sm">
                    <i class="ti ti-plus me-1"></i> Add Provider
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Name</th>
                                <th>Channel</th>
                                <th>Provider</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($providers as $p)
                            <tr>
                                <td class="ps-3 fw-semibold">{{ $p->name }} @if($p->is_default)<span class="badge bg-info ms-1">Default</span>@endif</td>
                                <td><span class="badge bg-light text-dark">{{ ucfirst($p->channel) }}</span></td>
                                <td>{{ $p->provider }}</td>
                                <td>{{ $p->priority }}</td>
                                <td>
                                    @if($p->is_active)
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                    @else
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('notification-providers.show', $p->id) }}" class="btn btn-light btn-sm" title="View"><i class="ti ti-eye"></i></a>
                                    <a href="{{ route('notification-providers.edit', $p->id) }}" class="btn btn-warning btn-sm" title="Edit"><i class="ti ti-edit"></i></a>
                                    <form action="{{ route('notification-providers.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this provider?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-danger btn-sm" title="Delete"><i class="ti ti-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No providers configured.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3">{{ $providers->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
