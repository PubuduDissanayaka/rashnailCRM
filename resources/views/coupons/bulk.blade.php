@extends('layouts.vertical', ['title' => 'Bulk Generate Coupons'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Bulk Generate Coupons'])

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('coupons.bulk.generate') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Batch Name *</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Summer Sale 2026">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code Pattern *</label>
                            <input type="text" name="pattern" class="form-control" required placeholder="e.g. SUMMER-{RANDOM:6}" value="{{ old('pattern', 'COUPON-{RANDOM:6}') }}">
                            <small class="text-muted">{RANDOM:N} generates N random characters</small>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Number of Coupons *</label>
                            <input type="number" name="count" class="form-control" value="{{ old('count', 10) }}" min="1" max="500" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Coupon Type *</label>
                            <select name="coupon_type" class="form-select" required>
                                <option value="fixed">Fixed Amount</option>
                                <option value="percentage">Percentage</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Discount Value *</label>
                            <input type="number" name="discount_value" class="form-control" value="{{ old('discount_value', 10) }}" min="0" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Valid For (Days) *</label>
                            <input type="number" name="valid_days" class="form-control" value="{{ old('valid_days', 30) }}" min="1" max="3650" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Usage Limit</label>
                            <input type="number" name="usage_limit" class="form-control" value="{{ old('usage_limit', 1) }}" min="1">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Per Customer *</label>
                            <input type="number" name="per_customer" class="form-control" value="{{ old('per_customer', 1) }}" min="1" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Min Purchase</label>
                            <input type="number" name="min_purchase" class="form-control" value="{{ old('min_purchase', 0) }}" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="text-end">
                        <a href="{{ route('coupons.index') }}" class="btn btn-light me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-copy me-1"></i> Generate Coupons</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
