@extends('layouts.vertical', ['title' => 'Create Coupon'])

@section('content')
@include('layouts.partials.page-title', ['title' => 'Create Coupon'])

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <form action="{{ route('coupons.store') }}" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Code *</label>
                            <input type="text" name="code" class="form-control" value="{{ old('code') }}" required maxlength="50">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Name *</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Type *</label>
                            <select name="type" class="form-select" required>
                                <option value="percentage">Percentage</option>
                                <option value="fixed">Fixed Amount</option>
                                <option value="bogo">Buy One Get One</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Discount %</label>
                            <input type="number" name="discount_percentage" class="form-control" value="{{ old('discount_percentage') }}" min="0" max="100" step="0.01">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Min Purchase</label>
                            <input type="number" name="minimum_purchase_amount" class="form-control" value="{{ old('minimum_purchase_amount', 0) }}" min="0" step="0.01">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Max Discount</label>
                            <input type="number" name="max_discount_amount" class="form-control" value="{{ old('max_discount_amount') }}" min="0" step="0.01">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Start Date *</label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date', date('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">End Date</label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Usage Limit</label>
                            <input type="number" name="total_usage_limit" class="form-control" value="{{ old('total_usage_limit') }}" min="1">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Per Customer Limit</label>
                            <input type="number" name="per_customer_limit" class="form-control" value="{{ old('per_customer_limit', 1) }}" min="1">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Location Restriction</label>
                            <select name="location_restriction_type" class="form-select" required>
                                <option value="all">All Locations</option>
                                <option value="specific">Specific Locations</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Customer Eligibility</label>
                            <select name="customer_eligibility_type" class="form-select" required>
                                <option value="all">All Customers</option>
                                <option value="new">New Customers</option>
                                <option value="existing">Existing Customers</option>
                                <option value="groups">Specific Groups</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Product Restriction</label>
                            <select name="product_restriction_type" class="form-select" required>
                                <option value="all">All Products</option>
                                <option value="specific">Specific Products</option>
                                <option value="categories">Categories</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-3">
                            <div class="form-check"><input type="checkbox" name="stackable" value="1" class="form-check-input"><label class="form-check-label">Stackable</label></div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-check"><input type="checkbox" name="active" value="1" class="form-check-input" checked><label class="form-check-label">Active</label></div>
                        </div>
                    </div>
                    <input type="hidden" name="timezone" value="UTC">
                    <div class="text-end">
                        <a href="{{ route('coupons.index') }}" class="btn btn-light me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary"><i class="ti ti-check me-1"></i> Create Coupon</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
