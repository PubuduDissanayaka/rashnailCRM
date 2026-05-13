@extends('layouts.vertical', ['title' => 'Create Customer Group'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Create Customer Group'])
<div class="row"><div class="col-lg-8"><div class="card"><div class="card-body">
<form action="{{ route('customer-groups.store') }}" method="POST">
    @csrf
    <div class="mb-3"><label class="form-label">Name *</label><input type="text" name="name" class="form-control" value="{{ old('name') }}" required></div>
    <div class="mb-3"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea></div>
    <div class="mb-3"><div class="form-check"><input type="checkbox" name="is_active" value="1" class="form-check-input" checked><label class="form-check-label">Active</label></div></div>
    <div class="text-end">
        <a href="{{ route('customer-groups.index') }}" class="btn btn-light me-2">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Create Group</button>
    </div>
</form>
</div></div></div></div>
@endsection
