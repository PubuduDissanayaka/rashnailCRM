@extends('layouts.vertical', ['title' => 'Customer Groups'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Customer Groups'])

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header border-light justify-content-between">
                <h4 class="card-title">Customer Groups</h4>
                <a href="{{ route('customer-groups.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i> Create Group</a>
            </div>
            <div class="table-responsive">
                <table class="table table-custom table-centered table-hover w-100 mb-0">
                    <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                        <tr class="text-uppercase fs-xxs">
                            <th>Name</th><th>Description</th><th>Customers</th><th>Status</th><th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($groups as $group)
                        <tr>
                            <td class="fw-semibold">{{ $group->name }}</td>
                            <td>{{ $group->description ?? '—' }}</td>
                            <td><span class="badge bg-info-subtle text-info">{{ $group->customers_count }}</span></td>
                            <td><span class="badge bg-{{ $group->is_active ? 'success' : 'secondary' }}-subtle text-{{ $group->is_active ? 'success' : 'secondary' }}">{{ $group->is_active ? 'Active' : 'Inactive' }}</span></td>
                            <td class="text-center">
                                <div class="d-flex justify-content-center gap-1">
                                    <a href="{{ route('customer-groups.edit', $group) }}" class="btn btn-light btn-icon btn-sm rounded-circle"><i class="ti ti-edit fs-lg"></i></a>
                                    <form action="{{ route('customer-groups.destroy', $group) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this group?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-danger btn-icon btn-sm rounded-circle"><i class="ti ti-trash fs-lg"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-5 text-muted">No customer groups.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
