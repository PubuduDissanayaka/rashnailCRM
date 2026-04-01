@extends('layouts.vertical', ['title' => 'Coupon Details'])

@section('css')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.css'])
@endsection

@section('scripts')
    @vite(['node_modules/sweetalert2/dist/sweetalert2.min.js'])
@endsection

@section('content')
    @include('layouts.partials.page-title', ['title' => 'Coupon Details'])

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div>
                        <h4 class="card-title mb-0">{{ $coupon->name }}</h4>
                        <p class="text-muted mb-0">Code: <strong>{{ $coupon->code }}</strong></p>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <a href="{{ route('coupons.index') }}" class="btn btn-secondary">
                            <i class="ti ti-arrow-left me-1"></i> Back
                        </a>
                        @can('edit coupons')
                        <a href="{{ route('coupons.edit', $coupon) }}" class="btn btn-primary">
                            <i class="ti ti-edit me-1"></i> Edit
                        </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Coupon Type</h6>
                                    <p class="mb-3">
                                        <span class="badge bg-info-subtle text-info">
                                            {{ ucfirst(str_replace('_', ' ', $coupon->type)) }}
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Status</h6>
                                    <p class="mb-3">
                                        @php
                                            $statusClass = $coupon->isActive() ? 'success' : ($coupon->isExpired() ? 'danger' : 'secondary');
                                            $statusText = $coupon->isActive() ? 'Active' : ($coupon->isExpired() ? 'Expired' : 'Inactive');
                                        @endphp
                                        <span class="badge bg-{{ $statusClass }}-subtle text-{{ $statusClass }}">
                                            <i class="ti ti-circle-filled fs-xs"></i> {{ $statusText }}
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Discount Value</h6>
                                    <p class="mb-3">
                                        @if($coupon->type === 'percentage')
                                            {{ $coupon->discount_percentage }}% @if($coupon->max_discount_amount) (max {{ $currencySymbol }}{{ number_format($coupon->max_discount_amount, 2) }}) @endif
                                        @elseif($coupon->type === 'fixed')
                                            {{ $currencySymbol }}{{ number_format($coupon->discount_value, 2) }}
                                        @elseif($coupon->type === 'bogo')
                                            Buy 1 Get 1
                                        @elseif($coupon->type === 'free_shipping')
                                            Free Shipping
                                        @elseif($coupon->type === 'tiered')
                                            Tiered Discount
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Minimum Purchase</h6>
                                    <p class="mb-3">
                                        @if($coupon->minimum_purchase_amount)
                                            {{ $currencySymbol }}{{ number_format($coupon->minimum_purchase_amount, 2) }}
                                        @else
                                            <span class="text-muted">No minimum</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Validity Period</h6>
                                    <p class="mb-3">
                                        @if($coupon->valid_from && $coupon->valid_until)
                                            {{ $coupon->valid_from->format('M d, Y') }} - {{ $coupon->valid_until->format('M d, Y') }}
                                        @elseif($coupon->valid_from)
                                            From {{ $coupon->valid_from->format('M d, Y') }}
                                        @else
                                            No expiry
                                        @endif
                                        <br><small class="text-muted">Timezone: {{ $coupon->timezone }}</small>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Usage Limits</h6>
                                    <p class="mb-3">
                                        Total: {{ $coupon->usage_limit ?: '∞' }}<br>
                                        Per Customer: {{ $coupon->per_customer_limit ?: '∞' }}<br>
                                        Used: {{ $coupon->usage_count ?? 0 }} times
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Stackable</h6>
                                    <p class="mb-3">
                                        @if($coupon->stackable)
                                            <span class="badge bg-success-subtle text-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">No</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-uppercase text-muted fs-xs mb-2">Batch</h6>
                                    <p class="mb-3">
                                        @if($coupon->batch)
                                            {{ $coupon->batch->name }}
                                        @else
                                            <span class="text-muted">No batch</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h6 class="text-uppercase text-muted fs-xs mb-2">Description</h6>
                                <p class="mb-0">{{ $coupon->description ?: 'No description provided.' }}</p>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">Redemption Stats</h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <h2 class="mb-1">{{ $stats['total_redemptions'] ?? 0 }}</h2>
                                        <p class="text-muted mb-0">Total Redemptions</p>
                                    </div>
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h5 class="mb-1">{{ $stats['unique_customers'] ?? 0 }}</h5>
                                            <p class="text-muted mb-0 fs-xs">Unique Customers</p>
                                        </div>
                                        <div class="col-6">
                                            <h5 class="mb-1">{{ $currencySymbol }}{{ number_format($stats['total_discount_amount'] ?? 0, 2) }}</h5>
                                            <p class="text-muted mb-0 fs-xs">Total Discount</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card border mt-3">
                                <div class="card-header bg-light">
                                    <h5 class="card-title mb-0">Restrictions</h5>
                                </div>
                                <div class="card-body">
                                    <h6 class="fs-sm mb-2">Customer Eligibility</h6>
                                    <p class="mb-2">
                                        {{ ucfirst($coupon->customer_eligibility_type) }}
                                        @if($coupon->customer_eligibility_type === 'groups' && $coupon->customerGroups->count())
                                            <br>
                                            <small>
                                                Groups:
                                                @foreach($coupon->customerGroups as $group)
                                                    <span class="badge bg-primary-subtle text-primary">{{ $group->name }}</span>
                                                @endforeach
                                            </small>
                                        @endif
                                    </p>
                                    
                                    <h6 class="fs-sm mb-2 mt-3">Location Restrictions</h6>
                                    <p class="mb-2">
                                        {{ ucfirst($coupon->location_restriction_type) }}
                                        @if($coupon->location_restriction_type === 'specific' && $coupon->locations->count())
                                            <br>
                                            <small>
                                                Locations:
                                                @foreach($coupon->locations as $location)
                                                    <span class="badge bg-secondary-subtle text-secondary">{{ $location->name }}</span>
                                                @endforeach
                                            </small>
                                        @endif
                                    </p>
                                    
                                    <h6 class="fs-sm mb-2 mt-3">Product Restrictions</h6>
                                    <p class="mb-2">
                                        {{ ucfirst($coupon->product_restriction_type) }}
                                        @if($coupon->product_restriction_type !== 'all' && ($coupon->products->count() || $coupon->categories->count()))
                                            <br>
                                            <small>
                                                @if($coupon->products->count())
                                                    Products/Services: {{ $coupon->products->count() }}<br>
                                                @endif
                                                @if($coupon->categories->count())
                                                    Categories: 
                                                    @foreach($coupon->categories as $category)
                                                        <span class="badge bg-info-subtle text-info">{{ $category->name }}</span>
                                                    @endforeach
                                                @endif
                                            </small>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Redemptions Table -->
                    <div class="mt-5">
                        <h5 class="mb-3">Redemption History</h5>
                        @if($coupon->redemptions->count())
                        <div class="table-responsive">
                            <table class="table table-custom table-centered table-hover w-100 mb-0">
                                <thead class="bg-light align-middle bg-opacity-25 thead-sm">
                                    <tr class="text-uppercase fs-xxs">
                                        <th class="ps-3">Date</th>
                                        <th>Sale ID</th>
                                        <th>Customer</th>
                                        <th>Discount Applied</th>
                                        <th>Sale Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($coupon->redemptions as $redemption)
                                    <tr>
                                        <td class="ps-3">{{ $redemption->created_at->format('M d, Y H:i') }}</td>
                                        <td>
                                            @if($redemption->sale)
                                            <a href="{{ route('pos.receipt', $redemption->sale) }}" target="_blank">
                                                #{{ $redemption->sale->sale_number }}
                                            </a>
                                            @else
                                            N/A
                                            @endif
                                        </td>
                                        <td>
                                            @if($redemption->customer)
                                            {{ $redemption->customer->full_name }}
                                            @else
                                            Guest
                                            @endif
                                        </td>
                                        <td>{{ $currencySymbol }}{{ number_format($redemption->discount_amount, 2) }}</td>
                                        <td>
                                            @if($redemption->sale)
                                            {{ $currencySymbol }}{{ number_format($redemption->sale->total_amount, 2) }}
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @else
                        <div class="alert alert-light">
                            <i class="ti ti-history me-2"></i> No redemption history yet.
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection