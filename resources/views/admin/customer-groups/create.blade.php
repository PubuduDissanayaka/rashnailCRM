@extends('layouts.vertical', ['title' => 'Create Customer Group'])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css'])
@endsection

@section('scripts')
    @vite(['resources/js/pages/form-choice.js'])
    <script>
        // Initialize any needed JS
    </script>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Create Customer Group'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light">
                    <h4 class="card-title mb-0">New Customer Group</h4>
                    <p class="text-muted mb-0">Define a group of customers for coupon eligibility</p>
                </div>
                <div class="card-body">
                    <form action="{{ route('customer-groups.store') }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Group Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g., Premium Members">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <small class="form-text text-muted">Unique identifier for this group.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="is_active" class="form-label">Status</label>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="is_active">Active</label>
                                    </div>
                                    <small class="form-text text-muted">Inactive groups cannot be used in coupons.</small>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="3" placeholder="Optional description of this group">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="criteria" class="form-label">Criteria (JSON)</label>
                            <textarea class="form-control @error('criteria') is-invalid @enderror" id="criteria" name="criteria" rows="5" placeholder='{"min_orders": 5, "has_membership": true}'>{{ old('criteria') }}</textarea>
                            @error('criteria')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Optional JSON criteria for automatic customer matching. Leave empty for manual assignment.</small>
                        </div>
                        <div class="d-flex justify-content-between">
                            <a href="{{ route('customer-groups.index') }}" class="btn btn-secondary">
                                <i class="ti ti-arrow-left me-1"></i> Back to List
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-1"></i> Create Group
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection