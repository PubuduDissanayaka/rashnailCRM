@extends('layouts.vertical', ['title' => 'Edit Coupon - ' . $coupon->name])

@section('css')
    @vite(['node_modules/choices.js/public/assets/styles/choices.min.css', 'node_modules/sweetalert2/dist/sweetalert2.min.css'])
    <style>
        .conditional-field { display: none; }
    </style>
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Edit Coupon', 'subtitle' => 'Coupons'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="POST" action="{{ route('coupons.update', $coupon) }}">
                        @csrf
                        @method('PUT')

                        <ul class="nav nav-tabs mb-4" id="couponTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab">Basic Details</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="discount-tab" data-bs-toggle="tab" data-bs-target="#discount" type="button" role="tab">Discount Settings</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="usage-tab" data-bs-toggle="tab" data-bs-target="#usage" type="button" role="tab">Usage Limits</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="restrictions-tab" data-bs-toggle="tab" data-bs-target="#restrictions" type="button" role="tab">Restrictions</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced" type="button" role="tab">Advanced</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="couponTabsContent">
                            {{-- Basic Details Tab --}}
                            <div class="tab-pane fade show active" id="basic" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="code" class="form-label">Coupon Code <span class="text-danger">*</span></label>
                                            <input class="form-control" type="text" id="code" name="code" value="{{ old('code', $coupon->code) }}" required>
                                            <div class="form-text">Unique coupon code (e.g., SUMMER25). Uppercase letters and numbers only.</div>
                                            @error('code')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="batch_id" class="form-label">Batch (Optional)</label>
                                            <select class="form-select" id="batch_id" name="batch_id">
                                                <option value="">No Batch</option>
                                                @foreach($batches as $batch)
                                                <option value="{{ $batch->id }}" {{ old('batch_id', $coupon->batch_id) == $batch->id ? 'selected' : '' }}>{{ $batch->name }}</option>
                                                @endforeach
                                            </select>
                                            @error('batch_id')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="name" class="form-label">Coupon Name <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" id="name" name="name" value="{{ old('name', $coupon->name) }}" required>
                                    @error('name')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="2">{{ old('description', $coupon->description) }}</textarea>
                                    @error('description')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                            <input class="form-control" type="date" id="start_date" name="start_date" value="{{ old('start_date', $coupon->start_date?->format('Y-m-d')) }}" required>
                                            @error('start_date')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="end_date" class="form-label">End Date (Optional)</label>
                                            <input class="form-control" type="date" id="end_date" name="end_date" value="{{ old('end_date', $coupon->end_date?->format('Y-m-d')) }}">
                                            <div class="form-text">Leave empty for no expiration.</div>
                                            @error('end_date')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="timezone" class="form-label">Timezone <span class="text-danger">*</span></label>
                                    <select class="form-select" id="timezone" name="timezone" required>
                                        <option value="Asia/Colombo" {{ old('timezone', $coupon->timezone ?? 'Asia/Colombo') == 'Asia/Colombo' ? 'selected' : '' }}>Asia/Colombo (UTC+5:30)</option>
                                        <option value="America/New_York" {{ old('timezone', $coupon->timezone) == 'America/New_York' ? 'selected' : '' }}>America/New York</option>
                                        <option value="Europe/London" {{ old('timezone', $coupon->timezone) == 'Europe/London' ? 'selected' : '' }}>Europe/London</option>
                                        <option value="UTC" {{ old('timezone', $coupon->timezone) == 'UTC' ? 'selected' : '' }}>UTC</option>
                                    </select>
                                    @error('timezone')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                </div>
                            </div>

                            {{-- Discount Settings Tab --}}
                            <div class="tab-pane fade" id="discount" role="tabpanel">
                                <div class="mb-3">
                                    <label for="type" class="form-label">Coupon Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="percentage" {{ old('type', $coupon->type) == 'percentage' ? 'selected' : '' }}>Percentage Discount</option>
                                        <option value="fixed" {{ old('type', $coupon->type) == 'fixed' ? 'selected' : '' }}>Fixed Amount Discount</option>
                                        <option value="bogo" {{ old('type', $coupon->type) == 'bogo' ? 'selected' : '' }}>Buy One Get One (BOGO)</option>
                                        <option value="free_shipping" {{ old('type', $coupon->type) == 'free_shipping' ? 'selected' : '' }}>Free Shipping</option>
                                        <option value="tiered" {{ old('type', $coupon->type) == 'tiered' ? 'selected' : '' }}>Tiered Discount</option>
                                    </select>
                                    @error('type')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3" id="discount-percentage-group">
                                            <label for="discount_percentage" class="form-label">Discount Percentage</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="discount_percentage" name="discount_percentage" value="{{ old('discount_percentage', $coupon->type === 'percentage' ? $coupon->discount_value : '') }}" step="0.01" min="0" max="100" placeholder="e.g., 10">
                                                <span class="input-group-text">%</span>
                                            </div>
                                            <div class="form-text">Percentage discount (0-100%).</div>
                                            @error('discount_percentage')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                        </div>
                                        <div class="mb-3" id="discount-value-group">
                                            <label for="discount_value" class="form-label">Discount Amount</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ $currencySymbol }}</span>
                                                <input type="number" class="form-control" id="discount_value" name="discount_value" value="{{ old('discount_value', $coupon->type === 'fixed' ? $coupon->discount_value : '') }}" step="0.01" min="0" placeholder="e.g., 500">
                                            </div>
                                            <div class="form-text">Fixed amount discount.</div>
                                            @error('discount_value')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3" id="max-discount-group">
                                            <label for="max_discount_amount" class="form-label">Max Discount Amount (Optional)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ $currencySymbol }}</span>
                                                <input type="number" class="form-control" id="max_discount_amount" name="max_discount_amount" value="{{ old('max_discount_amount', $coupon->max_discount_amount) }}" step="0.01" min="0" placeholder="e.g., 1000">
                                            </div>
                                            <div class="form-text">For percentage discounts only. Leave empty for no limit.</div>
                                            @error('max_discount_amount')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                        </div>
                                        <div class="mb-3" id="min-purchase-group">
                                            <label for="minimum_purchase_amount" class="form-label">Min Purchase Amount (Optional)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ $currencySymbol }}</span>
                                                <input type="number" class="form-control" id="minimum_purchase_amount" name="minimum_purchase_amount" value="{{ old('minimum_purchase_amount', $coupon->minimum_purchase_amount) }}" step="0.01" min="0" placeholder="e.g., 1000">
                                            </div>
                                            <div class="form-text">Minimum cart total required to apply coupon.</div>
                                            @error('minimum_purchase_amount')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="stackable" name="stackable" value="1" {{ old('stackable', $coupon->stackable) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="stackable">Stackable with other coupons</label>
                                    </div>
                                    <div class="form-text">If enabled, this coupon can be used together with other stackable coupons.</div>
                                    @error('stackable')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                </div>
                            </div>

                            {{-- Usage Limits Tab --}}
                            <div class="tab-pane fade" id="usage" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="total_usage_limit" class="form-label">Total Usage Limit (Optional)</label>
                                            <input type="number" class="form-control" id="total_usage_limit" name="total_usage_limit" value="{{ old('total_usage_limit', $coupon->total_usage_limit) }}" min="1" placeholder="e.g., 100">
                                            <div class="form-text">Max times this coupon can be used overall. Leave empty for unlimited.</div>
                                            @error('total_usage_limit')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="per_customer_limit" class="form-label">Per Customer Limit (Optional)</label>
                                            <input type="number" class="form-control" id="per_customer_limit" name="per_customer_limit" value="{{ old('per_customer_limit', $coupon->per_customer_limit) }}" min="1" placeholder="e.g., 1">
                                            <div class="form-text">Max times a single customer can use this coupon. Leave empty for unlimited.</div>
                                            @error('per_customer_limit')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="active" name="active" value="1" {{ old('active', $coupon->active) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="active">Active</label>
                                    </div>
                                    <div class="form-text">If disabled, coupon cannot be used.</div>
                                    @error('active')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                </div>
                            </div>

                            {{-- Restrictions Tab --}}
                            <div class="tab-pane fade" id="restrictions" role="tabpanel">
                                <h5 class="mb-3">Customer Eligibility</h5>
                                <div class="mb-3">
                                    <label for="customer_eligibility_type" class="form-label">Eligibility Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="customer_eligibility_type" name="customer_eligibility_type" required>
                                        <option value="all" {{ old('customer_eligibility_type', $coupon->customer_eligibility_type) == 'all' ? 'selected' : '' }}>All Customers</option>
                                        <option value="new" {{ old('customer_eligibility_type', $coupon->customer_eligibility_type) == 'new' ? 'selected' : '' }}>New Customers Only</option>
                                        <option value="existing" {{ old('customer_eligibility_type', $coupon->customer_eligibility_type) == 'existing' ? 'selected' : '' }}>Existing Customers Only</option>
                                        <option value="groups" {{ old('customer_eligibility_type', $coupon->customer_eligibility_type) == 'groups' ? 'selected' : '' }}>Specific Customer Groups</option>
                                    </select>
                                    @error('customer_eligibility_type')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                </div>
                                <div class="mb-3 conditional-field" id="customer-groups-group">
                                    <label for="customer_groups" class="form-label">Select Customer Groups</label>
                                    <select class="form-select" name="customer_groups[]" id="customer_groups" data-choices multiple>
                                        @foreach($customerGroups as $group)
                                        <option value="{{ $group->id }}" {{ in_array($group->id, old('customer_groups', $selectedCustomerGroups)) ? 'selected' : '' }}>{{ $group->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Select one or more customer groups.</div>
                                    @error('customer_groups')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                </div>

                                <hr class="my-4">

                                <h5 class="mb-3">Location Restrictions</h5>
                                <div class="mb-3">
                                    <label for="location_restriction_type" class="form-label">Location Restriction Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="location_restriction_type" name="location_restriction_type" required>
                                        <option value="all" {{ old('location_restriction_type', $coupon->location_restriction_type) == 'all' ? 'selected' : '' }}>All Locations</option>
                                        <option value="specific" {{ old('location_restriction_type', $coupon->location_restriction_type) == 'specific' ? 'selected' : '' }}>Specific Locations</option>
                                    </select>
                                    @error('location_restriction_type')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                </div>
                                <div class="mb-3 conditional-field" id="location-specific-group">
                                    <label for="locations" class="form-label">Select Locations</label>
                                    <select class="form-select" name="locations[]" id="locations" data-choices multiple>
                                        @foreach($locations as $location)
                                        <option value="{{ $location->id }}" {{ in_array($location->id, old('locations', $selectedLocations)) ? 'selected' : '' }}>{{ $location->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Select one or more locations. Leave empty for all locations.</div>
                                    @error('locations')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                </div>

                                <hr class="my-4">

                                <h5 class="mb-3">Product Restrictions</h5>
                                <div class="mb-3">
                                    <label for="product_restriction_type" class="form-label">Product Restriction Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="product_restriction_type" name="product_restriction_type" required>
                                        <option value="all" {{ old('product_restriction_type', $coupon->product_restriction_type) == 'all' ? 'selected' : '' }}>All Products/Services</option>
                                        <option value="specific" {{ old('product_restriction_type', $coupon->product_restriction_type) == 'specific' ? 'selected' : '' }}>Specific Products/Services</option>
                                        <option value="categories" {{ old('product_restriction_type', $coupon->product_restriction_type) == 'categories' ? 'selected' : '' }}>Specific Categories</option>
                                    </select>
                                    @error('product_restriction_type')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                </div>
                                <div class="mb-3 conditional-field" id="product-specific-group">
                                    <label for="products" class="form-label">Select Products/Services</label>
                                    <select class="form-select" name="products[]" id="products" data-choices multiple>
                                        @foreach($services as $service)
                                        <option value="service_{{ $service->id }}" {{ in_array('service_' . $service->id, old('products', $selectedProducts)) ? 'selected' : '' }}>{{ $service->name }} (Service)</option>
                                        @endforeach
                                        @foreach($servicePackages as $package)
                                        <option value="package_{{ $package->id }}" {{ in_array('package_' . $package->id, old('products', $selectedProducts)) ? 'selected' : '' }}>{{ $package->name }} (Package)</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Select specific products/services/packages that this coupon applies to.</div>
                                    @error('products')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                </div>
                                <div class="mb-3 conditional-field" id="category-specific-group">
                                    <label for="categories" class="form-label">Select Categories</label>
                                    <select class="form-select" name="categories[]" id="categories" data-choices multiple>
                                        @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ in_array($category->id, old('categories', $selectedCategories)) ? 'selected' : '' }}>{{ $category->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="form-text">Select categories that this coupon applies to.</div>
                                    @error('categories')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                </div>
                            </div>

                            {{-- Advanced Tab --}}
                            <div class="tab-pane fade" id="advanced" role="tabpanel">
                                <div class="mb-3">
                                    <label for="metadata" class="form-label">Metadata (JSON)</label>
                                    <textarea class="form-control" id="metadata" name="metadata" rows="6" placeholder='{"notes": "", "created_by": ""}'>{{ old('metadata', is_array($coupon->metadata) ? json_encode($coupon->metadata) : $coupon->metadata) }}</textarea>
                                    <div class="form-text">Additional metadata in JSON format (optional).</div>
                                    @error('metadata')<span class="text-danger"><small>{{ $message }}</small></span>@enderror
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button class="btn btn-primary px-4" type="submit"><i class="ti ti-device-floppy me-1"></i> Update Coupon</button>
                            <a href="{{ route('coupons.show', $coupon) }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite(['resources/js/pages/form-choice.js', 'node_modules/sweetalert2/dist/sweetalert2.min.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const typeSelect = document.getElementById('type');
            const discountValueGroup = document.getElementById('discount-value-group');
            const discountPercentageGroup = document.getElementById('discount-percentage-group');
            const maxDiscountGroup = document.getElementById('max-discount-group');

            function toggleDiscountFields() {
                const type = typeSelect.value;
                if (type === 'percentage') {
                    discountPercentageGroup.style.display = 'block';
                    discountValueGroup.style.display = 'none';
                    maxDiscountGroup.style.display = 'block';
                } else if (type === 'fixed') {
                    discountPercentageGroup.style.display = 'none';
                    discountValueGroup.style.display = 'block';
                    maxDiscountGroup.style.display = 'none';
                } else {
                    discountPercentageGroup.style.display = 'none';
                    discountValueGroup.style.display = 'none';
                    maxDiscountGroup.style.display = 'none';
                }
            }
            if (typeSelect) {
                typeSelect.addEventListener('change', toggleDiscountFields);
                toggleDiscountFields();
            }

            function toggleConditional(triggerId, targetId, showValue) {
                const trigger = document.getElementById(triggerId);
                const target = document.getElementById(targetId);
                if (!trigger || !target) return;
                function update() { target.style.display = trigger.value === showValue ? 'block' : 'none'; }
                trigger.addEventListener('change', update);
                update();
            }

            toggleConditional('customer_eligibility_type', 'customer-groups-group', 'groups');
            toggleConditional('location_restriction_type', 'location-specific-group', 'specific');
            toggleConditional('product_restriction_type', 'product-specific-group', 'specific');
            toggleConditional('product_restriction_type', 'category-specific-group', 'categories');

            @if(session('success'))
                Swal.fire({ title: 'Success!', text: @json(session('success')), icon: 'success', confirmButtonClass: 'btn btn-primary' });
            @endif
            @if(session('error'))
                Swal.fire({ title: 'Error!', text: @json(session('error')), icon: 'error', confirmButtonClass: 'btn btn-primary' });
            @endif
        });
    </script>
@endsection
